<?php

namespace App\Filament\Widgets;

use App\Filament\Exports\DtrExporter;
use App\Models\Branch;
use App\Models\Dtr as ModelsDtr;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\DtrCalculator;
use App\Services\HolidayResolver;
use App\Support\HrDatabaseNotification;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class DtrManageTable extends BaseWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    public ?string $employeeId = null;

    public ?string $branchId = null;

    public ?string $periodId = null;

    public function table(Table $table): Table
    {
        return $table
            ->heading(fn (): string => 'Payroll Period: '.($this->getSelectedPayrollPeriod()?->title ?? 'No payroll period selected'))
            ->query(fn (): Builder => $this->getDtrQuery())
            ->recordClasses('text-xs')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('attendance_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (ModelsDtr $record): string => $record->is_absent ? 'Absent' : 'Present')
                    ->color(fn (string $state): string => $state === 'Absent' ? 'danger' : 'success'),

                TextColumn::make('date_in')
                    ->label('Date In')
                    ->date(),

                TextColumn::make('time_in')
                    ->label('IN')
                    ->placeholder('-'),

                TextColumn::make('date_out')
                    ->label('Date Out')
                    ->date()
                    ->placeholder('-'),

                TextColumn::make('time_out')
                    ->label('OUT')
                    ->placeholder('-'),

                TextColumn::make('schedule_start')
                    ->label('SCHED. START')
                    ->placeholder('-'),

                TextColumn::make('schedule_end')
                    ->label('SCHED. END')
                    ->placeholder('-'),

                TextColumn::make('schedule_type')
                    ->label('SCHED. TYPE')
                    ->badge()
                    ->placeholder('-')
                    ->color(fn (?string $state): string => match ($state) {
                        'Saturday' => 'info',
                        'Overtime' => 'warning',
                        'Forgot to Punch', 'Absent' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('late')
                    ->label('LATE')
                    ->numeric(),

                TextColumn::make('undertime')
                    ->label('UNDERTIME')
                    ->numeric(),

                TextColumn::make('overtime')
                    ->label('OVERTIME')
                    ->numeric(),

                TextColumn::make('credited_overtime')
                    ->label('CRED. OT')
                    ->numeric(),

                TextColumn::make('work_hrs')
                    ->label('WORK HOURS')
                    ->numeric(),

                TextColumn::make('credited_work_hrs')
                    ->label('CRED. WORK HOURS')
                    ->numeric(),

                TextColumn::make('holiday_type')
                    ->label('HOL.')
                    ->badge()
                    ->placeholder('n/a')
                    ->color('warning'),

                TextColumn::make('holiday_rate')
                    ->label('HOL. RATE')
                    ->placeholder('n/a'),

                TextColumn::make('overtime_status')
                    ->label('OT Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Approved' => 'success',
                        default => 'gray',
                    }),
            ])
            ->headerActions([
                Action::make('viewComments')
                    ->label('View Comments')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->modalHeading('D.T.R Comments')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('4xl')
                    ->modalContent(fn () => view('filament.widgets.dtr-comments-modal', [
                        'comments' => $this->getDtrComments(),
                    ])),

                ExportAction::make('exportDtr')
                    ->label('Export D.T.R')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->exporter(DtrExporter::class)
                    ->fileName(fn (): string => 'managedtr-'.now()->format('Ymd-His')),

                ActionGroup::make([
                    Action::make('addDtr')
                        ->label('Add D.T.R')
                        ->icon('heroicon-m-plus-circle')
                        ->schema($this->getDtrFormSchema())
                        ->modalHeading('Add D.T.R')
                        ->modalSubmitActionLabel('Save')
                        ->disabled(fn (): bool => $this->isSelectedPayrollPeriodLocked())
                        ->action(fn (array $data): mixed => $this->addDtr($data)),

                    Action::make('addAbsence')
                        ->label('Add Absence')
                        ->icon('heroicon-m-no-symbol')
                        ->visible(fn (): bool => $this->isRegularScheduleEmployee())
                        ->schema([
                            DatePicker::make('absence_date')
                                ->label('Absence Date')
                                ->required(),

                            Textarea::make('comment')
                                ->label('Comment')
                                ->rows(3)
                                ->maxLength(1000)
                                ->columnSpanFull(),
                        ])
                        ->modalHeading('Add Absence')
                        ->modalSubmitActionLabel('Save')
                        ->disabled(fn (): bool => $this->isSelectedPayrollPeriodLocked())
                        ->action(fn (array $data): mixed => $this->addAbsence($data)),

                    Action::make('fillDtr')
                        ->label('Fill D.T.R')
                        ->icon('heroicon-m-calendar-days')
                        ->requiresConfirmation()
                        ->modalHeading('Fill D.T.R for this payroll period?')
                        ->modalDescription('This will create regular schedule D.T.R entries from the payroll period start date to end date, excluding Sundays.')
                        ->visible(fn (): bool => $this->canUseRegularFill())
                        ->disabled(fn (): bool => $this->isSelectedPayrollPeriodLocked())
                        ->action(fn (): mixed => $this->fillDtr()),

                    Action::make('clearDtr')
                        ->label('Clear D.T.R')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Clear D.T.R entries?')
                        ->modalDescription('This will clear all D.T.R entries for this employee, branch, and selected payroll period.')
                        ->disabled(fn (): bool => $this->isSelectedPayrollPeriodLocked())
                        ->action(fn (): mixed => $this->clearDtr()),
                ])
                    ->label('D.T.R Actions')
                    ->icon('heroicon-m-chevron-down')
                    ->button(),
            ])
            ->recordActions([
                Action::make('approveOvertime')
                    ->label('Approve OT')
                    ->icon('heroicon-m-check-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Approve overtime?')
                    ->modalDescription('This will credit the overtime minutes to credited work hours.')
                    ->visible(fn (ModelsDtr $record): bool => $this->shouldShowOvertimeApproval($record))
                    ->action(function (ModelsDtr $record): void {
                        $record->overtime_approved = true;
                        $this->updateApprovalTotals($record);

                        Notification::make()
                            ->title('Overtime approved')
                            ->success()
                            ->send();
                    }),

                ActionGroup::make([
                    EditAction::make('editDtr')
                        ->label('Edit')
                        ->schema($this->getDtrFormSchema())
                        ->modalHeading('Edit D.T.R')
                        ->modalSubmitActionLabel('Save')
                        ->visible(fn (ModelsDtr $record): bool => ! $record->is_absent && ! $this->isRecordLocked($record))
                        ->fillForm(fn (ModelsDtr $record): array => $this->getEditFormData($record))
                        ->action(fn (ModelsDtr $record, array $data): mixed => $this->updateDtr($record, $data)),

                    DeleteAction::make('deleteDtr')
                        ->label('Delete')
                        ->visible(fn (ModelsDtr $record): bool => ! $this->isRecordLocked($record))
                        ->using(fn (ModelsDtr $record): bool => (bool) $record->forceDelete()),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Actions')
                    ->color('primary'),
            ]);
    }

    protected function getDtrFormSchema(): array
    {
        return [
            Group::make()
                ->schema([
                    Toggle::make('overtime_only')
                        ->label('Overtime Only')
                        ->live()
                        ->columnSpanFull(),

                    DatePicker::make('date_in')
                        ->label('Date In')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state): void {
                            if ($this->usesSaturdaySchedule($state)) {
                                $set('saturday_schedule_start', '08:00:00');
                                $set('saturday_schedule_end', '11:00:00');
                            }
                        }),

                    TimePicker::make('time_in')
                        ->label('Time In')
                        ->required(),

                    DatePicker::make('date_out')
                        ->label('Date Out')
                        ->required(),

                    TimePicker::make('time_out')
                        ->label('Time Out')
                        ->required(),

                    Select::make('schedule_start')
                        ->label('Schedule Start')
                        ->options(fn (): array => $this->getScheduleStartOptions())
                        ->searchable()
                        ->disabled(fn (Get $get): bool => (bool) $get('overtime_only') || $this->usesSaturdaySchedule($get('date_in')))
                        ->required(fn (Get $get): bool => ! (bool) $get('overtime_only') && ! $this->usesSaturdaySchedule($get('date_in'))),

                    Select::make('schedule_end')
                        ->label('Schedule End')
                        ->options(fn (): array => $this->getScheduleEndOptions())
                        ->searchable()
                        ->disabled(fn (Get $get): bool => (bool) $get('overtime_only') || $this->usesSaturdaySchedule($get('date_in')))
                        ->required(fn (Get $get): bool => ! (bool) $get('overtime_only') && ! $this->usesSaturdaySchedule($get('date_in'))),

                    Select::make('saturday_schedule_start')
                        ->label('Saturday Schedule Start')
                        ->options(['08:00:00' => '08:00 AM'])
                        ->default('08:00:00')
                        ->disabled()
                        ->dehydrated(true)
                        ->visible(fn (Get $get): bool => ! (bool) $get('overtime_only') && $this->usesSaturdaySchedule($get('date_in'))),

                    Select::make('saturday_schedule_end')
                        ->label('Saturday Schedule End')
                        ->options(['11:00:00' => '11:00 AM'])
                        ->default('11:00:00')
                        ->disabled()
                        ->dehydrated(true)
                        ->visible(fn (Get $get): bool => ! (bool) $get('overtime_only') && $this->usesSaturdaySchedule($get('date_in'))),

                    Textarea::make('comment')
                        ->label('Comment')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    protected function addDtr(array $data): void
    {
        if (! $this->canMutateDtr()) {
            return;
        }

        if (! $this->ensureDtrDatesWithinSelectedPayrollPeriod($data, 'Unable to add D.T.R record')) {
            return;
        }

        if ($this->hasAbsenceOnDate($data['date_in']) || $this->hasAbsenceOnDate($data['date_out'])) {
            Notification::make()
                ->title('Unable to add D.T.R record')
                ->body('The selected date in or date out is already marked as absent.')
                ->danger()
                ->send();

            return;
        }

        ModelsDtr::create([
            ...$this->buildDtrData($data),
            ...$this->getDtrScopeData(),
            'is_imported' => 0,
            'is_locked' => 0,
            'is_absent' => 0,
        ]);

        $this->flushCachedTableRecords();

        Notification::make()
            ->title('D.T.R record added')
            ->success()
            ->send();
    }

    protected function updateDtr(ModelsDtr $record, array $data): void
    {
        if ($this->isRecordLocked($record)) {
            $this->sendLockedNotification();

            return;
        }

        if (! $this->ensureDtrDatesWithinSelectedPayrollPeriod($data, 'Unable to update D.T.R record')) {
            return;
        }

        if ($this->hasAbsenceOnDate($data['date_in'], $record->id) || $this->hasAbsenceOnDate($data['date_out'], $record->id)) {
            Notification::make()
                ->title('Unable to update D.T.R record')
                ->body('The selected date in or date out is already marked as absent.')
                ->danger()
                ->send();

            return;
        }

        $record->update($this->buildDtrData($data));

        $this->flushCachedTableRecords();

        Notification::make()
            ->title('D.T.R record updated')
            ->success()
            ->send();
    }

    protected function addAbsence(array $data): void
    {
        if (! $this->canMutateDtr()) {
            return;
        }

        $absenceDate = Carbon::parse($data['absence_date'])->toDateString();

        if (! $this->ensureDatesWithinSelectedPayrollPeriod([$absenceDate], 'Unable to add absence')) {
            return;
        }

        if ($this->getScopedDtrQuery()->whereDate('date_in', $absenceDate)->exists()) {
            Notification::make()
                ->title('Unable to add absence')
                ->body('A D.T.R or absence record already exists for this date.')
                ->danger()
                ->send();

            return;
        }

        ModelsDtr::create([
            ...$this->getDtrScopeData(),
            ...$this->getHolidayData($absenceDate),
            'date_in' => $absenceDate,
            'date_out' => $absenceDate,
            'time_in' => null,
            'time_out' => null,
            'schedule_type' => 'Absent',
            'schedule_start' => null,
            'schedule_end' => null,
            'late' => 0,
            'undertime' => 0,
            'overtime' => 0,
            'early_clock_in' => 0,
            'credited_overtime' => 0,
            'work_hrs' => 0,
            'credited_work_hrs' => 0,
            'overtime_status' => 'n/a',
            'early_clock_in_approved' => false,
            'overtime_approved' => false,
            'daily_rate' => $this->getDailyRate(),
            'comment' => $this->normalizeComment($data['comment'] ?? null),
            'is_absent' => true,
            'is_imported' => 0,
            'is_locked' => 0,
        ]);

        $this->flushCachedTableRecords();

        Notification::make()
            ->title('Absence added')
            ->success()
            ->send();
    }

    protected function fillDtr(): void
    {
        if (! $this->canMutateDtr()) {
            return;
        }

        if (! $this->canUseRegularFill()) {
            Notification::make()
                ->title('Fill D.T.R is unavailable')
                ->body('This employee must use a regular branch schedule.')
                ->danger()
                ->send();

            return;
        }

        $payrollPeriod = $this->getSelectedPayrollPeriod();
        $branch = $this->getBranch();

        if (! $payrollPeriod || ! $branch) {
            Notification::make()
                ->title('Unable to fill D.T.R')
                ->body('Payroll period or branch details are missing.')
                ->danger()
                ->send();

            return;
        }

        if ($this->getScopedDtrQuery()->exists()) {
            Notification::make()
                ->title('Unable to fill D.T.R')
                ->body('This employee already has D.T.R entries for this payroll period. Clear the entries first.')
                ->danger()
                ->send();

            return;
        }

        $created = 0;

        foreach (CarbonPeriod::create($payrollPeriod->date_start, $payrollPeriod->date_end) as $date) {
            if ($date->isSunday()) {
                continue;
            }

            $dateString = $date->toDateString();
            $isSaturday = $date->isSaturday();

            $data = [
                'date_in' => $dateString,
                'time_in' => $isSaturday ? '08:00:00' : $branch->reg_sched_start,
                'date_out' => $dateString,
                'time_out' => $isSaturday ? '11:00:00' : $branch->reg_sched_end,
                'schedule_start' => 'reg_sched_start',
                'schedule_end' => 'reg_sched_end',
                'saturday_schedule_start' => '08:00:00',
                'saturday_schedule_end' => '11:00:00',
                'overtime_only' => false,
            ];

            ModelsDtr::create([
                ...$this->buildDtrData($data),
                ...$this->getDtrScopeData(),
                'is_imported' => 0,
                'is_locked' => 0,
                'is_absent' => 0,
            ]);

            $created++;
        }

        $this->flushCachedTableRecords();

        Notification::make()
            ->title('D.T.R filled')
            ->body("Created {$created} D.T.R entries.")
            ->success()
            ->send();
    }

    protected function clearDtr(): void
    {
        if (! $this->canMutateDtr()) {
            return;
        }

        $deleted = $this->getScopedDtrQuery()->delete();

        if ($deleted > 0) {
            HrDatabaseNotification::send(
                title: 'D.T.R entries deleted',
                body: "Cleared {$deleted} D.T.R entries",
                status: 'danger',
                icon: Heroicon::Trash,
            );
        }

        $this->flushCachedTableRecords();

        Notification::make()
            ->title('D.T.R entries cleared')
            ->body("Cleared {$deleted} D.T.R entries.")
            ->success()
            ->send();
    }

    public function deleteDtrComment(int $dtrId): void
    {
        $record = $this->getScopedDtrQuery()
            ->with('payrollPeriod')
            ->whereKey($dtrId)
            ->first();

        if (! $record) {
            Notification::make()
                ->title('Comment not found')
                ->danger()
                ->send();

            return;
        }

        if ($this->isRecordLocked($record)) {
            $this->sendLockedNotification();

            return;
        }

        $record->forceFill(['comment' => null])->save();

        $this->flushCachedTableRecords();

        Notification::make()
            ->title('Comment deleted')
            ->success()
            ->send();
    }

    protected function getDtrComments()
    {
        if (blank($this->getSelectedPayrollPeriodId()) || blank($this->getBranchId()) || blank($this->getFingerprintId())) {
            return collect();
        }

        return $this->getScopedDtrQuery()
            ->with('payrollPeriod')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->orderBy('date_in')
            ->orderBy('time_in')
            ->get();
    }

    protected function getEditFormData(ModelsDtr $record): array
    {
        $usesSaturdaySchedule = in_array($record->schedule_type, ['Saturday', 'Regular Saturday'], true);

        return [
            'date_in' => $record->date_in,
            'time_in' => $record->time_in,
            'date_out' => $record->date_out,
            'time_out' => $record->time_out,
            'schedule_start' => $usesSaturdaySchedule ? null : $this->getScheduleColumnByTime($record->schedule_start, $this->getScheduleStartColumns()),
            'schedule_end' => $usesSaturdaySchedule ? null : $this->getScheduleColumnByTime($record->schedule_end, $this->getScheduleEndColumns()),
            'saturday_schedule_start' => '08:00:00',
            'saturday_schedule_end' => '11:00:00',
            'overtime_only' => $record->schedule_type === 'Overtime',
            'comment' => $record->comment,
        ];
    }

    protected function getDtrQuery(): Builder
    {
        $fingerprintId = $this->getFingerprintId();
        $branchId = $this->getBranchId();
        $payrollPeriodId = $this->getSelectedPayrollPeriodId();

        return ModelsDtr::query()
            ->with(['payrollPeriod', 'holiday'])
            ->when(
                filled($fingerprintId) && filled($branchId) && filled($payrollPeriodId),
                fn (Builder $query) => $query
                    ->where('fingerprint_id', $fingerprintId)
                    ->where('branch_id', $branchId)
                    ->where('payroll_period_id', $payrollPeriodId),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            )
            ->latest('date_in')
            ->latest('time_in');
    }

    protected function buildDtrData(array $data): array
    {
        $dateIn = Carbon::parse($data['date_in'])->toDateString();
        $dateOut = Carbon::parse($data['date_out'])->toDateString();
        $overtimeOnly = (bool) ($data['overtime_only'] ?? false);
        $usesSaturdaySchedule = ! $overtimeOnly && $this->usesSaturdaySchedule($dateIn);

        if ($overtimeOnly) {
            $scheduleStart = '00:00:00';
            $scheduleEnd = '00:00:00';
            $scheduleStartColumn = null;
        } elseif ($usesSaturdaySchedule) {
            $scheduleStart = '08:00:00';
            $scheduleEnd = '11:00:00';
            $scheduleStartColumn = 'saturday_schedule_start';
        } else {
            $scheduleStart = $this->getScheduleTime($data['schedule_start'] ?? null);
            $scheduleEnd = $this->getScheduleTime($data['schedule_end'] ?? null);
            $scheduleStartColumn = $data['schedule_start'] ?? null;
        }

        $calculationData = [
            ...$data,
            'date_in' => $dateIn,
            'date_out' => $dateOut,
            'time_in' => $this->normalizeTime($data['time_in']),
            'time_out' => $this->normalizeTime($data['time_out']),
            'schedule_start' => $scheduleStart,
            'schedule_end' => $scheduleEnd,
            'schedule_start_column' => $scheduleStartColumn,
            'overtime_only' => $overtimeOnly,
        ];

        return [
            'date_in' => $dateIn,
            'time_in' => $calculationData['time_in'],
            'date_out' => $dateOut,
            'time_out' => $calculationData['time_out'],
            'schedule_type' => $this->getScheduleTypeLabel($scheduleStartColumn, $overtimeOnly, $usesSaturdaySchedule),
            'schedule_start' => $scheduleStart,
            'schedule_end' => $scheduleEnd,
            'daily_rate' => $this->getDailyRate(),
            'comment' => $this->normalizeComment($data['comment'] ?? null),
            ...$this->getHolidayData($dateIn),
            ...$this->calculateDtr($calculationData),
        ];
    }

    protected function calculateDtr(array $data): array
    {
        return app(DtrCalculator::class)->calculate(
            dateIn: $data['date_in'],
            timeIn: $data['time_in'],
            dateOut: $data['date_out'],
            timeOut: $data['time_out'],
            scheduleStart: $data['schedule_start'] ?? null,
            scheduleEnd: $data['schedule_end'] ?? null,
            scheduleStartColumn: $data['schedule_start_column'] ?? null,
            scheduleType: $this->getScheduleTypeLabel(
                $data['schedule_start_column'] ?? null,
                (bool) ($data['overtime_only'] ?? false),
                $this->usesSaturdaySchedule($data['date_in'] ?? null),
            ),
            overtimeOnly: (bool) ($data['overtime_only'] ?? false),
        );
    }

    protected function getBreakDeductionMinutes(?string $scheduleStartColumn, Carbon $scheduleStart, Carbon $scheduleEnd): int
    {
        if ($scheduleStartColumn !== 'reg_sched_start') {
            return 0;
        }

        $breakStart = $scheduleStart->copy()->setTime(12, 0);
        $breakEnd = $scheduleStart->copy()->setTime(13, 0);

        return $scheduleStart->lessThan($breakEnd) && $scheduleEnd->greaterThan($breakStart)
            ? 60
            : 0;
    }

    protected function shouldShowOvertimeApproval(ModelsDtr $record): bool
    {
        return ! $this->isRecordLocked($record)
            && $record->overtime_status === 'Pending'
            && ((int) $record->overtime >= 30)
            && ! (bool) $record->overtime_approved;
    }

    protected function updateApprovalTotals(ModelsDtr $record): void
    {
        if ($this->isRecordLocked($record)) {
            $this->sendLockedNotification();

            return;
        }

        $earlyClockIn = (int) $record->early_clock_in;
        $overtime = (int) $record->overtime;
        $workMinutes = (int) $record->work_hrs;

        $approvedOvertime = ((bool) $record->overtime_approved && $overtime >= 30) ? $overtime : 0;
        $creditedOvertime = $approvedOvertime;

        $hasPendingOvertime = $overtime >= 30 && ! (bool) $record->overtime_approved;
        $hasApprovableOvertime = $overtime >= 30;

        $record->forceFill([
            'credited_overtime' => $creditedOvertime,
            'credited_work_hrs' => max(0, $workMinutes - $earlyClockIn - $overtime + $creditedOvertime),
            'overtime_status' => $hasPendingOvertime
                ? 'Pending'
                : ($hasApprovableOvertime ? 'Approved' : 'n/a'),
        ])->save();

        $this->flushCachedTableRecords();
    }

    protected function getScheduleStartOptions(): array
    {
        return $this->getScheduleOptions($this->getScheduleStartColumns());
    }

    protected function getScheduleEndOptions(): array
    {
        return $this->getScheduleOptions($this->getScheduleEndColumns());
    }

    protected function getScheduleStartColumns(): array
    {
        return [
            'reg_sched_start' => 'Regular Start',
            'shift1_start' => 'Shift 1 Start',
            'shift2_start' => 'Shift 2 Start',
            'shift3_start' => 'Shift 3 Start',
            'broken_shift1_start' => 'Broken Shift 1 Start',
            'broken_shift2_start' => 'Broken Shift 2 Start',
        ];
    }

    protected function getScheduleEndColumns(): array
    {
        return [
            'reg_sched_end' => 'Regular End',
            'shift1_end' => 'Shift 1 End',
            'shift2_end' => 'Shift 2 End',
            'shift3_end' => 'Shift 3 End',
            'broken_shift1_end' => 'Broken Shift 1 End',
            'broken_shift2_end' => 'Broken Shift 2 End',
        ];
    }

    protected function getScheduleOptions(array $columns): array
    {
        $branch = $this->getBranch();

        if (! $branch) {
            return [];
        }

        $options = [];

        foreach ($columns as $column => $label) {
            $value = $branch->{$column};

            if (blank($value)) {
                continue;
            }

            $options[$column] = "{$label} - ".Carbon::parse($value)->format('h:i A');
        }

        return $options;
    }

    protected function getScheduleTime(?string $scheduleColumn): ?string
    {
        $branch = $this->getBranch();

        if ($branch && filled($scheduleColumn) && filled($branch->{$scheduleColumn})) {
            return $this->normalizeTime($branch->{$scheduleColumn});
        }

        return filled($scheduleColumn) ? $this->normalizeTime($scheduleColumn) : null;
    }

    protected function getScheduleColumnByTime(?string $time, array $columns): ?string
    {
        $branch = $this->getBranch();

        if (! $branch || blank($time)) {
            return null;
        }

        $normalizedTime = Carbon::parse($time)->format('H:i:s');

        foreach ($columns as $column => $label) {
            if (blank($branch->{$column})) {
                continue;
            }

            if (Carbon::parse($branch->{$column})->format('H:i:s') === $normalizedTime) {
                return $column;
            }
        }

        return null;
    }

    protected function getScheduleTypeLabel(?string $scheduleStartColumn, bool $overtimeOnly, bool $usesSaturdaySchedule): string
    {
        if ($overtimeOnly) {
            return 'Overtime';
        }

        if ($usesSaturdaySchedule) {
            return 'Saturday';
        }

        return match ($scheduleStartColumn) {
            'reg_sched_start' => 'Regular',
            'shift1_start', 'shift2_start', 'shift3_start' => 'Shifting',
            'broken_shift1_start', 'broken_shift2_start' => 'Broken Shift',
            default => 'Manual',
        };
    }

    protected function usesSaturdaySchedule(mixed $date): bool
    {
        if (blank($date) || ! $this->canUseRegularFill()) {
            return false;
        }

        return Carbon::parse($date)->isSaturday();
    }

    protected function canUseRegularFill(): bool
    {
        $employee = $this->getEmployee();
        $branch = $this->getBranch();

        return $employee
            && $this->isRegularScheduleEmployee()
            && $branch
            && filled($branch->reg_sched_start)
            && filled($branch->reg_sched_end);
    }

    protected function isRegularScheduleEmployee(): bool
    {
        return ($this->getEmployee()?->schedule_type ?: 'regular') === 'regular';
    }

    protected function normalizeTime(mixed $time): string
    {
        return Carbon::parse($time)->second(0)->format('H:i:s');
    }

    protected function normalizeComment(mixed $comment): ?string
    {
        $comment = trim((string) ($comment ?? ''));

        return $comment === '' ? null : $comment;
    }

    protected function ensureDatesWithinSelectedPayrollPeriod(array $dates, string $title): bool
    {
        $payrollPeriod = $this->getSelectedPayrollPeriod();

        if (! $payrollPeriod || blank($payrollPeriod->date_start) || blank($payrollPeriod->date_end)) {
            Notification::make()
                ->title($title)
                ->body('Payroll period details are missing.')
                ->danger()
                ->send();

            return false;
        }

        $periodStart = Carbon::parse($payrollPeriod->date_start)->startOfDay();
        $periodEnd = Carbon::parse($payrollPeriod->date_end)->startOfDay();

        foreach ($dates as $date) {
            if (blank($date)) {
                continue;
            }

            $date = Carbon::parse($date)->startOfDay();

            if ($date->betweenIncluded($periodStart, $periodEnd)) {
                continue;
            }

            Notification::make()
                ->title($title)
                ->body('Selected dates must be within '.$periodStart->format('M d, Y').' to '.$periodEnd->format('M d, Y').'.')
                ->danger()
                ->send();

            return false;
        }

        return true;
    }

    protected function ensureDtrDatesWithinSelectedPayrollPeriod(array $data, string $title): bool
    {
        if (! $this->ensureDatesWithinSelectedPayrollPeriod([$data['date_in'] ?? null], $title)) {
            return false;
        }

        if (blank($data['date_in'] ?? null) || blank($data['date_out'] ?? null)) {
            return true;
        }

        $dateIn = Carbon::parse($data['date_in'])->startOfDay();
        $dateOut = Carbon::parse($data['date_out'])->startOfDay();

        if ($dateOut->lessThan($dateIn)) {
            Notification::make()
                ->title($title)
                ->body('Date out cannot be earlier than date in.')
                ->danger()
                ->send();

            return false;
        }

        if ($dateOut->greaterThan($dateIn->copy()->addDay())) {
            Notification::make()
                ->title($title)
                ->body('Date out can only be the same date or the next date for overnight schedules.')
                ->danger()
                ->send();

            return false;
        }

        return true;
    }

    protected function canMutateDtr(): bool
    {
        if ($this->isSelectedPayrollPeriodLocked()) {
            $this->sendLockedNotification();

            return false;
        }

        if (blank($this->getFingerprintId()) || blank($this->getBranchId()) || blank($this->getSelectedPayrollPeriodId())) {
            Notification::make()
                ->title('Unable to update D.T.R')
                ->body('Employee branch, fingerprint ID, or payroll period is missing.')
                ->danger()
                ->send();

            return false;
        }

        return true;
    }

    protected function isRecordLocked(ModelsDtr $record): bool
    {
        return (bool) $record->is_locked || (bool) $record->payrollPeriod?->is_locked;
    }

    protected function isSelectedPayrollPeriodLocked(): bool
    {
        return (bool) $this->getSelectedPayrollPeriod()?->is_locked;
    }

    protected function sendLockedNotification(): void
    {
        Notification::make()
            ->title('Payroll period is locked')
            ->body('Unlock the payroll period before changing D.T.R entries.')
            ->danger()
            ->send();
    }

    protected function hasAbsenceOnDate(mixed $date, ?int $exceptRecordId = null): bool
    {
        if (blank($date)) {
            return false;
        }

        return $this->getScopedDtrQuery()
            ->where('is_absent', true)
            ->whereDate('date_in', Carbon::parse($date)->toDateString())
            ->when($exceptRecordId, fn (Builder $query) => $query->whereKeyNot($exceptRecordId))
            ->exists();
    }

    protected function getScopedDtrQuery(): Builder
    {
        return ModelsDtr::query()
            ->where('payroll_period_id', $this->getSelectedPayrollPeriodId())
            ->where('branch_id', $this->getBranchId())
            ->where('fingerprint_id', $this->getFingerprintId());
    }

    protected function getDtrScopeData(): array
    {
        return [
            'payroll_period_id' => $this->getSelectedPayrollPeriodId(),
            'branch_id' => $this->getBranchId(),
            'fingerprint_id' => $this->getFingerprintId(),
        ];
    }

    protected function getEmployee(): ?Employee
    {
        if (blank($this->employeeId)) {
            return null;
        }

        return Employee::query()
            ->activeEmployment()
            ->find($this->employeeId);
    }

    protected function getDailyRate(): ?float
    {
        $dailyRate = $this->getEmployee()?->daily_rate;

        return filled($dailyRate) ? (float) $dailyRate : null;
    }

    protected function getHolidayData(string $date): array
    {
        $holiday = app(HolidayResolver::class)
            ->resolveForDate($date, $this->getBranchId());

        if (! $holiday) {
            return [
                'is_holiday' => 0,
                'holiday_id' => null,
                'holiday_type' => null,
                'holiday_rate' => null,
            ];
        }

        return [
            'is_holiday' => 1,
            'holiday_id' => $holiday->id,
            'holiday_type' => $holiday->type?->type,
            'holiday_rate' => $holiday->type?->rate,
        ];
    }

    protected function getBranch(): ?Branch
    {
        $branchId = $this->getBranchId();

        if (blank($branchId)) {
            return null;
        }

        return Branch::query()->find($branchId);
    }

    protected function getFingerprintId(): ?string
    {
        $employee = $this->getEmployee();
        $fingerprintId = $employee?->fingerprint_id ?: $employee?->uid;

        return filled($fingerprintId) ? (string) $fingerprintId : null;
    }

    protected function getBranchId(): ?int
    {
        $branchId = $this->branchId ?: $this->getEmployee()?->branch_id;

        return filled($branchId) ? (int) $branchId : null;
    }

    protected function getSelectedPayrollPeriodId(): ?int
    {
        $payrollPeriodId = $this->periodId ?: PayrollPeriod::latest()->value('id');

        return filled($payrollPeriodId) ? (int) $payrollPeriodId : null;
    }

    protected function getSelectedPayrollPeriod(): ?PayrollPeriod
    {
        $payrollPeriodId = $this->getSelectedPayrollPeriodId();

        return filled($payrollPeriodId) ? PayrollPeriod::query()->find($payrollPeriodId) : null;
    }
}
