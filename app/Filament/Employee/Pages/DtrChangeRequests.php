<?php

namespace App\Filament\Employee\Pages;

use App\Models\DtrChangeRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\DtrChangeRequestService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DtrChangeRequests extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.employee.pages.dtr-change-requests';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'dtr-change-requests';

    protected static ?string $title = 'D.T.R Change Requests';

    public ?string $periodId = null;

    public static function canAccess(): bool
    {
        return parent::canAccess() && filled(auth()->user()?->employee);
    }

    public function mount(): void
    {
        $this->periodId = request()->query('periodId');
        $this->markReviewedRequestsAsSeen();
    }

    public function markReviewedRequestsAsSeen(): void
    {
        DtrChangeRequest::query()
            ->where('employee_id', $this->employee()->id)
            ->reviewed()
            ->unseenByEmployee()
            ->update(['employee_seen_at' => now()]);
    }

    public function table(Table $table): Table
    {
        $resolvedPeriodId = filled($this->periodId)
            ? (PayrollPeriod::resolvePublicId($this->periodId) ?: (int) $this->periodId)
            : null;

        return $table
            ->query(fn (): Builder => DtrChangeRequest::query()
                ->where('employee_id', $this->employee()->id)
                ->when(filled($resolvedPeriodId), fn ($q) => $q->where('payroll_period_id', $resolvedPeriodId))
                ->with(['payrollPeriod', 'reviewedByEmployee.user', 'assignedEmployee'])
                ->latest('created_at'))
            ->heading('My D.T.R Change Requests')
            ->description('Submitted requests are reviewed sequentially through the configured approval hierarchy.')
            ->columns([
                TextColumn::make('index')->label('#')->rowIndex(),

                TextColumn::make('created_at')
                    ->label('Date Requested')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

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
                    ->sortable(),

                TextColumn::make('payroll_period_title_snapshot')
                    ->label('Payroll Period')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('request_type_label')
                    ->label('Request Type')
                    ->badge()
                    ->color('info')
                    ->wrap(),

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
                        ? "Level {$record->current_level} of {$record->total_levels} (".($record->current_step_label ?? 'Review').')'
                        : ($record->status === DtrChangeRequest::STATUS_APPROVED ? 'Completed (All levels approved)' : 'Finalized'))
                    ->wrap(),

                TextColumn::make('current_approver_name')
                    ->label('Current Approver')
                    ->wrap(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon(Heroicon::Eye)
                        ->modalHeading('D.T.R Change Request Details')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalWidth('2xl')
                        ->modalContent(fn (DtrChangeRequest $record) => view('filament.employee.pages.partials.dtr-change-request-details-modal', [
                            'record' => $record,
                        ])),

                    Action::make('cancelRequest')
                        ->label('Cancel Request')
                        ->icon(Heroicon::XCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel D.T.R Change Request?')
                        ->modalDescription('Are you sure you want to cancel this change request? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, Cancel Request')
                        ->visible(fn (DtrChangeRequest $record): bool => $record->status === DtrChangeRequest::STATUS_PENDING)
                        ->action(function (DtrChangeRequest $record): void {
                            app(DtrChangeRequestService::class)->cancel($record, $this->employee());

                            Notification::make()
                                ->title('Change Request Cancelled')
                                ->body('The change request has been cancelled.')
                                ->success()
                                ->send();
                        }),
                ])
                ->icon(Heroicon::EllipsisVertical)
                ->tooltip('Actions'),
            ])
            ->poll('15s')
            ->striped()
            ->defaultPaginationPageOption(10);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('missingDtrEntries')
                ->label('Missing DTR Entries')
                ->icon(Heroicon::DocumentPlus)
                ->color('primary')
                ->modalHeading('Submit Missing D.T.R Entries')
                ->modalDescription('Submit one or more missing attendance records for an unlocked payroll period.')
                ->modalSubmitActionLabel('Submit Request')
                ->modalCancelActionLabel('Cancel')
                ->modalWidth('4xl')
                ->form([
                    Section::make('Payroll Period')
                        ->description('Select the unlocked payroll period to which the missing attendance records belong.')
                        ->schema([
                            Select::make('payroll_period_id')
                                ->label('Payroll Period')
                                ->placeholder('Select unlocked payroll period...')
                                ->options(function (): array {
                                    return PayrollPeriod::query()
                                        ->where('is_locked', false)
                                        ->newestFirst()
                                        ->get()
                                        ->pluck('title', 'id')
                                        ->all();
                                })
                                ->default(function () {
                                    if (filled($this->periodId)) {
                                        $res = PayrollPeriod::resolvePublicId($this->periodId) ?: (int) $this->periodId;
                                        $p = PayrollPeriod::find($res);
                                        if ($p && ! $p->is_locked) {
                                            return $p->id;
                                        }
                                    }
                                    return PayrollPeriod::query()->where('is_locked', false)->newestFirst()->value('id');
                                })
                                ->required()
                                ->live(),
                        ]),

                    Section::make('Missing Attendance Entries')
                        ->description('Add all missing attendance dates and times. You can add, review, or remove entries before submitting.')
                        ->schema([
                            Repeater::make('entries')
                                ->label('Attendance Records')
                                ->addActionLabel('Add DTR Entry')
                                ->defaultItems(1)
                                ->minItems(1)
                                ->collapsible()
                                ->itemLabel(fn (array $state): string => filled($state['date_in'] ?? null)
                                    ? 'D.T.R Entry: ' . Carbon::parse($state['date_in'])->format('M d, Y') . (filled($state['schedule_type'] ?? null) ? ' [' . \App\Services\DtrChangeRequestService::scheduleTypeLabel($state['schedule_type']) . ']' : '') . ' (' . ($state['time_in'] ?? '-') . ' - ' . ($state['time_out'] ?? '-') . ')'
                                    : 'New Missing Attendance Entry')
                                ->schema([
                                    DatePicker::make('date_in')
                                        ->label('Date In')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            if (blank($get('date_out'))) {
                                                $set('date_out', $state);
                                            }
                                        }),

                                    TimePicker::make('time_in')
                                        ->label('Time In')
                                        ->required(),

                                    DatePicker::make('date_out')
                                        ->label('Date Out')
                                        ->required()
                                        ->default(fn (Get $get): ?string => $get('date_in')),

                                    TimePicker::make('time_out')
                                        ->label('Time Out')
                                        ->required(),

                                    Select::make('schedule_type')
                                        ->label('Schedule Type')
                                        ->options([
                                            'Regular' => 'Regular',
                                            'Shift1' => 'Shift 1',
                                            'Shift2' => 'Shift 2',
                                            'Shift3' => 'Shift 3',
                                            'Broken1' => 'Broken Shift 1',
                                            'Broken2' => 'Broken Shift 2',
                                            'Broken3' => 'Broken Shift 3',
                                            'Saturday' => 'Saturday',
                                            'Overtime' => 'Overtime Only',
                                        ])
                                        ->default('Regular')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            $employee = auth()->user()?->employee;
                                            $branch = $employee?->branch ?? ($employee?->branch_id ? Branch::find($employee->branch_id) : null);
                                            [$start, $end] = app(DtrChangeRequestService::class)->resolveBranchScheduleTimes($branch, $state, formatForInput: true);
                                            if (filled($start)) {
                                                $set('schedule_start', $start);
                                            }
                                            if (filled($end)) {
                                                $set('schedule_end', $end);
                                            }
                                        }),

                                    TextInput::make('schedule_start')
                                        ->label('Schedule Start (Optional)')
                                        ->placeholder('e.g. 08:00 AM')
                                        ->default(function () {
                                            $employee = auth()->user()?->employee;
                                            $branch = $employee?->branch ?? ($employee?->branch_id ? Branch::find($employee->branch_id) : null);
                                            [$start] = app(DtrChangeRequestService::class)->resolveBranchScheduleTimes($branch, 'Regular', formatForInput: true);
                                            return $start;
                                        }),

                                    TextInput::make('schedule_end')
                                        ->label('Schedule End (Optional)')
                                        ->placeholder('e.g. 05:00 PM')
                                        ->default(function () {
                                            $employee = auth()->user()?->employee;
                                            $branch = $employee?->branch ?? ($employee?->branch_id ? Branch::find($employee->branch_id) : null);
                                            [, $end] = app(DtrChangeRequestService::class)->resolveBranchScheduleTimes($branch, 'Regular', formatForInput: true);
                                            return $end;
                                        }),

                                    TextInput::make('comment')
                                        ->label('Remarks / Comment (Optional)')
                                        ->placeholder('Optional notes for this entry...')
                                        ->maxLength(500)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),

                    Section::make('Explanation & Supporting Document')
                        ->schema([
                            Textarea::make('explanation')
                                ->label('Explanation / Justification')
                                ->placeholder('Explain why these attendance entries were missed (e.g. forgot to punch during field assignment, biometric lag)...')
                                ->required()
                                ->rows(3)
                                ->maxLength(2000)
                                ->columnSpanFull(),

                            FileUpload::make('attachment_path')
                                ->label('Supporting Document (Image Attachment)')
                                ->required()
                                ->disk('local')
                                ->directory('dtr-change-requests')
                                ->acceptedFileTypes([
                                    'image/png',
                                    'image/jpeg',
                                    'image/jpg',
                                ])
                                ->maxSize(2560)
                                ->helperText('Required. Upload supporting document (proof / log sheet). Accepted: PNG, JPG, JPEG (Max 2.5MB).')
                                ->storeFileNamesIn('attachment_original_name')
                                ->fetchFileInformation(false)
                                ->columnSpanFull(),
                        ]),
                ])
                ->action(function (array $data): void {
                    $employee = $this->employee();
                    try {
                        app(DtrChangeRequestService::class)->submitMissingDtrEntries($employee, $data);

                        Notification::make()
                            ->title('Missing D.T.R Entries Request Submitted')
                            ->body('Your request with ' . count($data['entries'] ?? []) . ' missing attendance entries has been submitted for approval.')
                            ->success()
                            ->send();
                    } catch (ValidationException $e) {
                        $message = collect($e->errors())->flatten()->first() ?: $e->getMessage();
                        Notification::make()
                            ->title('Unable to Submit Request')
                            ->body($message)
                            ->danger()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Submission Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(Dtr::getUrl()),
        ];
    }

    protected function periodBoundary(mixed $periodId, string $column): ?string
    {
        $value = filled($periodId) ? PayrollPeriod::query()->whereKey((int) $periodId)->value($column) : null;

        return filled($value) ? date('Y-m-d', strtotime((string) $value)) : null;
    }

    protected function employee(): Employee
    {
        return auth()->user()->employee()->with('branch')->firstOrFail();
    }
}
