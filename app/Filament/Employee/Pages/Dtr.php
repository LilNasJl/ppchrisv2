<?php

namespace App\Filament\Employee\Pages;

use App\Models\Dtr as DtrModel;
use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
use App\Services\DtrAttendanceUnitService;
use App\Services\DtrChangeRequestService;
use App\Services\DtrDayPartService;
use App\Services\DtrRecordService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Override;

class Dtr extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'filament.employee.pages.dtr';

    protected static ?string $slug = 'dtr';

    protected static ?string $title = 'D.T.R';

    protected static ?string $navigationLabel = 'D.T.R';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clock;

    protected static string|\UnitEnum|null $navigationGroup = 'My Workspace';

    protected static ?int $navigationSort = 2;

    public ?string $period_id = null;

    public static function canAccess(): bool
    {
        return parent::canAccess() && filled(auth()->user()?->employee);
    }

    public function mount(): void
    {
        $requestedPeriodId = PayrollPeriod::resolvePublicId(request()->query('periodId'))
            ?: (int) request()->query('period_id');

        $latestPeriodId = PayrollPeriod::query()
            ->newestFirst()
            ->value('id');

        $periodId = $requestedPeriodId && PayrollPeriod::query()->whereKey($requestedPeriodId)->exists()
            ? $requestedPeriodId
            : $latestPeriodId;

        $this->period_id = filled($periodId) ? (string) $periodId : null;

        $this->form->fill([
            'period_id' => $this->period_id,
        ]);
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeRequests')
                ->label('Change Requests')
                ->icon(Heroicon::DocumentText)
                ->badge(fn (): ?string => $this->unseenReviewedRequestBadge())
                ->badgeColor('danger')
                ->badgeTooltip('Reviewed change requests you have not opened yet')
                ->url(fn (): string => DtrChangeRequests::getUrl([
                    'periodId' => $this->selectedPeriod?->publicKey(),
                ])),

            Action::make('printDtr')
                ->label('Print / PDF D.T.R')
                ->icon(Heroicon::Printer)
                ->url(fn (): string => $this->getPrintUrl())
                ->openUrlInNewTab()
                ->disabled(fn (): bool => ! $this->canPrintDtr()),
        ];
    }

    protected function unseenReviewedRequestBadge(): ?string
    {
        $employeeId = auth()->user()?->employee?->id;

        if (! $employeeId) {
            return null;
        }

        $count = DtrChangeRequest::query()
            ->where('employee_id', $employeeId)
            ->reviewed()
            ->unseenByEmployee()
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('period_id')
                ->label('Payroll Period')
                ->options(fn (): array => $this->payrollPeriodOptions())
                ->default(fn (): ?string => $this->period_id)
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (?string $state): void {
                    $this->period_id = filled($state) ? (string) $state : null;
                    unset($this->selectedPeriod, $this->overview);
                    $this->resetTable();
                })
                ->placeholder('No payroll period available'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->dtrQuery()
                ->with(['holiday', 'payrollPeriod'])
                ->orderByDesc('date_in')
                ->orderByDesc('time_in')
                ->orderByDesc('id'))
            ->heading(fn (): string => $this->selectedPeriod?->title ?: 'D.T.R Entries')
            ->description('Read-only attendance records for the selected payroll period.')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('attendance_status')
                    ->label('Status')
                    ->getStateUsing(fn (DtrModel $record): string => $this->attendanceStatus($record))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Absent' => 'danger',
                        'Leave' => 'info',
                        'For Approval' => 'warning',
                        'Overtime' => 'warning',
                        default => 'success',
                    })
                    ->alignCenter(),

                TextColumn::make('date_in')
                    ->label('Date In')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('time_in')
                    ->label('In')
                    ->time('h:i A')
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono tabular-nums']),

                TextColumn::make('date_out')
                    ->label('Date Out')
                    ->date('M d, Y')
                    ->placeholder('-'),

                TextColumn::make('time_out')
                    ->label('Out')
                    ->time('h:i A')
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono tabular-nums']),

                TextColumn::make('schedule_start')
                    ->label('Sched. Start')
                    ->time('h:i A')
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono tabular-nums'])
                    ->toggleable(),

                TextColumn::make('schedule_end')
                    ->label('Sched. End')
                    ->time('h:i A')
                    ->placeholder('-')
                    ->extraAttributes(['class' => 'font-mono tabular-nums'])
                    ->toggleable(),

                TextColumn::make('schedule_type')
                    ->label('Sched. Type')
                    ->badge()
                    ->placeholder('-')
                    ->formatStateUsing(fn ($state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-')
                    ->color(fn (?string $state): string => match (str($state ?? '')->lower()->toString()) {
                        'regular' => 'gray',
                        'saturday', 'sunday', 'rest_day' => 'info',
                        'leave' => 'info',
                        'overtime' => 'warning',
                        'absent' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('day_part')
                    ->label('Day Part')
                    ->badge()
                    ->placeholder('-')
                    ->getStateUsing(fn ($record): string => $record instanceof \App\Models\Dtr
                        ? app(DtrAttendanceUnitService::class)->dayPartForRecord($record)
                        : (string) ($record->day_part ?? ''))
                    ->formatStateUsing(fn (?string $state): string => app(DtrDayPartService::class)->label($state))
                    ->color(fn (?string $state): string => match (app(DtrDayPartService::class)->normalize($state)) {
                        DtrDayPartService::MORNING, DtrDayPartService::AFTERNOON => 'info',
                        DtrDayPartService::UNCLASSIFIED => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('entry_source')
                    ->label('Source')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => filled($state) ? str($state)->title()->toString() : 'System')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('late')
                    ->label('Late')
                    ->formatStateUsing(fn ($state): string => (int) $state > 0 ? $this->formatMinutes($state) : '-')
                    ->badge(fn ($state): bool => (int) $state > 0)
                    ->color(fn ($state): ?string => (int) $state > 0 ? 'danger' : null)
                    ->alignCenter(),

                TextColumn::make('undertime')
                    ->label('Undertime')
                    ->formatStateUsing(fn ($state): string => (int) $state > 0 ? $this->formatMinutes($state) : '-')
                    ->badge(fn ($state): bool => (int) $state > 0)
                    ->color(fn ($state): ?string => (int) $state > 0 ? 'warning' : null)
                    ->alignCenter(),

                TextColumn::make('early_clock_in')
                    ->label('Early In')
                    ->formatStateUsing(fn ($state): string => (int) $state > 0 ? $this->formatMinutes($state) : '-')
                    ->badge(fn ($state): bool => (int) $state > 0)
                    ->color(fn ($state): ?string => (int) $state > 0 ? 'info' : null)
                    ->alignCenter(),

                TextColumn::make('overtime')
                    ->label('Overtime')
                    ->formatStateUsing(fn ($state): string => (int) $state > 0 ? $this->formatMinutes($state) : '-')
                    ->badge(fn ($state): bool => (int) $state > 0)
                    ->color(fn ($state): ?string => (int) $state > 0 ? 'purple' : null)
                    ->alignCenter(),

                TextColumn::make('credited_overtime')
                    ->label('Cred. OT')
                    ->formatStateUsing(fn ($state): string => (int) $state > 0 ? $this->formatMinutes($state) : '-')
                    ->badge(fn ($state): bool => (int) $state > 0)
                    ->color(fn ($state): ?string => (int) $state > 0 ? 'success' : null)
                    ->alignCenter(),

                TextColumn::make('work_hrs')
                    ->label('Work Hours')
                    ->formatStateUsing(fn ($state): string => (int) $state > 0 ? $this->formatDuration($state) : '-')
                    ->extraAttributes(['class' => 'font-semibold tabular-nums'])
                    ->alignCenter(),

                TextColumn::make('credited_work_hrs')
                    ->label('Cred. Work Hours')
                    ->formatStateUsing(fn ($state): string => (int) $state > 0 ? $this->formatDuration($state) : '-')
                    ->badge(fn ($state): bool => (int) $state > 0)
                    ->color(function (DtrModel $record, $state): ?string {
                        if ((int) $state <= 0) {
                            return null;
                        }

                        return ((int) $record->late > 0 || (int) $record->undertime > 0)
                            ? 'warning'
                            : 'info';
                    })
                    ->alignCenter(),

                TextColumn::make('holiday_type')
                    ->label('Holiday')
                    ->badge(fn ($state): bool => filled($state))
                    ->color(fn ($state): ?string => filled($state) ? 'warning' : null)
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('holiday_rate')
                    ->label('Hol. Rate')
                    ->formatStateUsing(fn ($state): string => filled($state) ? number_format((float) $state, 2).'%' : '-')
                    ->badge(fn ($state): bool => filled($state))
                    ->color(fn ($state): ?string => filled($state) ? 'success' : null)
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('overtime_status')
                    ->label('OT Status')
                    ->badge()
                    ->placeholder('-')
                    ->color(fn (?string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('comment')
                    ->label('Comment')
                    ->limit(35)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('viewDetails')
                        ->label('View Details')
                        ->icon(Heroicon::Eye)
                        ->modalHeading('D.T.R Details')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalWidth('2xl')
                        ->modalContent(fn (DtrModel $record) => view('filament.employee.pages.partials.dtr-details', [
                            'record' => $record,
                            'status' => $this->attendanceStatus($record),
                        ])),

                    Action::make('changeRequest')
                        ->label('Change Request')
                        ->icon(Heroicon::DocumentPlus)
                        ->modalHeading('Submit D.T.R Change Request')
                        ->modalIcon(Heroicon::DocumentPlus)
                        ->modalDescription(fn (DtrModel $record): string => 'Request approval or correction for ' . (filled($record->date_in) ? Carbon::parse($record->date_in)->format('M d, Y') : 'this D.T.R entry'))
                        ->modalSubmitActionLabel('Submit Request')
                        ->modalCancelActionLabel('Cancel')
                        ->modalWidth('2xl')
                        ->form([
                            ViewField::make('dtr_summary')
                                ->view('filament.employee.pages.partials.dtr-change-request-summary')
                                ->viewData(fn ($record, $get = null): array => [
                                    'record' => $record,
                                    'requestType' => is_callable($get) ? $get('request_type') : null,
                                    'overtimeScope' => is_callable($get) ? $get('overtime_request_scope') : null,
                                ])
                                ->columnSpanFull(),

                            Select::make('request_type')
                                ->label('Change Request Type')
                                ->placeholder('Select request type...')
                                ->options([
                                    DtrChangeRequest::TYPE_OVERTIME_APPROVAL => 'Overtime Approval',
                                    DtrChangeRequest::TYPE_FORGOT_TO_PUNCH => 'Forgot To Punch Approval',
                                    DtrChangeRequest::TYPE_TIME_DISCREPANCY => 'Time Entries Discrepancy',
                                    DtrChangeRequest::TYPE_DELETE_TIME_ENTRY => 'Delete Time Entry',
                                ])
                                ->required()
                                ->rules([
                                    fn ($record): \Closure => function (string $attribute, $value, \Closure $fail) use ($record): void {
                                        if ($value === DtrChangeRequest::TYPE_FORGOT_TO_PUNCH) {
                                            $schedTypeLower = strtolower((string) ($record->schedule_type ?? ''));
                                            $isForgotSched = str_contains($schedTypeLower, 'forgot');
                                            $hasMissingPunch = blank($record->time_in) || blank($record->time_out);

                                            if (! $isForgotSched && ! $hasMissingPunch) {
                                                $fail('The selected DTR entry is not a Forgot To Punch record. Please select Time Entries Discrepancy or another appropriate request type.');
                                            }
                                        }
                                    },
                                ])
                                ->helperText(function ($record, $get): ?string {
                                    if ($get('request_type') === DtrChangeRequest::TYPE_FORGOT_TO_PUNCH) {
                                        $schedTypeLower = strtolower((string) ($record->schedule_type ?? ''));
                                        $isForgotSched = str_contains($schedTypeLower, 'forgot');
                                        $hasMissingPunch = blank($record->time_in) || blank($record->time_out);

                                        if (! $isForgotSched && ! $hasMissingPunch) {
                                            return '⚠️ Warning: This DTR entry has complete punches and is not marked as Forgot To Punch. Submission is restricted; please use Time Entries Discrepancy instead.';
                                        }
                                    }

                                    return null;
                                })
                                ->live()
                                ->columnSpanFull(),

                            Select::make('overtime_request_scope')
                                ->label('Overtime Component to Request')
                                ->visible(fn ($get): bool => $get('request_type') === DtrChangeRequest::TYPE_OVERTIME_APPROVAL)
                                ->required(fn ($get): bool => $get('request_type') === DtrChangeRequest::TYPE_OVERTIME_APPROVAL)
                                ->live()
                                ->options(function ($record): array {
                                    $early = max(0, (int) ($record?->early_clock_in ?? 0));
                                    $ot = max(0, (int) ($record?->overtime ?? 0));

                                    if ($early >= 30 && $ot >= 30) {
                                        return [
                                            DtrChangeRequest::OT_SCOPE_BOTH => "Both Early Clock In ({$early} mins) & Overtime ({$ot} mins)",
                                            DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY => "Overtime Only ({$ot} mins)",
                                            DtrChangeRequest::OT_SCOPE_EARLY_ONLY => "Early Clock In Only ({$early} mins)",
                                        ];
                                    }

                                    if ($ot >= 30) {
                                        return [
                                            DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY => "Overtime Only ({$ot} mins)",
                                        ];
                                    }

                                    if ($early >= 30) {
                                        return [
                                            DtrChangeRequest::OT_SCOPE_EARLY_ONLY => "Early Clock In Only ({$early} mins)",
                                        ];
                                    }

                                    return [
                                        DtrChangeRequest::OT_SCOPE_BOTH => 'Both Early Clock In & Overtime',
                                    ];
                                })
                                ->default(function ($record): string {
                                    $early = max(0, (int) ($record?->early_clock_in ?? 0));
                                    $ot = max(0, (int) ($record?->overtime ?? 0));

                                    if ($early >= 30 && $ot >= 30) {
                                        return DtrChangeRequest::OT_SCOPE_BOTH;
                                    }

                                    if ($ot >= 30) {
                                        return DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY;
                                    }

                                    if ($early >= 30) {
                                        return DtrChangeRequest::OT_SCOPE_EARLY_ONLY;
                                    }

                                    return DtrChangeRequest::OT_SCOPE_BOTH;
                                })
                                ->helperText(function ($record): string {
                                    $early = max(0, (int) ($record?->early_clock_in ?? 0));
                                    $ot = max(0, (int) ($record?->overtime ?? 0));

                                    if ($early >= 30 && $ot >= 30) {
                                        return 'Select whether to request approval for Both, Overtime Only, or Early Clock In Only.';
                                    }

                                    if ($ot >= 30) {
                                        return sprintf('After-schedule overtime is eligible (%d mins). Early clock in (%d mins) is below policy threshold.', $ot, $early);
                                    }

                                    if ($early >= 30) {
                                        return sprintf('Early clock in is eligible (%d mins). After-schedule overtime (%d mins) is below policy threshold.', $early, $ot);
                                    }

                                    return 'Minimum 30 minutes required for overtime approval.';
                                })
                                ->columnSpanFull(),

                            Textarea::make('explanation')
                                ->label('Explanation / Justification')
                                ->placeholder(fn ($get): string => $get('request_type') === DtrChangeRequest::TYPE_DELETE_TIME_ENTRY
                                    ? 'Explain why this DTR entry should be permanently deleted...'
                                    : 'Provide a clear explanation for this request (e.g. reason for missed punch or details of overtime rendered)...')
                                ->required()
                                ->rows(3)
                                ->maxLength(2000)
                                ->columnSpanFull(),

                            FileUpload::make('attachment_path')
                                ->label('Attached File')
                                ->disk('local')
                                ->directory('dtr-change-requests')
                                ->acceptedFileTypes([
                                    'image/png',
                                    'image/jpeg',
                                    'image/jpg',
                                ])
                                ->maxSize(2560)
                                ->helperText('Accepted file types: PNG, JPG, JPEG (Max 2.5MB).')
                                ->storeFileNamesIn('attachment_original_name')
                                ->fetchFileInformation(false)
                                ->downloadable()
                                ->openable()
                                ->columnSpanFull(),
                        ])
                        ->action(function (DtrModel $record, array $data): void {
                            $employee = $this->employee;
                            if (! $employee) {
                                Notification::make()->title('Employee record not found')->danger()->send();
                                return;
                            }

                            try {
                                app(DtrChangeRequestService::class)->submitFromDtr($employee, $record, $data);

                                Notification::make()
                                    ->title('D.T.R Change Request Submitted')
                                    ->body('Your change request has been submitted for review.')
                                    ->success()
                                    ->send();

                                $this->resetTable();
                            } catch (ValidationException $e) {
                                $message = collect($e->errors())->flatten()->first() ?: $e->getMessage();
                                Notification::make()
                                    ->title('Unable to Submit Request')
                                    ->body($message)
                                    ->danger()
                                    ->send();
                            }
                        }),
                ])
                ->icon(Heroicon::EllipsisVertical)
                ->iconButton()
                ->label('Action')
                ->tooltip('Actions')
                ->color('gray'),
            ])
            ->actionsColumnLabel('Action')
            ->recordUrl(null)
            ->striped()
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('No D.T.R entries found')
            ->emptyStateDescription('There are no attendance records for this payroll period.')
            ->emptyStateIcon(Heroicon::Clock);
    }

    public function getEmployeeProperty(): ?Employee
    {
        return auth()->user()
            ?->employee()
            ->with(['branch', 'designation'])
            ->first();
    }

    public function getSelectedPeriodProperty(): ?PayrollPeriod
    {
        $periodId = $this->period_id ?: ($this->data['period_id'] ?? null);

        return filled($periodId)
            ? PayrollPeriod::query()->find($periodId)
            : null;
    }

    /**
     * @return array<string, int|float>
     */
    public function getOverviewProperty(): array
    {
        $defaults = [
            'total_entries' => 0,
            'present_entries' => 0,
            'leave_entries' => 0,
            'absent_entries' => 0,
            'total_late' => 0,
            'total_undertime' => 0,
            'total_credited_overtime' => 0,
            'total_credited_work' => 0,
        ];

        if (! $this->selectedPeriod) {
            return $defaults;
        }

        $records = $this->baseDtrQuery()->get();
        $finalizedRecords = $records
            ->reject(fn (DtrModel $record): bool => $record->requiresAttendanceApproval())
            ->values();
        $overview = [
            'total_entries' => $finalizedRecords->count(),
            'present_entries' => 0,
            'leave_entries' => $finalizedRecords->filter(
                fn (DtrModel $record): bool => filled($record->leave_id)
                    || str($record->schedule_type)->lower()->toString() === 'leave',
            )->count(),
            'absent_entries' => $finalizedRecords->filter(
                fn (DtrModel $record): bool => (bool) $record->is_absent,
            )->count(),
            'total_late' => (int) $finalizedRecords->sum('late'),
            'total_undertime' => (int) $finalizedRecords->sum('undertime'),
            'total_credited_overtime' => (int) $finalizedRecords->sum('credited_overtime'),
            'total_credited_work' => (int) $finalizedRecords->sum('credited_work_hrs'),
        ];

        $overview['present_entries'] = app(DtrAttendanceUnitService::class)
            ->attendanceDays($finalizedRecords);

        return $overview;
    }

    public function formatDayUnits(mixed $units): string
    {
        $units = max(0, (float) $units);

        return fmod($units, 1.0) === 0.0
            ? number_format($units)
            : number_format($units, 1);
    }

    public function formatMinutes(mixed $minutes): string
    {
        return max(0, (int) $minutes).' min';
    }

    public function formatDuration(mixed $minutes): string
    {
        $minutes = max(0, (int) $minutes);

        return sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
    }

    public function attendanceStatus(DtrModel $record): string
    {
        if ($record->requiresAttendanceApproval()) {
            return 'For Approval';
        }

        if ($record->is_absent) {
            return 'Absent';
        }

        if (filled($record->leave_id) || str($record->schedule_type)->lower()->toString() === 'leave') {
            return 'Leave';
        }

        if (str($record->schedule_type)->lower()->toString() === 'overtime') {
            return 'Overtime';
        }

        return 'Present';
    }

    /**
     * @return array<int|string, string>
     */
    protected function payrollPeriodOptions(): array
    {
        return PayrollPeriod::query()
            ->newestFirst()
            ->get()
            ->mapWithKeys(fn (PayrollPeriod $period): array => [
                $period->id => $period->title,
            ])
            ->all();
    }

    protected function dtrQuery(): Builder
    {
        if (! $this->selectedPeriod) {
            return DtrModel::query()->whereRaw('1 = 0');
        }

        return $this->baseDtrQuery();
    }

    protected function baseDtrQuery(): Builder
    {
        $employee = $this->employee;
        $periodId = $this->period_id ?: ($this->data['period_id'] ?? null);

        if (! $employee || blank($employee->branch_id) || blank($periodId)) {
            return DtrModel::query()->whereRaw('1 = 0');
        }

        if ($this->selectedPeriod && ! $this->selectedPeriod->is_locked && $this->hasEmployeeVisibleDtr($employee)) {
            return EmployeeVisibleDtr::query()
                ->where('payroll_period_id', (int) $periodId)
                ->forEmployee($employee);
        }

        return app(DtrRecordService::class)->query(
            $employee,
            (int) $employee->branch_id,
            (int) $periodId,
        );
    }

    protected function hasEmployeeVisibleDtr(Employee $employee): bool
    {
        $periodId = $this->period_id ?: ($this->data['period_id'] ?? null);

        return filled($periodId)
            && EmployeeVisibleDtr::query()
                ->where('payroll_period_id', (int) $periodId)
                ->forEmployee($employee)
                ->exists();
    }

    protected function fingerprintId(?Employee $employee): string|int|null
    {
        return $employee?->fingerprint_id ?: $employee?->uid;
    }

    protected function canPrintDtr(): bool
    {
        return filled($this->employee)
            && filled($this->employee?->branch)
            && filled($this->selectedPeriod);
    }

    protected function getPrintUrl(): string
    {
        if (! $this->canPrintDtr()) {
            return '#';
        }

        return route('dtr.print', [
            'period' => $this->selectedPeriod->publicKey(),
            'branch' => $this->employee->branch->publicKey(),
            'employee' => $this->employee->publicKey(),
        ]);
    }
}
