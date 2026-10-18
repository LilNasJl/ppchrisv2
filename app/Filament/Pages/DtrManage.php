<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DtrManageTable;
use App\Models\Branch;
use App\Models\Dtr as DtrModel;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\DtrAttendanceUnitService;
use App\Services\DtrDayPartService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Override;

class DtrManage extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.dtr-manage';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Manage D.T.R';

    public ?int $employeeId = null;

    public ?int $branchId = null;

    public ?int $periodId = null;

    public ?string $search = null;

    public function mount(): void
    {
        $this->employeeId = Employee::resolvePublicId(request()->query('employeeId'));
        $this->branchId = Branch::resolvePublicId(request()->query('branchId'));
        $this->periodId = PayrollPeriod::resolvePublicId(request()->query('periodId'));
        $this->search = request()->query('search');
    }

    #[Override]
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    #[Override]
    public function getHeading(): string
    {
        return '';
    }

    #[Override]
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getWidgetData(): array
    {
        return [
            'employeeId' => $this->employeeId,
            'branchId' => $this->branchId,
            'periodId' => $this->periodId,
        ];
    }

    public function getEmployee(): ?Employee
    {
        return filled($this->employeeId)
            ? Employee::query()->with(['user', 'designation', 'department', 'branch'])->find((int) $this->employeeId)
            : null;
    }

    public function getBranch(): ?Branch
    {
        return filled($this->branchId)
            ? Branch::query()->find((int) $this->branchId)
            : null;
    }

    public function getSelectedPayrollPeriod(): ?PayrollPeriod
    {
        return filled($this->periodId)
            ? PayrollPeriod::query()->find((int) $this->periodId)
            : null;
    }

    public function getReturnUrl(): string
    {
        return Dtr::getUrl(array_filter([
            'periodId' => PayrollPeriod::query()->find($this->periodId)?->publicKey() ?? $this->periodId,
            'branchId' => Branch::query()->find($this->branchId)?->publicKey() ?? $this->branchId,
            'search' => filled($this->search) ? $this->search : null,
        ], fn ($val) => filled($val)));
    }

    public function getPrintUrl(): ?string
    {
        $employee = $this->getEmployee();
        $period = $this->getSelectedPayrollPeriod();
        $branch = $this->getBranch();

        if (! $employee || ! $period || ! $branch) {
            return null;
        }

        return route('dtr.print', [
            'period' => $period->publicKey(),
            'branch' => $branch->publicKey(),
            'employee' => $employee->publicKey(),
        ]);
    }

    public function getExportUrl(): ?string
    {
        if (! $this->employeeId || ! $this->branchId || ! $this->periodId) {
            return null;
        }

        return route('hr_tools.export.dtr', [
            'employee_id' => $this->employeeId,
            'branch_id' => $this->branchId,
            'period_id' => $this->periodId,
        ]);
    }

    /**
     * @return array{total_entries:int, total_days_work:float, absent_days:float, saturday_absent_hours:int, credited_work_hrs:int, credited_overtime:int, pending_ot_count:int, late:int, undertime:int}
     */
    public function getSummaryMetrics(): array
    {
        $employee = $this->getEmployee();
        $period = $this->getSelectedPayrollPeriod();

        if (! $employee || ! $period) {
            return [
                'total_entries' => 0,
                'total_days_work' => 0.0,
                'absent_days' => 0,
                'saturday_absent_hours' => 0,
                'credited_work_hrs' => 0,
                'credited_overtime' => 0,
                'pending_ot_count' => 0,
                'late' => 0,
                'undertime' => 0,
            ];
        }

        $fingerprint = (string) ($employee->fingerprint_id ?: $employee->uid ?: '');
        if ($fingerprint === '') {
            return [
                'total_entries' => 0,
                'total_days_work' => 0.0,
                'absent_days' => 0,
                'saturday_absent_hours' => 0,
                'credited_work_hrs' => 0,
                'credited_overtime' => 0,
                'pending_ot_count' => 0,
                'late' => 0,
                'undertime' => 0,
            ];
        }

        $records = DtrModel::query()
            ->where('payroll_period_id', $period->id)
            ->where('fingerprint_id', $fingerprint)
            ->when($this->branchId, fn ($q) => $q->where('branch_id', (int) $this->branchId))
            ->get();

        $finalizedRecords = $records
            ->reject(fn (DtrModel $r): bool => $r->requiresAttendanceApproval())
            ->values();

        $workRecords = $finalizedRecords
            ->filter(fn (DtrModel $r): bool => ! (bool) $r->is_absent
                && blank($r->leave_id)
                && ! in_array($r->schedule_type, ['Absent', 'Leave', 'Overtime'], true)
                && filled($r->date_in))
            ->values();

        $attendanceUnits = app(DtrAttendanceUnitService::class);
        $workDayUnits = $attendanceUnits->attendanceDays($workRecords);
        $absence = $attendanceUnits->absenceBreakdown($finalizedRecords);
        $leaveDayCount = $finalizedRecords
            ->filter(fn (DtrModel $r): bool => filled($r->leave_id)
                || $r->entry_source === DtrDayPartService::SOURCE_LEAVE
                || $r->schedule_type === 'Leave')
            ->sum(function (DtrModel $r): float {
                $dayPart = app(DtrAttendanceUnitService::class)->dayPartForRecord($r);

                return in_array($dayPart, [DtrDayPartService::MORNING, DtrDayPartService::AFTERNOON], true) ? 0.5 : 1.0;
            });

        $pendingOtCount = $records->filter(fn (DtrModel $r): bool => $r->overtime_status === 'Pending'
            && (((int) $r->overtime >= 30) || ((int) $r->early_clock_in >= 30)))->count();

        return [
            'total_entries' => $records->count(),
            'total_days_work' => (float) ($workDayUnits + $leaveDayCount),
            'absent_days' => $absence['weekday_days'],
            'saturday_absent_hours' => $absence['saturday_hours'],
            'credited_work_hrs' => (int) $finalizedRecords->sum('credited_work_hrs'),
            'credited_overtime' => (int) $finalizedRecords->sum('credited_overtime'),
            'pending_ot_count' => $pendingOtCount,
            'late' => (int) $finalizedRecords->sum('late'),
            'undertime' => (int) $finalizedRecords->sum('undertime'),
        ];
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => $this->getReturnUrl()),
        ];
    }

    #[Override]
    protected function getFooterWidgets(): array
    {
        return [
            DtrManageTable::class,
        ];
    }
}
