<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\DtrChangeRequest;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\DtrChangeRequestService;
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
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Validation\ValidationException;
use Override;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DtrChangeRequests extends Page implements HasTable
{
    use HasTabs;
    use InteractsWithTable;

    protected string $view = 'filament.pages.dtr-change-requests';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'dtr-change-requests';

    protected static ?string $title = 'D.T.R Change Requests';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return DtrChangeRequest::isHrReviewer($user) || Dtr::canAccess();
    }

    public function mount(): void
    {
        if (! static::canAccess()) {
            throw new HttpException(403, 'Unauthorized access to HR D.T.R Change Requests.');
        }

        $this->loadDefaultActiveTab();
    }

    #[Override]
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'pending_hr';
    }

    public function getTabs(): array
    {
        return [
            'pending_hr' => Tab::make('Pending HR Final Approval')
                ->badge(fn (): ?int => $this->getTabCount('pending_hr'))
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->pendingForHr()),

            'approved' => Tab::make('Approved')
                ->badge(fn (): ?int => $this->getTabCount(DtrChangeRequest::STATUS_APPROVED))
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', DtrChangeRequest::STATUS_APPROVED)),

            'rejected' => Tab::make('Rejected')
                ->badge(fn (): ?int => $this->getTabCount(DtrChangeRequest::STATUS_REJECTED))
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', DtrChangeRequest::STATUS_REJECTED)),

            'cancelled' => Tab::make('Cancelled')
                ->badge(fn (): ?int => $this->getTabCount(DtrChangeRequest::STATUS_CANCELLED))
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', DtrChangeRequest::STATUS_CANCELLED)),

            'awaiting_approvers' => Tab::make('Awaiting Workflow Approvers')
                ->badge(fn (): ?int => $this->getTabCount('awaiting_approvers'))
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('status', DtrChangeRequest::STATUS_PENDING)
                    ->whereNotNull('assigned_employee_id')),

            'all' => Tab::make('All')
                ->badge(fn (): ?int => $this->getTabCount(null)),
        ];
    }

    protected function getTabCount(?string $tab): ?int
    {
        if (! SchemaFacade::hasTable('dtr_change_requests')) {
            return null;
        }

        $query = DtrChangeRequest::query();

        if ($tab === 'pending_hr') {
            $query->pendingForHr();
        } elseif ($tab === 'awaiting_approvers') {
            $query->where('status', DtrChangeRequest::STATUS_PENDING)
                ->whereNotNull('assigned_employee_id');
        } elseif (filled($tab)) {
            $query->where('status', $tab);
        }

        $count = $query->count();

        return $count > 0 ? $count : null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getTabsContentComponent(),
            EmbeddedTable::make(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToDtr')
                ->label('Back to D.T.R')
                ->icon(Heroicon::ArrowLeft)
                ->color('gray')
                ->url(fn (): string => Dtr::getUrl()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->modifyQueryWithActiveTab(
                DtrChangeRequest::query()
                    ->with(['employee', 'branch', 'payrollPeriod', 'reviewedByEmployee.user', 'assignedEmployee'])
                    ->latest('created_at')
            ))
            ->heading('HR Final Approval – D.T.R Change Requests')
            ->description('Review D.T.R change requests that have completed their employee-level approval workflow and are waiting for HR final approval.')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

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

                TextColumn::make('payroll_period_title_snapshot')
                    ->label('Payroll Period')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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

                        return filled($record->dtr_date ?: $record->date_from)
                            ? Carbon::parse($record->dtr_date ?: $record->date_from)->format('M d, Y')
                            : '-';
                    })
                    ->description(fn (DtrChangeRequest $record): ?string => $record->created_at?->format('M d, h:i A'))
                    ->sortable(),

                TextColumn::make('request_type_label')
                    ->label('Request Type')
                    ->badge()
                    ->icon(fn (DtrChangeRequest $record): string => $record->request_type_icon)
                    ->color(fn (DtrChangeRequest $record): string => $record->request_type_color)
                    ->description(fn (DtrChangeRequest $record): ?string => $record->overtime_summary_description),

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
                    ->label('Approval Stage')
                    ->formatStateUsing(function (DtrChangeRequest $record): string {
                        if ($record->status === DtrChangeRequest::STATUS_PENDING) {
                            if ($record->isCurrentStepHr()) {
                                return "L{$record->current_level}/{$record->total_levels}: HR Final Approval";
                            }

                            return "L{$record->current_level}/{$record->total_levels}: " . ($record->current_step_label ?? 'Review');
                        }

                        return $record->status === DtrChangeRequest::STATUS_APPROVED ? 'Completed' : 'Finalized';
                    }),

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
                    ->options(fn (): array => Branch::query()->orderBy('branch_name')->pluck('branch_name', 'id')->all())
                    ->searchable(),

                SelectFilter::make('payroll_period_id')
                    ->label('Payroll Period')
                    ->options(fn (): array => PayrollPeriod::query()->newestFirst()->pluck('title', 'id')->all())
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
                        ->visible(fn (DtrChangeRequest $record): bool => $record->isPendingHrApproval()
                            && $record->is_editable_type
                            && $record->canBeEditedBy(auth()->user()))
                        ->modalWidth('2xl')
                        ->modalHeading('Edit & Review D.T.R Information')
                        ->modalDescription('Correct the time punches and schedule. Clicking Approve will automatically save your changes and complete HR final approval.')
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
                                        } catch (\Throwable) {
                                        }
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
                                        } catch (\Throwable) {
                                        }
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
                                        } catch (\Throwable) {
                                        }
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
                                ->label('HR Final Approval Remarks (Optional)')
                                ->placeholder('Add any notes or justification for this final approval...')
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
                                    $reviewer = $this->hrReviewer();
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
                            $reviewer = $this->hrReviewer();
                            app(DtrChangeRequestService::class)->saveCorrections($record, $reviewer, $data);

                            $record = app(DtrChangeRequestService::class)->approve(
                                $record,
                                $reviewer,
                                $data['reviewer_remarks'] ?? null,
                                $data,
                            );

                            $this->notifyEmployee($record);
                            Notification::make()
                                ->title('D.T.R corrections saved & approved by HR')
                                ->body('The change request has been finalized and applied to D.T.R records.')
                                ->success()
                                ->send();
                        }),

                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->visible(fn (DtrChangeRequest $record): bool => $record->isPendingHrApproval()
                            && $record->canBeReviewedBy(auth()->user())
                            && (! $record->is_editable_type || (filled($record->proposed_values['time_in'] ?? null) && filled($record->proposed_values['time_out'] ?? null))))
                        ->requiresConfirmation()
                        ->modalWidth('2xl')
                        ->modalHeading('Confirm HR Final Approval')
                        ->modalDescription(function (DtrChangeRequest $record): string {
                            if ($record->is_overtime_type) {
                                return match ($record->overtime_request_scope) {
                                    DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY => 'Employee requested Overtime Only. Approving will finalize regular after-schedule overtime.',
                                    DtrChangeRequest::OT_SCOPE_EARLY_ONLY => 'Employee requested Early Clock In Only. Approving will finalize early clock in overtime.',
                                    default => 'Employee requested Both Early Clock In & Overtime. Confirm the final overtime scope and credited minutes below.',
                                };
                            }

                            if ($record->request_type === DtrChangeRequest::TYPE_DELETE_TIME_ENTRY) {
                                return 'Approving will permanently delete the selected DTR entry from official records. This action cannot be undone.';
                            }

                            if ($record->request_type === DtrChangeRequest::TYPE_MISSING_DTR_ENTRIES) {
                                $count = count($record->proposed_values['entries'] ?? []);

                                return "Approving will officially create {$count} missing DTR record(s) and apply them to payroll calculations.";
                            }

                            return 'Approving will officially finalize the attendance record and apply changes to payroll calculations.';
                        })
                        ->modalSubmitActionLabel('Yes, Final Approve')
                        ->schema(function (DtrChangeRequest $record): array {
                            if (! $record->is_overtime_type) {
                                return [
                                    Textarea::make('reviewer_remarks')
                                        ->label('HR Final Approval Remarks (Optional)')
                                        ->rows(4)
                                        ->maxLength(2000),
                                ];
                            }

                            $targetRecord = $record->targetDtrRecord();
                            $earlyCalculated = max(0, (int) ($record->original_values['early_clock_in'] ?? $targetRecord?->early_clock_in ?? 0));
                            $otCalculated = max(0, (int) ($record->original_values['overtime'] ?? $targetRecord?->overtime ?? 0));
                            $scope = $record->overtime_request_scope;

                            $defaultApprovedScope = $record->proposed_values['approved_overtime_scope'] ?? DtrChangeRequest::OT_SCOPE_BOTH;
                            $defaultOtMinutes = (int) ($record->proposed_values['credited_overtime'] ?? $otCalculated) ?: $otCalculated;
                            $defaultEarlyMinutes = (int) ($record->proposed_values['credited_early_clock_in'] ?? $earlyCalculated) ?: $earlyCalculated;

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
                                    ->default($defaultOtMinutes)
                                    ->required()
                                    ->helperText(sprintf('Approve regular after-schedule overtime (30 to %d mins). Early clock in is excluded from this request.', $otCalculated));
                            } elseif ($scope === DtrChangeRequest::OT_SCOPE_EARLY_ONLY) {
                                $components[] = TextInput::make('credited_early_minutes')
                                    ->label('Credited Early Clock In (Minutes)')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(30)
                                    ->maxValue($earlyCalculated)
                                    ->default($defaultEarlyMinutes)
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
                                    ->default($defaultApprovedScope)
                                    ->required()
                                    ->live()
                                    ->helperText('Choose whether to approve both overtime components or only one of them.');

                                $components[] = TextInput::make('credited_early_minutes')
                                    ->label('Credited Early Clock In (Minutes)')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(30)
                                    ->maxValue($earlyCalculated)
                                    ->default($defaultEarlyMinutes)
                                    ->visible(fn ($get): bool => in_array($get('approved_overtime_scope') ?? $defaultApprovedScope, [
                                        DtrChangeRequest::OT_SCOPE_BOTH,
                                        DtrChangeRequest::OT_SCOPE_EARLY_ONLY,
                                    ], true))
                                    ->required(fn ($get): bool => in_array($get('approved_overtime_scope') ?? $defaultApprovedScope, [
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
                                    ->default($defaultOtMinutes)
                                    ->visible(fn ($get): bool => in_array($get('approved_overtime_scope') ?? $defaultApprovedScope, [
                                        DtrChangeRequest::OT_SCOPE_BOTH,
                                        DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY,
                                    ], true))
                                    ->required(fn ($get): bool => in_array($get('approved_overtime_scope') ?? $defaultApprovedScope, [
                                        DtrChangeRequest::OT_SCOPE_BOTH,
                                        DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY,
                                    ], true))
                                    ->helperText(sprintf('Enter credited overtime minutes from 30 to %d.', $otCalculated));
                            }

                            $components[] = Textarea::make('reviewer_remarks')
                                ->label('HR Final Approval Remarks (Optional)')
                                ->rows(3)
                                ->maxLength(2000)
                                ->columnSpanFull();

                            return $components;
                        })
                        ->action(function (DtrChangeRequest $record, array $data): void {
                            $reviewer = $this->hrReviewer();
                            try {
                                $record = app(DtrChangeRequestService::class)->approve(
                                    $record,
                                    $reviewer,
                                    $data['reviewer_remarks'] ?? null,
                                    $data,
                                );

                                $this->notifyEmployee($record);
                                Notification::make()
                                    ->title('D.T.R change request finalized & approved')
                                    ->success()
                                    ->send();
                            } catch (\DomainException $e) {
                                Notification::make()
                                    ->title('Unable to Approve Request')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            } catch (ValidationException $e) {
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
                        ->visible(fn (DtrChangeRequest $record): bool => $record->isPendingHrApproval()
                            && $record->canBeReviewedBy(auth()->user()))
                        ->requiresConfirmation()
                        ->modalHeading('Reject D.T.R Change Request')
                        ->modalDescription('Explain why the change request cannot be approved by HR. The workflow will complete as Rejected and no D.T.R changes will be applied.')
                        ->modalSubmitActionLabel('Yes, Reject Request')
                        ->schema([
                            Textarea::make('reviewer_remarks')
                                ->label('Rejection Remarks / Reason')
                                ->rows(4)
                                ->maxLength(2000)
                                ->required(),
                        ])
                        ->action(function (DtrChangeRequest $record, array $data): void {
                            $reviewer = $this->hrReviewer();
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
            ->emptyStateHeading('No Change Requests Found')
            ->emptyStateDescription('There are currently no D.T.R change requests matching this queue.')
            ->defaultPaginationPageOption(10);
    }

    protected function hrReviewer(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User && DtrChangeRequest::isHrReviewer($user), 403);

        return $user;
    }

    protected function notifyEmployee(DtrChangeRequest $request): void
    {
        $user = $request->employee?->user;
        if (! $user) {
            return;
        }

        Notification::make()
            ->title('D.T.R change request ' . $request->status)
            ->body($request->reviewer_remarks ?: 'Open Change Requests to view the updated status.')
            ->color($request->status === DtrChangeRequest::STATUS_APPROVED ? 'success' : 'danger')
            ->sendToDatabase($user);
    }
}
