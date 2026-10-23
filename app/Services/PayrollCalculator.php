<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\Holiday;
use App\Models\HolidayEmployeeExclusion;
use App\Models\PayrollCalculationSetting;
use App\Models\PayrollPeriod;
use App\Models\PayrollPeriodBranchExclusion;
use App\Models\PayrollPeriodEmployeeAdjustment;
use App\Models\PayrollPeriodEmployeeExclusion;
use App\Models\PayrollSnapshot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollCalculator
{
    /** @var array<string, int|null>|null */
    private ?array $legacySnapshotDepartmentIds = null;

    public const REGULAR_WORK_DAYS_PER_MONTH = 26;

    public const REGULAR_HALF_MONTH_DAYS = 13;

    public const DEFAULT_DEDUCTION_TITLES = [
        'shortages' => 'SHORTAGES',
        'uniform' => 'COMPANY UNIFORM',
        'sss_loan' => 'SSS LOAN',
        'sss_ee' => 'SSS EE',
        'hdmf_loan' => 'HDMF LOAN',
        'hdmf_ee' => 'HDMF EE',
        'phic_ee' => 'PHIC EE',
    ];

    public function defaultPeriod(): ?PayrollPeriod
    {
        return PayrollPeriod::query()->newestFirst()->first();
    }

    public function periodOptions(): array
    {
        return PayrollPeriod::query()
            ->newestFirst()
            ->pluck('title', 'id')
            ->all();
    }

    public function employeesQuery(?int $branchId = null): Builder
    {
        return Employee::query()
            ->with(['user', 'designation', 'department', 'branch', 'employeeDeductions.deduction'])
            ->activeEmployment()
            ->whereHas('user', fn (Builder $query) => $query->where('role', 'employee'))
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->orderBy('lastname')
            ->orderBy('firstname');
    }

    public function rows(PayrollPeriod $period, ?int $branchId = null): Collection
    {
        if ((bool) $period->is_locked) {
            return $this->snapshotRows($period, $branchId);
        }

        return $this->liveRows($period, $branchId);
    }

    public function rowsForScope(PayrollPeriod $period, string $scope, ?int $scopeId = null): Collection
    {
        if ($scope === 'branch') {
            return $scopeId ? $this->rows($period, $scopeId) : collect();
        }

        if ($scope === 'department') {
            return $scopeId ? $this->rows($period)
                ->filter(fn (array $row): bool => (int) ($row['department_id'] ?? 0) === $scopeId)
                ->values() : collect();
        }

        return $scope === 'all' ? $this->rows($period) : collect();
    }

    public function snapshotPeriod(PayrollPeriod $period): void
    {
        $rows = $this->liveRows($period);

        DB::transaction(function () use ($period, $rows): void {
            PayrollSnapshot::query()
                ->where('payroll_period_id', $period->id)
                ->delete();

            foreach ($rows as $row) {
                PayrollSnapshot::create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $row['employee_id'] ?? null,
                    'branch_id' => $row['branch_id'] ?? null,
                    'row_number' => $row['number'] ?? 0,
                    'data' => $row,
                ]);
            }
        });
    }

    protected function liveRows(PayrollPeriod $period, ?int $branchId = null): Collection
    {
        $employees = $this->employeesForPeriod($period, $branchId)
            ->get()
            ->values();

        $this->preloadEmployeesForPeriod($employees, $period);

        return $employees
            ->map(fn (Employee $employee, int $index): array => $this->row($employee, $period, $index + 1));
    }

    /**
     * Batch-preload all period-specific payroll data onto a collection of Employee models
     * to avoid N+1 queries when computing rows across a branch or full payroll period.
     *
     * @param  Collection<int, Employee>  $employees
     */
    public function preloadEmployeesForPeriod(Collection $employees, PayrollPeriod $period): void
    {
        if ($employees->isEmpty()) {
            return;
        }

        $employeeIds = $employees
            ->pluck('id')
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($employeeIds->isEmpty()) {
            return;
        }

        $settings = PayrollCalculationSetting::forPeriod($period);
        $defaultDeductions = app(EmployeeDeductionService::class)->defaultDeductions();

        $branchIds = $employees
            ->pluck('branch_id')
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $fingerprintIds = $employees
            ->map(fn (Employee $employee): string => (string) ($employee->fingerprint_id ?: $employee->uid))
            ->filter(fn (string $fp): bool => $fp !== '')
            ->flatMap(fn (string $fp): array => ctype_digit($fp) ? [$fp, ltrim($fp, '0') ?: '0'] : [$fp])
            ->uniqueStrict()
            ->values();

        $identityCounts = Employee::query()->activeEmployment()->get(['fingerprint_id', 'uid'])
            ->map(fn (Employee $employee): string => $this->fingerprintIdentity($employee->fingerprint_id ?: $employee->uid))
            ->filter()
            ->countBy();

        $periodDtrs = ($branchIds->isNotEmpty() && $fingerprintIds->isNotEmpty())
            ? Dtr::query()
                ->with(['holiday.type', 'leave'])
                ->where('payroll_period_id', $period->id)
                ->where(function (Builder $query) use ($employeeIds, $fingerprintIds): void {
                    $query->whereIn('employee_id', $employeeIds->all())
                        ->orWhere(function (Builder $legacy) use ($fingerprintIds): void {
                            $legacy->whereNull('employee_id')
                                ->whereIn('fingerprint_id', $fingerprintIds->all());
                        });
                })
                ->get()
            : collect();
        $dtrsByEmployee = $periodDtrs->filter(fn (Dtr $dtr): bool => filled($dtr->employee_id))
            ->groupBy(fn (Dtr $dtr): int => (int) $dtr->employee_id);
        $legacyDtrs = $periodDtrs->filter(fn (Dtr $dtr): bool => blank($dtr->employee_id))
            ->groupBy(fn (Dtr $dtr): string => $this->fingerprintIdentity($dtr->fingerprint_id));

        $adjustmentsByEmployee = PayrollPeriodEmployeeAdjustment::query()
            ->where('payroll_period_id', $period->id)
            ->whereIn('employee_id', $employeeIds->all())
            ->get()
            ->keyBy(fn (PayrollPeriodEmployeeAdjustment $adj): int => (int) $adj->employee_id);

        $loansByEmployee = EmployeeLoan::query()
            ->with(['amortizationStartPayrollPeriod', 'postedPayments.payrollPeriod', 'adjustments.effectivePayrollPeriod'])
            ->whereIn('employee_id', $employeeIds->all())
            ->whereIn('status', [EmployeeLoan::STATUS_ACTIVE, EmployeeLoan::STATUS_PAID])
            ->get()
            ->groupBy(fn (EmployeeLoan $loan): int => (int) $loan->employee_id);

        $exclusionsByEmployee = HolidayEmployeeExclusion::query()
            ->whereIn('employee_id', $employeeIds->all())
            ->get()
            ->groupBy(fn (HolidayEmployeeExclusion $ex): int => (int) $ex->employee_id);

        $holidaysByBranch = [];

        if (filled($period->date_start) && filled($period->date_end) && $branchIds->isNotEmpty()) {
            $holidaysByBranch = app(HolidayResolver::class)->holidaysForRangeByBranches(
                $period->date_start,
                $period->date_end,
                $branchIds->all(),
            );
        }

        foreach ($employees as $employee) {
            $empId = (int) $employee->id;
            $fpId = (string) ($employee->fingerprint_id ?: $employee->uid);
            $dtrKey = $this->fingerprintIdentity($fpId);
            $legacyForEmployee = $legacyDtrs->get($dtrKey) ?? collect();
            if ((int) $identityCounts->get($dtrKey, 0) !== 1) {
                $legacyForEmployee = $legacyForEmployee->where('branch_id', $employee->branch_id);
            }

            $employee->setRelation('preloadedPayrollSettings', $settings);
            $employee->setRelation('preloadedDefaultDeductions', $defaultDeductions);
            $employee->setRelation(
                'preloadedDtrs',
                ($dtrsByEmployee->get($empId) ?? collect())
                    ->concat(filled($fpId) ? $legacyForEmployee : collect())
                    ->unique(fn (Dtr $dtr): string => implode('|', [
                        $dtr->date_in, $dtr->time_in, $dtr->date_out, $dtr->time_out, $dtr->schedule_type,
                    ]))
            );
            $employee->setRelation('preloadedPayrollAdjustment', $adjustmentsByEmployee->get($empId));
            $employee->setRelation('preloadedPayrollLoans', $loansByEmployee->get($empId) ?? collect());
            $employee->setRelation('preloadedHolidayExclusions', $exclusionsByEmployee->get($empId) ?? collect());

            if (isset($holidaysByBranch[(int) $employee->branch_id])) {
                $employee->setRelation('preloadedBranchHolidays', $holidaysByBranch[(int) $employee->branch_id]);
            }
        }
    }

    public function includedEmployeeIds(PayrollPeriod $period, ?int $branchId = null): Collection
    {
        return $this->employeesForPeriod($period, $branchId)
            ->pluck('employees.id');
    }

    protected function employeesForPeriod(PayrollPeriod $period, ?int $branchId = null): Builder
    {
        $excludedEmployeeIds = PayrollPeriodEmployeeExclusion::query()
            ->where('payroll_period_id', $period->id)
            ->pluck('employee_id')
            ->all();

        $excludedBranchIds = $this->excludedBranchIds($period);

        return $this->employeesQuery($branchId)
            ->when($excludedEmployeeIds !== [], fn (Builder $query) => $query->whereNotIn('employees.id', $excludedEmployeeIds))
            ->when($excludedBranchIds !== [], fn (Builder $query) => $query->whereNotIn('employees.branch_id', $excludedBranchIds));
    }

    public function excludedBranchIds(PayrollPeriod $period): array
    {
        return PayrollPeriodBranchExclusion::query()
            ->where('payroll_period_id', $period->id)
            ->pluck('branch_id')
            ->all();
    }

    public function branchOptionsForPeriod(?PayrollPeriod $period): array
    {
        $excludedBranchIds = $period ? $this->excludedBranchIds($period) : [];

        return Branch::query()
            ->when($excludedBranchIds !== [], fn (Builder $query) => $query->whereNotIn('id', $excludedBranchIds))
            ->orderBy('branch_name')
            ->pluck('branch_name', 'id')
            ->all();
    }

    public function isBranchExcluded(PayrollPeriod $period, ?int $branchId): bool
    {
        if (blank($branchId)) {
            return false;
        }

        return PayrollPeriodBranchExclusion::query()
            ->where('payroll_period_id', $period->id)
            ->where('branch_id', $branchId)
            ->exists();
    }

    public function isEmployeeExcluded(PayrollPeriod $period, ?int $employeeId): bool
    {
        if (blank($employeeId)) {
            return false;
        }

        return PayrollPeriodEmployeeExclusion::query()
            ->where('payroll_period_id', $period->id)
            ->where('employee_id', $employeeId)
            ->exists();
    }

    public function isEmployeePayrollExcluded(PayrollPeriod $period, Employee $employee): bool
    {
        return $this->isBranchExcluded($period, $employee->branch_id)
            || $this->isEmployeeExcluded($period, $employee->id);
    }

    protected function hasSnapshots(PayrollPeriod $period, ?int $branchId = null): bool
    {
        return PayrollSnapshot::query()
            ->where('payroll_period_id', $period->id)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->exists();
    }

    protected function snapshotRows(PayrollPeriod $period, ?int $branchId = null): Collection
    {
        return PayrollSnapshot::query()
            ->where('payroll_period_id', $period->id)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->orderBy('row_number')
            ->get()
            ->map(fn (PayrollSnapshot $snapshot): array => $this->snapshotData($snapshot));
    }

    public function employeeRow(Employee $employee, PayrollPeriod $period, int $number = 1): array
    {
        if ((bool) $period->is_locked) {
            $snapshot = PayrollSnapshot::query()
                ->where('payroll_period_id', $period->id)
                ->where('employee_id', $employee->id)
                ->first();

            if ($snapshot) {
                return $this->snapshotData($snapshot);
            }
        }

        return $this->row($employee, $period, $number);
    }

    public function snapshotData(PayrollSnapshot $snapshot): array
    {
        $data = (array) $snapshot->data;

        if (! array_key_exists('employee_id', $data) && filled($snapshot->employee_id)) {
            $data['employee_id'] = (int) $snapshot->employee_id;
        }

        if (! array_key_exists('branch_id', $data) && filled($snapshot->branch_id)) {
            $data['branch_id'] = (int) $snapshot->branch_id;
        }

        // Older locked snapshots stored the department name but not its ID.
        // Resolve only an unambiguous name; never recalculate finalized pay.
        if (! array_key_exists('department_id', $data) && filled($data['department'] ?? null)) {
            $name = mb_strtolower(trim((string) $data['department']));
            $this->legacySnapshotDepartmentIds ??= Department::withTrashed()
                ->get(['id', 'name'])
                ->groupBy(fn (Department $department): string => mb_strtolower(trim($department->name)))
                ->map(fn (Collection $departments): ?int => $departments->count() === 1
                    ? (int) $departments->first()->id : null)
                ->all();
            $data['department_id'] = $this->legacySnapshotDepartmentIds[$name] ?? null;
        }

        return [
            'shift3_premium' => 0.0,
            'paid_leave_days' => 0.0,
            'unpaid_leave_days' => 0.0,
            'unpaid_leave' => 0.0,
            ...$data,
        ];
    }

    public function row(Employee $employee, PayrollPeriod $period, int $number = 1): array
    {
        $employee->loadMissing(['designation', 'department', 'branch', 'employeeDeductions.deduction']);

        /** @var PayrollCalculationSetting $settings */
        $settings = $employee->relationLoaded('preloadedPayrollSettings')
            ? $employee->getRelation('preloadedPayrollSettings')
            : PayrollCalculationSetting::forPeriod($period);

        $dtrs = $this->finalizedDtrs($this->dtrs($employee, $period), $employee, $period);
        $rateType = $this->rateType($employee);
        $isDaily = $rateType === 'Daily';
        $workDaysPerMonth = $settings->divisor('regular_work_days_per_month');
        $halfMonthDays = $settings->value('regular_half_month_days');
        $workHoursPerDay = $settings->divisor('work_hours_per_day');
        $halfDayValue = $settings->value('half_day_work_day_value');
        $overtimeMultiplier = $settings->value('overtime_rate_multiplier');
        $ratePerDay = $this->ratePerDay($employee, $dtrs, $settings);
        $monthlyRate = $isDaily ? null : $this->money(
            $employee->monthly_rate ?: ($ratePerDay * $workDaysPerMonth));
        $halfMonthPay = $isDaily ? null : $this->money(($monthlyRate ?? 0) / 2);
        $ratePerHour = $ratePerDay / $workHoursPerDay;
        $leaveTotals = app(LeavePayService::class)->totals($dtrs, $workHoursPerDay, $halfDayValue);

        $wholeAbsenceMinutes = $this->wholeAbsenceMinutes($dtrs, $workHoursPerDay);
        $halfDayAbsenceMinutes = $this->halfDayAbsenceMinutes($dtrs, $workHoursPerDay, $halfDayValue);

        if (! $isDaily) {
            $halfDayAbsenceMinutes += $this->inferredRegularHalfDayAbsenceMinutes(
                $dtrs,
                $workHoursPerDay,
                $halfDayValue,
            );
        }

        $absenceDayValue = ($wholeAbsenceMinutes + $halfDayAbsenceMinutes + $leaveTotals['unpaid_minutes']) / ($workHoursPerDay * 60);
        $dtrWorkDays = $isDaily ? $this->workedDtrEntries($dtrs) + $leaveTotals['paid_days'] : $this->workedDtrDays($dtrs);
        $daysWorked = $isDaily
            ? max(0, $dtrWorkDays)
            : max(0, $halfMonthDays - $absenceDayValue);

        $basePay = $this->basePay($isDaily, $ratePerDay, $daysWorked, $monthlyRate, $workDaysPerMonth, $halfMonthDays);

        $deductibleDtrs = $this->deductibleDtrs($dtrs);
        $lateMinutes = $this->sumMinutes($deductibleDtrs, 'late');
        $undertimeMinutes = $this->sumMinutes($deductibleDtrs, 'undertime');
        $overtimeMinutes = $this->sumMinutes($dtrs, 'credited_overtime');
        $overtimeHours = $this->hours($overtimeMinutes);
        $overtimeAmount = $this->minutePay($overtimeMinutes, $ratePerHour, $overtimeMultiplier);
        $shift3Premium = app(Shift3PremiumService::class)->total($dtrs, $ratePerDay, $workHoursPerDay, ! $isDaily);
        $regularHolidayAmount = $this->money(
            $this->holidayAmount($employee, $dtrs, $ratePerDay, 'regular', $settings)
            + $this->unworkedRegularHolidayAmount($employee, $period, $dtrs, $ratePerDay, $settings)
        );
        $specialHolidayAmount = $this->holidayAmount($employee, $dtrs, $ratePerDay, 'special', $settings);
        $allowance = $this->money($employee->allowance ?? 0);
        $payrollAdjustment = $this->payrollAdjustment($employee, $period);
        $salaryAdjustment = $this->money($payrollAdjustment?->salary_adjustment ?? 0);
        $shortages = $this->money($payrollAdjustment?->shortages ?? 0);

        $undertimeAmount = $this->minutePay($undertimeMinutes, $ratePerHour);
        $lateAmount = $this->minutePay($lateMinutes, $ratePerHour);
        $halfDayAmount = $isDaily ? 0.0 : $this->money(($halfDayAbsenceMinutes / 60) * $ratePerHour);
        $absentAmount = $isDaily ? 0.0 : $this->money(($wholeAbsenceMinutes / 60) * $ratePerHour);
        $unpaidLeaveAmount = $isDaily ? 0.0 : $this->money(($leaveTotals['unpaid_minutes'] / 60) * $ratePerHour);
        $deductions = $this->deductions($employee);
        $loanPayment = $this->loanPaymentDeduction($employee, $period);

        $grossPay = $this->money(
            $basePay
            + $salaryAdjustment
            + $allowance
            + $overtimeAmount
            + $shift3Premium
            + $regularHolidayAmount
            + $specialHolidayAmount
        );

        $totalDeductions = $this->money(
            $undertimeAmount
            + $halfDayAmount
            + $absentAmount
            + $unpaidLeaveAmount
            + $lateAmount
            + $shortages
            + $deductions['uniform']
            + $deductions['other_deductions']
            + $deductions['sss_loan']
            + $deductions['sss_ee']
            + $deductions['hdmf_loan']
            + $deductions['hdmf_ee']
            + $deductions['phic_ee']
            + $loanPayment
        );

        return [
            'number' => $number,
            'employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'department_id' => $employee->department_id,
            'payment_type' => $employee->payment_type ?: '',
            'bank_id_no' => $employee->bank_id_no ?? '',
            'name' => $this->employeeName($employee),
            'designation' => $employee->designation?->title ?? '',
            'department' => $employee->department?->name ?? '',
            'branch' => $employee->branch?->branch_name ?? '',
            'rate' => $rateType,
            'monthly_rate' => $monthlyRate,
            'half_month_pay' => $halfMonthPay,
            'rate_per_day' => $ratePerDay,
            'rate_per_hour' => $ratePerHour,
            'days_worked' => $this->number($daysWorked),
            'total_pay' => $isDaily ? $basePay : null,
            'salary_adjustment' => $salaryAdjustment,
            'allowance' => $allowance,
            'overtime_minutes' => $overtimeMinutes,
            'overtime_hours' => $overtimeHours,
            'overtime_amount' => $overtimeAmount,
            'shift3_premium' => $shift3Premium,
            'regular_holiday' => $regularHolidayAmount,
            'special_holiday' => $specialHolidayAmount,
            'gross_pay' => $grossPay,
            'undertime_minutes' => $undertimeMinutes,
            'undertime_amount' => $undertimeAmount,
            'halfday' => $halfDayAmount,
            'absent' => $absentAmount,
            'paid_leave_days' => $this->number($leaveTotals['paid_days']),
            'unpaid_leave_days' => $this->number($leaveTotals['unpaid_days']),
            'unpaid_leave' => $unpaidLeaveAmount,
            'late' => $lateAmount,
            'shortages' => $shortages,
            'uniform' => $deductions['uniform'],
            'other_deductions' => $deductions['other_deductions'],
            'sss_loan' => $deductions['sss_loan'],
            'sss_ee' => $deductions['sss_ee'],
            'hdmf_loan' => $deductions['hdmf_loan'],
            'hdmf_ee' => $deductions['hdmf_ee'],
            'phic_ee' => $deductions['phic_ee'],
            'loan_payment' => $loanPayment,
            'total_deductions' => $totalDeductions,
            'net_pay' => $this->money($grossPay - $totalDeductions),
            'signature' => '',
        ];
    }

    public function branchSummaryRows(PayrollPeriod $period): Collection
    {
        return $this->summaryRows($period, 'branch');
    }

    /**
     * Ensure each employee appears at most once in the payroll collection using their unique employee_id.
     */
    public function deduplicateEmployeeRows(Collection $rows): Collection
    {
        $seenEmployeeIds = [];

        return $rows->filter(function (array $row) use (&$seenEmployeeIds): bool {
            $employeeId = $row['employee_id'] ?? null;

            if (filled($employeeId)) {
                $id = (int) $employeeId;

                if (isset($seenEmployeeIds[$id])) {
                    return false;
                }

                $seenEmployeeIds[$id] = true;
            }

            return true;
        })->values();
    }

    /**
     * Resolve summary rows with support for simultaneous Branch and Department selection
     * and Department Priority deduplication:
     *   Selected Department -> Selected Branch -> Exclude Duplicate Employee
     */
    public function summaryRows(
        PayrollPeriod $period,
        string $groupBy = 'branch',
        array $includedIds = [],
        ?array $branchIds = null,
        ?array $departmentIds = null,
    ): Collection {
        if (! in_array($groupBy, ['branch', 'department'], true)) {
            return collect();
        }

        // Normalize branch & department selections, preserving legacy ($groupBy, $includedIds) calls
        $legacyIds = array_values(array_unique(array_filter(array_map('intval', $includedIds))));

        $selectedBranchIds = $branchIds !== null
            ? array_values(array_unique(array_filter(array_map('intval', $branchIds))))
            : ($groupBy === 'branch' ? $legacyIds : []);

        $selectedDepartmentIds = $departmentIds !== null
            ? array_values(array_unique(array_filter(array_map('intval', $departmentIds))))
            : ($groupBy === 'department' ? $legacyIds : []);

        $hasSelection = $selectedBranchIds !== [] || $selectedDepartmentIds !== [];

        $assignAndGroup = function (Collection $rawRows) use (
            $groupBy,
            $hasSelection,
            $selectedBranchIds,
            $selectedDepartmentIds
        ): Collection {
            $uniqueRows = $this->deduplicateEmployeeRows($rawRows);

            if (! $hasSelection) {
                $idKey = $groupBy.'_id';
                $labelDefault = $groupBy === 'department' ? 'No Department' : 'No Branch';

                return $uniqueRows
                    ->map(function (array $row) use ($groupBy, $idKey, $labelDefault): array {
                        $groupId = (int) ($row[$idKey] ?? 0);
                        $row['_summary_group_key'] = $groupBy.':'.$groupId;
                        $row['_summary_group_type'] = $groupBy;
                        $row['_summary_group_id'] = $groupId;
                        $row['_summary_group_label'] = ($row[$groupBy] ?? '') ?: $labelDefault;

                        return $row;
                    })
                    ->groupBy('_summary_group_key');
            }

            // Priority Rule: Selected Department -> Selected Branch -> Exclude
            $assignedRows = $uniqueRows
                ->map(function (array $row) use ($selectedBranchIds, $selectedDepartmentIds): ?array {
                    $deptId = (int) ($row['department_id'] ?? 0);
                    $branchId = (int) ($row['branch_id'] ?? 0);

                    if ($selectedDepartmentIds !== [] && in_array($deptId, $selectedDepartmentIds, true)) {
                        $row['_summary_group_key'] = 'department:'.$deptId;
                        $row['_summary_group_type'] = 'department';
                        $row['_summary_group_id'] = $deptId;
                        $row['_summary_group_label'] = ($row['department'] ?? '') ?: 'No Department';

                        return $row;
                    }

                    if ($selectedBranchIds !== [] && in_array($branchId, $selectedBranchIds, true)) {
                        $row['_summary_group_key'] = 'branch:'.$branchId;
                        $row['_summary_group_type'] = 'branch';
                        $row['_summary_group_id'] = $branchId;
                        $row['_summary_group_label'] = ($row['branch'] ?? '') ?: 'No Branch';

                        return $row;
                    }

                    return null;
                })
                ->filter()
                ->values();

            // Group and order so Department groupings appear first, followed by Branch groupings
            return $assignedRows
                ->sortBy(fn (array $row): int => $row['_summary_group_type'] === 'department' ? 0 : 1)
                ->groupBy('_summary_group_key');
        };

        $previousPeriod = $this->previousPeriod($period);
        $previousNetByGroup = $previousPeriod
            ? $assignAndGroup($this->rows($previousPeriod))
                ->map(fn (Collection $rows): float => $this->money($rows->sum('net_pay')))
            : collect();

        return $assignAndGroup($this->rows($period))
            ->map(function (Collection $rows, string $groupKey) use ($previousNetByGroup): array {
                $first = $rows->first();
                $netPay = $this->money($rows->sum('net_pay'));
                $previousNetPay = $this->money($previousNetByGroup->get($groupKey, 0));

                return [
                    'group_key' => $groupKey,
                    'group_type' => $first['_summary_group_type'] ?? 'branch',
                    'group_id' => $first['_summary_group_id'] ?? 0,
                    'branch' => $first['_summary_group_label'] ?? 'No Branch',
                    'workers' => $rows->count(),
                    'gross_pay' => $this->money($rows->sum('gross_pay')),
                    'total_deductions' => $this->money($rows->sum('total_deductions')),
                    'net_pay' => $netPay,
                    'previous_net_pay' => $previousNetPay,
                    'variance' => $this->money($netPay - $previousNetPay),
                    'employees' => $rows->map(fn (array $r): array => [
                        'employee_id' => $r['employee_id'] ?? null,
                        'name' => $r['name'] ?? ($r['employee_name'] ?? '-'),
                        'gross_pay' => $this->money((float) ($r['gross_pay'] ?? 0)),
                        'net_pay' => $this->money((float) ($r['net_pay'] ?? 0)),
                    ])->values()->all(),
                ];
            })
            ->values()
            ->map(function (array $row, int $index): array {
                $row['number'] = $index + 1;

                return $row;
            });
    }

    public function payrollHeaders(): array
    {
        return [
            'number' => '#',
            'bank_id_no' => 'BANK ID NO.',
            'name' => 'NAME',
            'designation' => 'DESIGNATION',
            'branch' => 'BRANCH',
            'rate' => 'RATE',
            'monthly_rate' => 'MONTHLY RATE',
            'half_month_pay' => 'HALF MONTH PAY',
            'rate_per_day' => 'RATE PER DAY',
            'rate_per_hour' => 'RATE PER HOUR',
            'days_worked' => 'DAYS WORK',
            'total_pay' => 'TOTAL PAY (DAILY RATE ONLY)',
            'salary_adjustment' => 'SALARY ADJUSTMENT',
            'allowance' => 'ALLOWANCE',
            'overtime_hours' => 'OVERTIME HRS',
            'overtime_amount' => 'OVERTIME AMOUNT',
            'shift3_premium' => '10%',
            'regular_holiday' => 'REGULAR HOLIDAY',
            'special_holiday' => 'SPECIAL HOLIDAY',
            'gross_pay' => 'GROSS PAY',
            'undertime_minutes' => 'UNDERTIME MINUTES',
            'undertime_amount' => 'UNDERTIME AMOUNT',
            'halfday' => 'HALFDAY',
            'absent' => 'ABSENT',
            'unpaid_leave' => 'UNPAID LEAVE',
            'late' => 'LATE',
            'shortages' => 'SHORTAGES',
            'uniform' => 'UNIFORM',
            'other_deductions' => 'OTHER DEDUCTIONS',
            'loan_payment' => 'LOAN PAYMENT',
            'sss_loan' => 'SSS LOAN',
            'sss_ee' => 'SSS EE',
            'hdmf_loan' => 'HDMF LOAN',
            'hdmf_ee' => 'HDMF EE',
            'phic_ee' => 'PHIC EE',
            'total_deductions' => 'TOTAL DEDUCTIONS',
            'net_pay' => 'NET PAY',
            'signature' => 'SIGNATURE',
        ];
    }

    public function summaryHeaders(
        string $groupBy = 'branch',
        array $branchIds = [],
        array $departmentIds = [],
    ): array {
        $hasBranches = array_filter($branchIds) !== [];
        $hasDepartments = array_filter($departmentIds) !== [];

        if ($hasBranches && $hasDepartments) {
            $groupLabel = 'DEPARTMENTS / BRANCHES';
        } elseif ($hasDepartments) {
            $groupLabel = 'DEPARTMENTS';
        } elseif ($hasBranches) {
            $groupLabel = 'BRANCHES';
        } else {
            $groupLabel = $groupBy === 'department' ? 'DEPARTMENTS' : 'BRANCHES';
        }

        return [
            'number' => '#',
            'branch' => $groupLabel,
            'workers' => 'NO. OF WORKERS',
            'gross_pay' => 'GROSS PAY',
            'total_deductions' => 'LESS: TOTAL DEDUCTIONS',
            'net_pay' => 'NET PAY',
            'previous_net_pay' => 'PREVIOUS NET PAY',
            'variance' => 'VARIANCE',
        ];
    }

    public function previousPeriod(PayrollPeriod $period): ?PayrollPeriod
    {
        if (blank($period->date_start)) {
            return PayrollPeriod::query()
                ->whereKeyNot($period->id)
                ->where('id', '<', $period->id)
                ->newestFirst()
                ->first();
        }

        return PayrollPeriod::query()
            ->whereDate('date_start', '<', $period->date_start)
            ->latest('date_start')
            ->first()
            ?: PayrollPeriod::query()
                ->whereKeyNot($period->id)
                ->where('id', '<', $period->id)
                ->newestFirst()
                ->first();
    }

    protected function dtrs(Employee $employee, PayrollPeriod $period): Collection
    {
        if ($employee->relationLoaded('preloadedDtrs')) {
            return $employee->getRelation('preloadedDtrs') ?? collect();
        }

        $fingerprintId = $employee->fingerprint_id ?: $employee->uid;

        if (blank($fingerprintId) || blank($employee->branch_id)) {
            return collect();
        }

        $identity = $this->fingerprintIdentity($fingerprintId);
        $unique = Employee::query()->activeEmployment()->get(['fingerprint_id', 'uid'])
            ->filter(fn (Employee $candidate): bool => $this->fingerprintIdentity($candidate->fingerprint_id ?: $candidate->uid) === $identity)
            ->count() === 1;
        $variants = ctype_digit((string) $fingerprintId)
            ? array_unique([(string) $fingerprintId, ltrim((string) $fingerprintId, '0') ?: '0'])
            : [(string) $fingerprintId];

        return Dtr::query()
            ->with(['holiday.type', 'leave'])
            ->where('payroll_period_id', $period->id)
            ->where(fn (Builder $query) => $query
                ->where('employee_id', $employee->id)
                ->orWhere(fn (Builder $legacy) => $legacy
                    ->whereNull('employee_id')
                    ->when(! $unique, fn (Builder $query) => $query->where('branch_id', $employee->branch_id))
                    ->whereIn('fingerprint_id', $variants)))
            ->get()
            ->unique(fn (Dtr $dtr): string => implode('|', [
                $dtr->date_in, $dtr->time_in, $dtr->date_out, $dtr->time_out, $dtr->schedule_type,
            ]));
    }

    protected function fingerprintIdentity(mixed $value): string
    {
        $value = trim((string) $value);

        return ctype_digit($value) ? (ltrim($value, '0') ?: '0') : mb_strtolower($value);
    }

    protected function finalizedDtrs(Collection $dtrs, ?Employee $employee = null, ?PayrollPeriod $period = null): Collection
    {
        $finalized = $dtrs
            ->reject(fn (Dtr $dtr): bool => $dtr->requiresAttendanceApproval())
            ->values();
        $finalized = app(LeavePayService::class)->effectiveRecords($finalized);

        if ($employee && $period) {
            return $this->syncDtrHolidaysFromCalendar($employee, $period, $finalized);
        }

        return $finalized;
    }

    protected function syncDtrHolidaysFromCalendar(Employee $employee, PayrollPeriod $period, Collection $dtrs): Collection
    {
        if ($dtrs->isEmpty()) {
            return $dtrs;
        }

        /** @var Collection<string, Holiday>|null $branchHolidays */
        $branchHolidays = $employee->relationLoaded('preloadedBranchHolidays')
            ? $employee->getRelation('preloadedBranchHolidays')
            : ((filled($period->date_start) && filled($period->date_end) && filled($employee->branch_id))
                ? app(HolidayResolver::class)->holidaysForRange($period->date_start, $period->date_end, (int) $employee->branch_id)
                : null);

        if (! $branchHolidays || $branchHolidays->isEmpty()) {
            return $dtrs;
        }

        foreach ($dtrs as $dtr) {
            if ((bool) $dtr->is_holiday || (bool) $dtr->holiday_excluded || blank($dtr->date_in)) {
                continue;
            }

            $dateKey = Carbon::parse($dtr->date_in)->toDateString();
            $holiday = $branchHolidays->get($dateKey);

            if (! $holiday) {
                continue;
            }

            $dtr->is_holiday = true;
            $dtr->holiday_id = $holiday->id;
            $dtr->holiday_type = $holiday->type?->type;
            $dtr->holiday_rate = $holiday->type?->rate;
            $dtr->setRelation('holiday', $holiday);
        }

        return $dtrs;
    }

    protected function rateType(Employee $employee): string
    {
        return Str::contains(Str::lower((string) $employee->rate_type), 'daily') ? 'Daily' : 'Monthly';
    }

    protected function ratePerDay(Employee $employee, ?Collection $dtrs = null, ?PayrollCalculationSetting $settings = null): float
    {
        // Monthly-derived daily rates are stored at two decimals; do not use them as the salary source.
        if ($this->rateType($employee) === 'Monthly' && (float) $employee->monthly_rate > 0) {
            return (float) $employee->monthly_rate / ($settings?->divisor('regular_work_days_per_month') ?? self::REGULAR_WORK_DAYS_PER_MONTH);
        }
        $storedDailyRate = $this->storedDailyRate($dtrs);

        if ($storedDailyRate > 0) {
            return $this->money($storedDailyRate);
        }

        $dailyRate = (float) ($employee->daily_rate ?? 0);

        if ($dailyRate > 0) {
            return $this->money($dailyRate);
        }

        $monthlyRate = (float) ($employee->monthly_rate ?? 0);
        $workDaysPerMonth = $settings?->divisor('regular_work_days_per_month') ?? self::REGULAR_WORK_DAYS_PER_MONTH;

        return $monthlyRate > 0
            ? $this->money($monthlyRate / $workDaysPerMonth)
            : 0.0;
    }

    protected function storedDailyRate(?Collection $dtrs): float
    {
        if (! $dtrs || $dtrs->isEmpty()) {
            return 0.0;
        }

        return (float) ($dtrs
            ->sortBy('date_in')
            ->first(fn (Dtr $dtr): bool => (float) ($dtr->daily_rate ?? 0) > 0)
            ?->daily_rate ?? 0);
    }

    protected function workedDtrDays(Collection $dtrs): float
    {
        return app(DtrAttendanceUnitService::class)->attendanceDays($dtrs);
    }

    protected function workedDtrEntries(Collection $dtrs): float
    {
        return app(DtrAttendanceUnitService::class)->dailyRatePayUnits($dtrs);
    }

    protected function deductibleDtrs(Collection $dtrs): Collection
    {
        return $dtrs
            ->reject(fn (Dtr $dtr): bool => (bool) $dtr->is_absent)
            ->reject(fn (Dtr $dtr): bool => app(LeavePayService::class)->isLeave($dtr))
            ->reject(fn (Dtr $dtr): bool => Str::lower((string) $dtr->schedule_type) === 'overtime');
    }

    protected function wholeAbsenceMinutes(Collection $dtrs, float $workHoursPerDay): float
    {
        return $this->absenceMinutesFor($dtrs, $workHoursPerDay, DtrDayPartService::WHOLE_DAY);
    }

    protected function halfDayAbsenceMinutes(Collection $dtrs, float $workHoursPerDay, float $halfDayValue): float
    {
        $fallbackMinutes = $workHoursPerDay * 60 * $halfDayValue;

        return $dtrs
            ->filter(fn (Dtr $dtr): bool => (bool) $dtr->is_absent)
            ->reject(fn (Dtr $dtr): bool => app(DtrDayPartService::class)->normalize($dtr->day_part ?? null) === DtrDayPartService::WHOLE_DAY)
            ->sum(fn (Dtr $dtr): float => Carbon::parse($dtr->date_in)->isSaturday()
                ? 180.0
                : (float) ($dtr->absence_minutes ?: $fallbackMinutes));
    }

    protected function inferredRegularHalfDayAbsenceMinutes(
        Collection $dtrs,
        float $workHoursPerDay,
        float $halfDayValue,
    ): float {
        $fallbackMinutes = $workHoursPerDay * 60 * $halfDayValue;
        $dayPartService = app(DtrDayPartService::class);
        $attendanceUnits = app(DtrAttendanceUnitService::class);

        return $dtrs
            ->filter(fn (Dtr $dtr): bool => filled($dtr->date_in))
            ->groupBy(fn (Dtr $dtr): string => Carbon::parse($dtr->date_in)->toDateString())
            ->sum(function (Collection $dateRecords) use ($fallbackMinutes, $dayPartService, $attendanceUnits): float {
                $workedRegularParts = $dateRecords
                    ->reject(fn (Dtr $dtr): bool => (bool) $dtr->is_absent || filled($dtr->leave_id))
                    ->filter(fn (Dtr $dtr): bool => $dayPartService->isRegularScheduleType($dtr->schedule_type))
                    ->map(fn (Dtr $dtr): string => $attendanceUnits->dayPartForRecord($dtr))
                    ->unique();

                if ($workedRegularParts->contains(DtrDayPartService::WHOLE_DAY)) {
                    return 0.0;
                }
                $workedRegularParts = $workedRegularParts
                    ->filter(fn (string $part): bool => in_array($part, [
                        DtrDayPartService::MORNING,
                        DtrDayPartService::AFTERNOON,
                    ], true))
                    ->unique()
                    ->values();

                if ($workedRegularParts->count() !== 1) {
                    return 0.0;
                }

                $missingPart = $workedRegularParts->first() === DtrDayPartService::MORNING
                    ? DtrDayPartService::AFTERNOON
                    : DtrDayPartService::MORNING;

                $missingPartCovered = $dateRecords->contains(function (Dtr $dtr) use ($missingPart, $dayPartService): bool {
                    $isLeave = filled($dtr->leave_id)
                        || $dtr->entry_source === DtrDayPartService::SOURCE_LEAVE
                        || Str::lower((string) $dtr->schedule_type) === 'leave';
                    $isAbsence = (bool) $dtr->is_absent
                        || $dtr->entry_source === DtrDayPartService::SOURCE_ABSENCE;

                    if (! $isLeave && ! $isAbsence) {
                        return false;
                    }

                    $coveredPart = $dayPartService->normalize($dtr->day_part);

                    return $coveredPart === DtrDayPartService::WHOLE_DAY || $coveredPart === $missingPart;
                });

                return $missingPartCovered ? 0.0 : $fallbackMinutes;
            });
    }

    protected function absenceMinutesFor(Collection $dtrs, float $workHoursPerDay, string $dayPart): float
    {
        $fallbackMinutes = $workHoursPerDay * 60;

        return $dtrs
            ->filter(fn (Dtr $dtr): bool => (bool) $dtr->is_absent)
            ->filter(fn (Dtr $dtr): bool => app(DtrDayPartService::class)->normalize($dtr->day_part ?? null) === $dayPart)
            ->sum(fn (Dtr $dtr): float => Carbon::parse($dtr->date_in)->isSaturday()
                ? 180.0
                : (float) ($dtr->absence_minutes ?: $fallbackMinutes));
    }

    protected function sumMinutes(Collection $dtrs, string $column): float
    {
        return $this->number($dtrs->sum(fn (Dtr $dtr): float => (float) ($dtr->{$column} ?? 0)));
    }

    protected function hours(float $minutes): float
    {
        return $this->number($minutes / 60);
    }

    protected function holidayAmount(Employee $employee, Collection $dtrs, float $ratePerDay, string $type, PayrollCalculationSetting $settings): float
    {
        return $this->money(
            $dtrs
                ->filter(fn (Dtr $dtr): bool => (bool) $dtr->is_holiday)
                ->reject(fn (Dtr $dtr): bool => app(LeavePayService::class)->isLeave($dtr))
                ->reject(fn (Dtr $dtr): bool => $this->isHolidayExcludedForPayroll($employee, $dtr))
                ->filter(function (Dtr $dtr) use ($type): bool {
                    $holidayType = Str::lower((string) ($dtr->holiday_type ?: $dtr->holiday?->type?->type));
                    $holidayRate = (float) ($dtr->holiday_rate ?: $dtr->holiday?->type?->rate ?: 0);

                    if ($type === 'regular') {
                        return Str::contains($holidayType, 'regular') || $holidayRate >= 100;
                    }

                    return Str::contains($holidayType, 'special') || ($holidayRate > 0 && $holidayRate < 100);
                })
                ->sum(function (Dtr $dtr) use ($ratePerDay, $settings, $type): float {
                    $holidayRate = $this->holidayRate($dtr, $settings, $type);
                    $dailyRate = $this->rateType($employee) === 'Monthly' && (float) $employee->monthly_rate > 0
                        ? $ratePerDay : (float) ($dtr->daily_rate ?: $ratePerDay);
                    $ratePerHour = $dailyRate / $settings->divisor('work_hours_per_day');
                    $creditedWorkMinutes = (float) ($dtr->credited_work_hrs ?? 0);
                    $creditedOvertimeMinutes = $this->holidayOvertimeMinutes($dtr, $creditedWorkMinutes);
                    $regularHolidayMinutes = max(0, $creditedWorkMinutes - $creditedOvertimeMinutes);
                    $baseWorkedPay = ($regularHolidayMinutes / 60) * $ratePerHour;
                    $premiumMultiplier = $holidayRate >= 100
                        ? max(0, ($holidayRate - 100) / 100)
                        : $holidayRate / 100;
                    $holidayOvertimePremium = ($creditedOvertimeMinutes / 60)
                        * $ratePerHour
                        * ($settings->value('holiday_overtime_premium_rate') / 100);

                    return ($baseWorkedPay * $premiumMultiplier) + $holidayOvertimePremium;
                })
        );
    }

    protected function holidayOvertimeMinutes(Dtr $dtr, float $creditedWorkMinutes): float
    {
        $scheduleType = Str::lower((string) $dtr->schedule_type);
        $creditedOvertime = max(0, (float) ($dtr->credited_overtime ?? 0));

        if ($scheduleType === 'overtime') {
            return max($creditedOvertime, $creditedWorkMinutes);
        }

        return min($creditedWorkMinutes, $creditedOvertime);
    }

    protected function isHolidayExcludedForPayroll(Employee $employee, Dtr $dtr): bool
    {
        if ((bool) $dtr->holiday_excluded) {
            return true;
        }

        if (! $dtr->holiday || blank($dtr->date_in)) {
            return false;
        }

        return app(HolidayEntitlementService::class)
            ->isExcluded($dtr->holiday, $employee, $dtr->date_in);
    }

    protected function unworkedRegularHolidayAmount(
        Employee $employee,
        PayrollPeriod $period,
        Collection $dtrs,
        float $ratePerDay,
        PayrollCalculationSetting $settings,
    ): float {
        if (! $settings->enabled('unworked_regular_holiday_pay_enabled')) {
            return 0.0;
        }

        if ($this->rateType($employee) !== 'Daily') {
            return 0.0;
        }

        if (blank($period->date_start) || blank($period->date_end) || blank($employee->branch_id)) {
            return 0.0;
        }

        $dtrDates = $dtrs
            ->filter(fn (Dtr $dtr): bool => filled($dtr->date_in))
            ->map(fn (Dtr $dtr): string => Carbon::parse($dtr->date_in)->toDateString())
            ->unique()
            ->values();

        $preloadedBranchHolidays = $employee->relationLoaded('preloadedBranchHolidays')
            ? $employee->getRelation('preloadedBranchHolidays')
            : null;

        return $this->money(
            app(HolidayEntitlementService::class)
                ->regularHolidaysForEmployeeRange(
                    $employee,
                    $period->date_start,
                    $period->date_end,
                    $employee->branch_id,
                    $preloadedBranchHolidays,
                )
                ->reject(fn ($holiday): bool => $dtrDates->contains((string) $holiday->occurrence_date))
                ->count() * $ratePerDay
        );
    }

    protected function holidayRate(Dtr $dtr, PayrollCalculationSetting $settings, string $type): float
    {
        $holidayRate = (float) ($dtr->holiday_rate ?: $dtr->holiday?->type?->rate ?: 0);

        if ($holidayRate > 0) {
            return $holidayRate;
        }

        return $type === 'regular'
            ? $settings->value('regular_holiday_rate')
            : $settings->value('special_holiday_rate');
    }

    protected function deductions(Employee $employee): array
    {
        $deductions = array_fill_keys(array_keys(self::DEFAULT_DEDUCTION_TITLES), 0.0);
        $deductions['other_deductions'] = 0.0;
        $periodSpecificTitles = [
            $this->normalizeTitle(self::DEFAULT_DEDUCTION_TITLES['shortages']),
        ];
        $titleToKey = collect(self::DEFAULT_DEDUCTION_TITLES)
            ->mapWithKeys(fn (string $title, string $key): array => [$this->normalizeTitle($title) => $key])
            ->all();

        $defaultDeductions = $employee->relationLoaded('preloadedDefaultDeductions')
            ? $employee->getRelation('preloadedDefaultDeductions')
            : null;

        foreach (app(EmployeeDeductionService::class)->activeEmployeeDeductions($employee, $defaultDeductions) as $employeeDeduction) {
            $title = $this->normalizeTitle((string) $employeeDeduction->deduction?->title);
            $amount = $this->money($employeeDeduction->amount ?? 0);

            if (in_array($title, $periodSpecificTitles, true)) {
                continue;
            }

            $key = $titleToKey[$title] ?? null;

            if ($key !== null) {
                $deductions[$key] += $amount;

                continue;
            }

            if (! array_key_exists($title, $titleToKey)) {
                $deductions['other_deductions'] += $amount;
            }
        }

        return collect($deductions)
            ->map(fn (float $value): float => $this->money($value))
            ->all();
    }

    protected function payrollAdjustment(Employee $employee, PayrollPeriod $period): ?PayrollPeriodEmployeeAdjustment
    {
        if ($employee->relationLoaded('preloadedPayrollAdjustment')) {
            return $employee->getRelation('preloadedPayrollAdjustment');
        }

        return PayrollPeriodEmployeeAdjustment::query()
            ->where('payroll_period_id', $period->id)
            ->where('employee_id', $employee->id)
            ->first();
    }

    protected function loanPaymentDeduction(Employee $employee, PayrollPeriod $period): float
    {
        if ($employee->relationLoaded('preloadedPayrollLoans')) {
            /** @var Collection<int, EmployeeLoan> $loans */
            $loans = $employee->getRelation('preloadedPayrollLoans');

            return $this->money(
                $loans->sum(fn (EmployeeLoan $loan): float => $loan->paymentAmountForPeriod($period))
            );
        }

        return $this->money(
            EmployeeLoan::query()
                ->with(['amortizationStartPayrollPeriod', 'postedPayments.payrollPeriod', 'adjustments.effectivePayrollPeriod'])
                ->where('employee_id', $employee->id)
                ->whereIn('status', [EmployeeLoan::STATUS_ACTIVE, EmployeeLoan::STATUS_PAID])
                ->get()
                ->sum(fn (EmployeeLoan $loan): float => $loan->paymentAmountForPeriod($period))
        );
    }

    protected function employeeName(Employee $employee): string
    {
        return trim($employee->lastname.', '.(filled($employee->middlename) ? $employee->middlename.'. ' : '').$employee->firstname);
    }

    protected function normalizeTitle(string $title): string
    {
        return Str::of($title)->upper()->squish()->toString();
    }

    protected function money(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }

    protected function basePay(bool $daily, float $dailyRate, float $daysWorked, ?float $monthlyRate, float $monthlyDays, float $cutoffDays): float
    {
        return $this->money($daily ? $dailyRate * $daysWorked : ($monthlyRate / $monthlyDays) * $cutoffDays);
    }

    protected function minutePay(float $minutes, float $hourlyRate, float $multiplier = 1): float
    {
        return $this->money(($minutes / 60) * $hourlyRate * $multiplier);
    }

    protected function number(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}
