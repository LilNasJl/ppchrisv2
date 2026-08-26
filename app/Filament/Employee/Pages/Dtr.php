<?php

namespace App\Filament\Employee\Pages;

use App\Models\Dtr as DtrModel;
use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
use App\Services\DtrAttendanceUnitService;
use App\Services\DtrDayPartService;
use App\Services\DtrRecordService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
        $employee = $this->employee;
        $fingerprintId = $this->fingerprintId($employee);

        $periodId = null;

        if ($employee && filled($fingerprintId) && filled($employee->branch_id)) {
            $periodId = PayrollPeriod::query()
                ->where(function (Builder $query) use ($employee, $fingerprintId): void {
                    $query
                        ->whereHas('dtrs', fn (Builder $query): Builder => $query
                            ->where('fingerprint_id', $fingerprintId)
                            ->where('branch_id', $employee->branch_id))
                        ->orWhereHas('employeeVisibleDtrs', fn (Builder $query): Builder => $query
                            ->forEmployee($employee));
                })
                ->newestFirst()
                ->value('id');
        }

        $this->period_id = (string) ($periodId ?: PayrollPeriod::query()
            ->newestFirst()
            ->value('id'));

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
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (): void {
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
                    ->rowIndex(),

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
                    }),

                TextColumn::make('date_in')
                    ->label('Date In')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('time_in')
                    ->label('In')
                    ->time('h:i A'),

                TextColumn::make('date_out')
                    ->label('Date Out')
                    ->date('M d, Y')
                    ->placeholder('-'),

                TextColumn::make('time_out')
                    ->label('Out')
                    ->time('h:i A')
                    ->placeholder('-'),

                TextColumn::make('schedule_start')
                    ->label('Sched. Start')
                    ->time('h:i A')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('schedule_end')
                    ->label('Sched. End')
                    ->time('h:i A')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('schedule_type')
                    ->label('Sched. Type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-')
                    ->toggleable(),

                TextColumn::make('day_part')
                    ->label('Day Part')
                    ->formatStateUsing(fn ($state): string => app(DtrDayPartService::class)->label($state))
                    ->toggleable(),

                TextColumn::make('entry_source')
                    ->label('Source')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => filled($state) ? str($state)->title()->toString() : 'System')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('late')
                    ->label('Late')
                    ->formatStateUsing(fn ($state): string => $this->formatMinutes($state)),

                TextColumn::make('undertime')
                    ->label('Undertime')
                    ->formatStateUsing(fn ($state): string => $this->formatMinutes($state)),

                TextColumn::make('overtime')
                    ->label('Overtime')
                    ->formatStateUsing(fn ($state): string => $this->formatMinutes($state)),

                TextColumn::make('credited_overtime')
                    ->label('Cred. OT')
                    ->formatStateUsing(fn ($state): string => $this->formatMinutes($state)),

                TextColumn::make('work_hrs')
                    ->label('Work Hours')
                    ->formatStateUsing(fn ($state): string => $this->formatDuration($state)),

                TextColumn::make('credited_work_hrs')
                    ->label('Cred. Work Hours')
                    ->formatStateUsing(fn ($state): string => $this->formatDuration($state)),

                TextColumn::make('holiday_type')
                    ->label('Holiday')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('holiday_rate')
                    ->label('Hol. Rate')
                    ->formatStateUsing(fn ($state): string => filled($state) ? number_format((float) $state, 2).'%' : '-')
                    ->toggleable(),

                TextColumn::make('overtime_status')
                    ->label('OT Status')
                    ->badge()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('comment')
                    ->label('Comment')
                    ->limit(35)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('viewDetails')
                    ->label('View Details')
                    ->icon(Heroicon::Eye)
                    ->modalHeading('D.T.R Details')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('4xl')
                    ->modalContent(fn (DtrModel $record) => view('filament.employee.pages.partials.dtr-details', [
                        'record' => $record,
                        'status' => $this->attendanceStatus($record),
                    ])),
            ])
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
        return filled($this->period_id)
            ? PayrollPeriod::query()->find($this->period_id)
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

        if (! $employee || blank($employee->branch_id) || blank($this->period_id)) {
            return DtrModel::query()->whereRaw('1 = 0');
        }

        if ($this->selectedPeriod && ! $this->selectedPeriod->is_locked && $this->hasEmployeeVisibleDtr($employee)) {
            return EmployeeVisibleDtr::query()
                ->where('payroll_period_id', (int) $this->period_id)
                ->forEmployee($employee);
        }

        return app(DtrRecordService::class)->query(
            $employee,
            (int) $employee->branch_id,
            (int) $this->period_id,
        );
    }

    protected function hasEmployeeVisibleDtr(Employee $employee): bool
    {
        return filled($this->period_id)
            && EmployeeVisibleDtr::query()
                ->where('payroll_period_id', (int) $this->period_id)
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
