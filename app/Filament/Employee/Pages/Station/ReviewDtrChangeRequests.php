<?php

namespace App\Filament\Employee\Pages\Station;

use App\Models\Branch;
use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Filament\Employee\Pages\Station\StationChangeRequestHistory;
use App\Services\DtrChangeRequestService;
use App\Services\StationManagementAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Symfony\Component\HttpKernel\Exception\HttpException;
use UnitEnum;

class ReviewDtrChangeRequests extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.employee.pages.station.table-page';

    protected static ?string $slug = 'station/change-requests';

    protected static ?string $title = 'Station Change Requests';

    protected static ?string $navigationLabel = 'Change Requests';

    protected static string|UnitEnum|null $navigationGroup = 'Station Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return StationManagementAccess::canAccessStationManagement(auth()->user());
    }

    public static function getNavigationBadge(): ?string
    {
        if (! SchemaFacade::hasTable('dtr_change_requests')) {
            return null;
        }

        $user = auth()->user();
        $employee = $user?->employee;
        if (! $employee || ! StationManagementAccess::canAccessStationManagement($user)) {
            return null;
        }

        $count = DtrChangeRequest::query()
            ->pendingForApprover($employee)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pending station D.T.R change requests';
    }

    public function mount(): void
    {
        if (! StationManagementAccess::canAccessStationManagement(auth()->user())) {
            throw new HttpException(403, 'Unauthorized access to Station Management.');
        }
    }

    public function getSubheading(): string
    {
        return 'Review attendance change requests from employees in your assigned stations.';
    }

    public function table(Table $table): Table
    {
        $employee = auth()->user()?->employee;

        return $table
            ->query(fn (): Builder => DtrChangeRequest::query()
                ->pendingForApprover($employee)
                ->with(['employee', 'branch', 'payrollPeriod', 'reviewedByEmployee.user', 'assignedEmployee'])
                ->latest('created_at'))
            ->heading('Employee D.T.R Change Requests')
            ->description('Review attendance change requests sequentially. Level 1 approvers may correct D.T.R entries prior to approval.')
            ->columns([
                TextColumn::make('index')->label('#')->rowIndex(),

                TextColumn::make('employee_name_snapshot')
                    ->label('Employee')
                    ->description(fn (DtrChangeRequest $record): string => ($record->employee_company_id_snapshot ? "ID: {$record->employee_company_id_snapshot}" : '') . ($record->branch_name_snapshot ? " • {$record->branch_name_snapshot}" : ''))
                    ->searchable(['employee_name_snapshot', 'employee_company_id_snapshot', 'branch_name_snapshot'])
                    ->sortable(),

                TextColumn::make('branch_name_snapshot')
                    ->label('Station / Scope')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('dtr_date')
                    ->label('D.T.R Date')
                    ->getStateUsing(function (DtrChangeRequest $record): ?string {
                        if ($record->request_type === DtrChangeRequest::TYPE_MISSING_DTR_ENTRIES) {
                            $count = count($record->proposed_values['entries'] ?? []);
                            $from = $record->date_from ? Carbon::parse($record->date_from)->format('M d') : '';
                            $to = $record->date_to ? Carbon::parse($record->date_to)->format('M d, Y') : '';
                            $range = ($from && $to) ? ($from === Carbon::parse($record->date_to)->format('M d') ? $to : "{$from} - {$to}") : '-';
                            return "{$range} ({$count} entries)";
                        }
                        return filled($record->dtr_date ?: $record->date_from) ? Carbon::parse($record->dtr_date ?: $record->date_from)->format('M d, Y') : '-';
                    })
                    ->description(fn (DtrChangeRequest $record): ?string => $record->created_at?->format('M d, h:i A'))
                    ->sortable(),

                TextColumn::make('request_type_label')
                    ->label('Request Type')
                    ->badge()
                    ->color('info'),

                TextColumn::make('display_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (DtrChangeRequest $record): string => match ($record->status) {
                        DtrChangeRequest::STATUS_APPROVED => 'success',
                        DtrChangeRequest::STATUS_REJECTED => 'danger',
                        DtrChangeRequest::STATUS_CANCELLED => 'gray',
                        default => 'warning',
                    })
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('status', $direction)),

                TextColumn::make('current_level')
                    ->label('Approval Level')
                    ->formatStateUsing(fn (DtrChangeRequest $record): string => $record->status === DtrChangeRequest::STATUS_PENDING
                        ? "L{$record->current_level}/{$record->total_levels}: ".($record->current_step_label ?? 'Review')
                        : ($record->status === DtrChangeRequest::STATUS_APPROVED ? 'Completed' : 'Finalized')),

                TextColumn::make('created_at')
                    ->label('Date Submitted')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('description')
                    ->label('Explanation')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Station / Scope')
                    ->options(function (): array {
                        $branches = StationManagementAccess::getAssignedBranches(auth()->user());
                        return collect($branches)->pluck('branch_name', 'branch_id')->all();
                    })
                    ->searchable(),

                SelectFilter::make('request_type')
                    ->label('Request Type')
                    ->options(DtrChangeRequest::requestTypeOptions()),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon(Heroicon::Eye)
                        ->visible(fn (DtrChangeRequest $record): bool => $record->canBeReviewedBy(auth()->user()))
                        ->modalHeading('D.T.R Change Request')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalWidth('2xl')
                        ->modalContent(fn (DtrChangeRequest $record) => view('filament.employee.pages.partials.dtr-change-request-details-modal', [
                            'record' => $record,
                        ])),

                    Action::make('editDtr')
                        ->label('Edit & Review D.T.R')
                        ->icon(Heroicon::PencilSquare)
                        ->color('warning')
                        ->visible(fn (DtrChangeRequest $record): bool => $record->status === DtrChangeRequest::STATUS_PENDING
                            && $record->is_first_level
                            && $record->is_editable_type
                            && $record->canBeEditedBy(auth()->user()))
                        ->modalWidth('2xl')
                        ->modalHeading('Edit & Review D.T.R Information')
                        ->modalDescription('Correct the time punches and schedule. Clicking Approve will automatically save your changes and approve the request.')
                        ->modalSubmitActionLabel('Approve & Save Changes')
                        ->modalSubmitAction(fn ($action) => $action->color('success')->icon('heroicon-m-check-circle'))
                        ->form([
                            DatePicker::make('date_in')
                                ->label('Date In')
                                ->default(fn (DtrChangeRequest $record): ?string => $record->proposed_values['date_in'] ?? $record->original_values['date_in'] ?? (string) $record->dtr_date)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    if (blank($state)) {
                                        return;
                                    }
                                    $start = $get('schedule_start');
                                    $end = $get('schedule_end');
                                    if (filled($start) && filled($end)) {
                                        try {
                                            if (Carbon::parse($end)->lessThanOrEqualTo(Carbon::parse($start))) {
                                                $set('date_out', Carbon::parse($state)->addDay()->toDateString());
                                                return;
                                            }
                                        } catch (\Throwable) {}
                                    }
                                    $set('date_out', Carbon::parse($state)->toDateString());
                                }),

                            TimePicker::make('time_in')
                                ->label('Time In')
                                ->placeholder('Select Time In')
                                ->default(function (DtrChangeRequest $record): ?string {
                                    $val = $record->proposed_values['time_in'] ?? $record->original_values['time_in'] ?? null;
                                    if (blank($val)) {
                                        return null;
                                    }
                                    try {
                                        return Carbon::parse($val)->format('H:i:s');
                                    } catch (\Throwable) {
                                        return $val;
                                    }
                                })
                                ->required(),

                            DatePicker::make('date_out')
                                ->label('Date Out')
                                ->default(function (DtrChangeRequest $record): ?string {
                                    if (filled($record->proposed_values['date_out'] ?? null)) {
                                        return (string) $record->proposed_values['date_out'];
                                    }
                                    if (filled($record->original_values['date_out'] ?? null)) {
                                        return (string) $record->original_values['date_out'];
                                    }
                                    $branch = $record->branch ?: ($record->branch_id ? Branch::find($record->branch_id) : $record->employee?->branch);
                                    $schedType = $record->proposed_values['schedule_type'] ?? $record->original_values['schedule_type'] ?? null;
                                    [$start, $end] = app(DtrChangeRequestService::class)->resolveBranchScheduleTimes($branch, $schedType);
                                    $baseDate = $record->dtr_date ?: ($record->original_values['date_in'] ?? today()->toDateString());
                                    if (filled($start) && filled($end)) {
                                        try {
                                            if (Carbon::parse($end)->lessThanOrEqualTo(Carbon::parse($start))) {
                                                return Carbon::parse($baseDate)->addDay()->toDateString();
                                            }
                                        } catch (\Throwable) {}
                                    }
                                    return (string) $baseDate;
                                })
                                ->required(),

                            TimePicker::make('time_out')
                                ->label('Time Out')
                                ->placeholder('Select Time Out')
                                ->default(function (DtrChangeRequest $record): ?string {
                                    $val = $record->proposed_values['time_out'] ?? $record->original_values['time_out'] ?? null;
                                    if (blank($val)) {
                                        return null;
                                    }
                                    try {
                                        return Carbon::parse($val)->format('H:i:s');
                                    } catch (\Throwable) {
                                        return $val;
                                    }
                                })
                                ->required(),

                            Select::make('schedule_type')
                                ->label('Schedule Type')
                                ->options(function (DtrChangeRequest $record): array {
                                    $branch = $record->branch ?: ($record->branch_id ? Branch::find($record->branch_id) : $record->employee?->branch);
                                    return app(DtrChangeRequestService::class)->availableScheduleOptionsForBranch($branch, withTimes: true);
                                })
                                ->placeholder(function (DtrChangeRequest $record): string {
                                    $branch = $record->branch ?: ($record->branch_id ? Branch::find($record->branch_id) : $record->employee?->branch);
                                    $options = app(DtrChangeRequestService::class)->availableScheduleOptionsForBranch($branch, withTimes: false);
                                    return empty($options)
                                        ? "No schedule configuration is available for this employee's assigned station or branch."
                                        : 'Select Schedule Type...';
                                })
                                ->helperText(function (DtrChangeRequest $record): ?string {
                                    $branch = $record->branch ?: ($record->branch_id ? Branch::find($record->branch_id) : $record->employee?->branch);
                                    $options = app(DtrChangeRequestService::class)->availableScheduleOptionsForBranch($branch, withTimes: false);
                                    if (empty($options)) {
                                        return "No schedule configuration is available for this employee's assigned station or branch.";
                                    }
                                    return null;
                                })
                                ->default(function (DtrChangeRequest $record): ?string {
                                    $branch = $record->branch ?: ($record->branch_id ? Branch::find($record->branch_id) : $record->employee?->branch);
                                    $options = app(DtrChangeRequestService::class)->availableScheduleOptionsForBranch($branch, withTimes: false);
                                    if (empty($options)) {
                                        return null;
                                    }

                                    $existingType = $record->proposed_values['schedule_type']
                                        ?? $record->original_values['schedule_type']
                                        ?? $record->targetDtrRecord()?->schedule_type
                                        ?? null;

                                    if (filled($existingType)) {
                                        $norm = DtrChangeRequestService::normalizeScheduleType($existingType);
                                        foreach (array_keys($options) as $optKey) {
                                            if (DtrChangeRequestService::normalizeScheduleType($optKey) === $norm) {
                                                return $optKey;
                                            }
                                        }
                                    }

                                    return array_key_first($options);
                                })
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get, ?DtrChangeRequest $record) {
                                    if (! $record) {
                                        return;
                                    }
                                    $branch = $record->branch ?: ($record->branch_id ? Branch::find($record->branch_id) : $record->employee?->branch);
                                    [$start, $end] = app(DtrChangeRequestService::class)->resolveBranchScheduleTimes($branch, $state, formatForInput: true);
                                    $set('schedule_start', $start ?? '');
                                    $set('schedule_end', $end ?? '');

                                    $dateIn = $get('date_in');
                                    if (filled($dateIn) && filled($start) && filled($end)) {
                                        try {
                                            if (Carbon::parse($end)->lessThanOrEqualTo(Carbon::parse($start))) {
                                                $set('date_out', Carbon::parse($dateIn)->addDay()->toDateString());
                                            } else {
                                                $set('date_out', Carbon::parse($dateIn)->toDateString());
                                            }
                                        } catch (\Throwable) {}
                                    }
                                }),

                            TextInput::make('schedule_start')
                                ->label('Schedule Start')
                                ->placeholder('Automatically populated')
                                ->readOnly()
                                ->dehydrated(true)
                                ->default(function (DtrChangeRequest $record): ?string {
                                    $branch = $record->branch ?: ($record->branch_id ? Branch::find($record->branch_id) : $record->employee?->branch);
                                    $options = app(DtrChangeRequestService::class)->availableScheduleOptionsForBranch($branch, withTimes: false);
                                    $existingType = $record->proposed_values['schedule_type']
                                        ?? $record->original_values['schedule_type']
                                        ?? $record->targetDtrRecord()?->schedule_type
                                        ?? null;

                                    $selectedType = null;
                                    if (filled($existingType)) {
                                        $norm = DtrChangeRequestService::normalizeScheduleType($existingType);
                                        foreach (array_keys($options) as $optKey) {
                                            if (DtrChangeRequestService::normalizeScheduleType($optKey) === $norm) {
                                                $selectedType = $optKey;
                                                break;
                                            }
                                        }
                                    }
                                    $selectedType = $selectedType ?: (empty($options) ? null : array_key_first($options));

                                    [$start] = app(DtrChangeRequestService::class)->resolveBranchScheduleTimes($branch, $selectedType, formatForInput: true);
                                    return $start;
                                }),

                            TextInput::make('schedule_end')
                                ->label('Schedule End')
                                ->placeholder('Automatically populated')
                                ->readOnly()
                                ->dehydrated(true)
                                ->default(function (DtrChangeRequest $record): ?string {
                                    $branch = $record->branch ?: ($record->branch_id ? Branch::find($record->branch_id) : $record->employee?->branch);
                                    $options = app(DtrChangeRequestService::class)->availableScheduleOptionsForBranch($branch, withTimes: false);
                                    $existingType = $record->proposed_values['schedule_type']
                                        ?? $record->original_values['schedule_type']
                                        ?? $record->targetDtrRecord()?->schedule_type
                                        ?? null;

                                    $selectedType = null;
                                    if (filled($existingType)) {
                                        $norm = DtrChangeRequestService::normalizeScheduleType($existingType);
                                        foreach (array_keys($options) as $optKey) {
                                            if (DtrChangeRequestService::normalizeScheduleType($optKey) === $norm) {
                                                $selectedType = $optKey;
                                                break;
                                            }
                                        }
                                    }
                                    $selectedType = $selectedType ?: (empty($options) ? null : array_key_first($options));

                                    [, $end] = app(DtrChangeRequestService::class)->resolveBranchScheduleTimes($branch, $selectedType, formatForInput: true);
                                    return $end;
                                }),

                            TextInput::make('comment')
                                ->label('Remarks / Comment')
                                ->default(fn (DtrChangeRequest $record): ?string => $record->proposed_values['comment'] ?? $record->original_values['comment'] ?? null)
                                ->columnSpanFull(),

                            Textarea::make('reviewer_remarks')
                                ->label('Approver Remarks (Optional)')
                                ->placeholder('Add any notes or justification for this approval...')
                                ->rows(3)
                                ->maxLength(2000)
                                ->columnSpanFull(),
                        ])
                        ->extraModalFooterActions(fn (DtrChangeRequest $record): array => [
                            Action::make('rejectInsideEditModal')
                                ->label('Reject Request')
                                ->icon('heroicon-m-x-circle')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('Are you sure you want to continue?')
                                ->modalDescription('Are you sure you want to reject this change request? The workflow will stop and no changes will be applied.')
                                ->modalSubmitActionLabel('Yes, Reject Request')
                                ->schema([
                                    Textarea::make('rejection_reason_inside')
                                        ->label('Rejection Remarks / Reason')
                                        ->placeholder('Explain why this change request is being rejected...')
                                        ->rows(3)
                                        ->maxLength(2000)
                                        ->required(),
                                ])
                                ->action(function (DtrChangeRequest $record, array $data): void {
                                    $reviewer = $this->managerEmployee();
                                    $record = app(DtrChangeRequestService::class)->reject(
                                        $record,
                                        $reviewer,
                                        (string) $data['rejection_reason_inside'],
                                    );

                                    $this->notifyEmployee($record);
                                    Notification::make()->title('D.T.R change request rejected')->success()->send();
                                })
                                ->cancelParentActions(),
                        ])
                        ->action(function (DtrChangeRequest $record, array $data): void {
                            $reviewer = $this->managerEmployee();
                            // 1. Automatically save corrections
                            app(DtrChangeRequestService::class)->saveCorrections($record, $reviewer, $data);

                            // 2. Automatically approve
                            $record = app(DtrChangeRequestService::class)->approve(
                                $record,
                                $reviewer,
                                $data['reviewer_remarks'] ?? null,
                                $data,
                            );

                            $this->notifyEmployee($record);
                            Notification::make()
                                ->title('D.T.R corrections saved & approved')
                                ->body('The change request has been processed successfully.')
                                ->success()
                                ->send();
                        }),

                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->visible(fn (DtrChangeRequest $record): bool => $record->status === DtrChangeRequest::STATUS_PENDING
                            && $record->canBeReviewedBy(auth()->user())
                            && ! ($record->is_first_level && $record->is_editable_type))
                        ->requiresConfirmation()
                        ->modalWidth('2xl')
                        ->modalHeading('Are you sure you want to continue?')
                        ->modalDescription(function (DtrChangeRequest $record): string {
                            $isFinal = (int) $record->current_level >= (int) $record->total_levels;

                            if ($record->is_overtime_type) {
                                return match ($record->overtime_request_scope) {
                                    DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY => 'Employee requested Overtime Only. Only regular after-schedule overtime will be approved.',
                                    DtrChangeRequest::OT_SCOPE_EARLY_ONLY => 'Employee requested Early Clock In Only. Only early clock in overtime will be approved.',
                                    default => 'Employee requested Both Early Clock In & Overtime. You can approve both or choose which component to approve below.',
                                };
                            }

                            if ($record->request_type === DtrChangeRequest::TYPE_DELETE_TIME_ENTRY) {
                                if ($isFinal) {
                                    return 'Approving will permanently delete the selected DTR entry from official records. This action cannot be undone.';
                                }

                                return 'Approving will forward this deletion request to the next approval level.';
                            }

                            if ($record->request_type === DtrChangeRequest::TYPE_MISSING_DTR_ENTRIES) {
                                $count = count($record->proposed_values['entries'] ?? []);
                                if ($isFinal) {
                                    return "Approving will officially create {$count} missing DTR record(s) and apply them to payroll calculations.";
                                }

                                return "Approving will forward this missing DTR request ({$count} entries) to the next approval level.";
                            }

                            if ($isFinal) {
                                return 'Approving will officially finalize the attendance record and apply changes to payroll calculations.';
                            }

                            return 'Approving will forward this request to the next approval level with any saved corrections.';
                        })
                        ->modalSubmitActionLabel('Yes, Approve Request')
                        ->schema(function (DtrChangeRequest $record): array {
                            if (! $record->is_overtime_type) {
                                return [
                                    Textarea::make('reviewer_remarks')
                                        ->label('Approver Remarks (Optional)')
                                        ->rows(4)
                                        ->maxLength(2000),
                                ];
                            }

                            $targetRecord = $record->targetDtrRecord();
                            $earlyCalculated = max(0, (int) ($record->original_values['early_clock_in'] ?? $targetRecord?->early_clock_in ?? 0));
                            $otCalculated = max(0, (int) ($record->original_values['overtime'] ?? $targetRecord?->overtime ?? 0));
                            $scope = $record->overtime_request_scope;

                            $components = [
                                ViewField::make('overtime_approval_info')
                                    ->view('filament.employee.pages.partials.dtr-change-request-overtime-approval-info')
                                    ->viewData([
                                        'record' => $record,
                                        'scope' => $scope,
                                        'earlyCalculated' => $earlyCalculated,
                                        'otCalculated' => $otCalculated,
                                    ])
                                    ->columnSpanFull(),
                            ];

                            if ($scope === DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY) {
                                $components[] = TextInput::make('credited_overtime_minutes')
                                    ->label('Credited Overtime (Minutes)')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(30)
                                    ->maxValue($otCalculated)
                                    ->default($otCalculated)
                                    ->required()
                                    ->helperText(sprintf('Approve regular after-schedule overtime (30 to %d mins). Early clock in is excluded from this request.', $otCalculated));
                            } elseif ($scope === DtrChangeRequest::OT_SCOPE_EARLY_ONLY) {
                                $components[] = TextInput::make('credited_early_minutes')
                                    ->label('Credited Early Clock In (Minutes)')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(30)
                                    ->maxValue($earlyCalculated)
                                    ->default($earlyCalculated)
                                    ->required()
                                    ->helperText(sprintf('Approve early clock in overtime (30 to %d mins). Regular overtime is excluded from this request.', $earlyCalculated));
                            } else {
                                $components[] = Select::make('approved_overtime_scope')
                                    ->label('Select What to Approve')
                                    ->options([
                                        DtrChangeRequest::OT_SCOPE_BOTH => sprintf('Both Early Clock In (%d mins) & Overtime (%d mins)', $earlyCalculated, $otCalculated),
                                        DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY => sprintf('Overtime Only (%d mins)', $otCalculated),
                                        DtrChangeRequest::OT_SCOPE_EARLY_ONLY => sprintf('Early Clock In Only (%d mins)', $earlyCalculated),
                                    ])
                                    ->default(DtrChangeRequest::OT_SCOPE_BOTH)
                                    ->required()
                                    ->live()
                                    ->helperText('Choose whether to approve both overtime components or only one of them.');

                                $components[] = TextInput::make('credited_early_minutes')
                                    ->label('Credited Early Clock In (Minutes)')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(30)
                                    ->maxValue($earlyCalculated)
                                    ->default($earlyCalculated)
                                    ->visible(fn ($get): bool => in_array($get('approved_overtime_scope') ?? DtrChangeRequest::OT_SCOPE_BOTH, [
                                        DtrChangeRequest::OT_SCOPE_BOTH,
                                        DtrChangeRequest::OT_SCOPE_EARLY_ONLY,
                                    ], true))
                                    ->required(fn ($get): bool => in_array($get('approved_overtime_scope') ?? DtrChangeRequest::OT_SCOPE_BOTH, [
                                        DtrChangeRequest::OT_SCOPE_BOTH,
                                        DtrChangeRequest::OT_SCOPE_EARLY_ONLY,
                                    ], true))
                                    ->helperText(sprintf('Enter credited early minutes from 30 to %d.', $earlyCalculated));

                                $components[] = TextInput::make('credited_overtime_minutes')
                                    ->label('Credited Regular Overtime (Minutes)')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(30)
                                    ->maxValue($otCalculated)
                                    ->default($otCalculated)
                                    ->visible(fn ($get): bool => in_array($get('approved_overtime_scope') ?? DtrChangeRequest::OT_SCOPE_BOTH, [
                                        DtrChangeRequest::OT_SCOPE_BOTH,
                                        DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY,
                                    ], true))
                                    ->required(fn ($get): bool => in_array($get('approved_overtime_scope') ?? DtrChangeRequest::OT_SCOPE_BOTH, [
                                        DtrChangeRequest::OT_SCOPE_BOTH,
                                        DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY,
                                    ], true))
                                    ->helperText(sprintf('Enter credited overtime minutes from 30 to %d.', $otCalculated));
                            }

                            $components[] = Textarea::make('reviewer_remarks')
                                ->label('Approver Remarks (Optional)')
                                ->rows(3)
                                ->maxLength(2000)
                                ->columnSpanFull();

                            return $components;
                        })
                        ->action(function (DtrChangeRequest $record, array $data): void {
                            $reviewer = $this->managerEmployee();
                            try {
                                $record = app(DtrChangeRequestService::class)->approve(
                                    $record,
                                    $reviewer,
                                    $data['reviewer_remarks'] ?? null,
                                    $data,
                                );

                                $this->notifyEmployee($record);
                                Notification::make()->title('D.T.R change request approved')->success()->send();
                            } catch (\DomainException $e) {
                                Notification::make()
                                    ->title('Unable to Approve Request')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            } catch (\Illuminate\Validation\ValidationException $e) {
                                $msg = collect($e->errors())->flatten()->first() ?: $e->getMessage();
                                Notification::make()
                                    ->title('Unable to Approve Request')
                                    ->body($msg)
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->visible(fn (DtrChangeRequest $record): bool => $record->status === DtrChangeRequest::STATUS_PENDING
                            && $record->canBeReviewedBy(auth()->user())
                            && ! ($record->is_first_level && $record->is_editable_type))
                        ->requiresConfirmation()
                        ->modalHeading('Are you sure you want to continue?')
                        ->modalDescription('Explain why the change request cannot be approved. Workflow will stop and no changes will be applied.')
                        ->modalSubmitActionLabel('Yes, Reject Request')
                        ->schema([
                            Textarea::make('reviewer_remarks')
                                ->label('Rejection Remarks / Reason')
                                ->rows(4)
                                ->maxLength(2000)
                                ->required(),
                        ])
                        ->action(function (DtrChangeRequest $record, array $data): void {
                            $reviewer = $this->managerEmployee();
                            $record = app(DtrChangeRequestService::class)->reject(
                                $record,
                                $reviewer,
                                (string) $data['reviewer_remarks'],
                            );

                            $this->notifyEmployee($record);
                            Notification::make()->title('D.T.R change request rejected')->success()->send();
                        }),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions'),
            ])
            ->poll('15s')
            ->striped()
            ->emptyStateHeading('No Pending Change Requests')
            ->emptyStateDescription('There are currently no attendance change requests awaiting your approval.')
            ->emptyStateActions([
                Action::make('history')
                    ->label('View Change Request History')
                    ->icon(Heroicon::Clock)
                    ->color('gray')
                    ->url(fn (): string => StationChangeRequestHistory::getUrl()),
            ])
            ->defaultPaginationPageOption(10);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeRequestHistory')
                ->label('Change Request History')
                ->icon(Heroicon::Clock)
                ->color('gray')
                ->url(fn (): string => StationChangeRequestHistory::getUrl()),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    protected function managerEmployee(): Employee
    {
        $employee = auth()->user()?->employee;
        abort_unless($employee instanceof Employee, 403);

        return $employee;
    }

    protected function notifyEmployee(DtrChangeRequest $request): void
    {
        $user = $request->employee?->user;
        if (! $user) {
            return;
        }

        Notification::make()
            ->title('D.T.R change request '.$request->status)
            ->body($request->reviewer_remarks ?: 'Open Change Requests to view the updated status.')
            ->color($request->status === DtrChangeRequest::STATUS_APPROVED ? 'success' : 'danger')
            ->sendToDatabase($user);
    }
}
