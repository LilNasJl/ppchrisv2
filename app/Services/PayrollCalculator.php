<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\PayrollCalculationSetting;
use App\Models\PayrollPeriod;
use App\Models\PayrollPeriodBranchExclusion;
use App\Models\PayrollPeriodEmployeeExclusion;
use App\Models\PayrollSnapshot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollCalculator
{
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
        if ((bool) $period->is_locked && $this->hasSnapshots($period, $branchId)) {
            return $this->snapshotRows($period, $branchId);
        }

        return $this->liveRows($period, $branchId);
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
        return $this->employeesForPeriod($period, $branchId)
            ->get()
            ->values()
            ->map(fn (Employee $employee, int $index): array => $this->row($employee, $period, $index + 1));
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
            ->map(fn (PayrollSnapshot $snapshot): array => $snapshot->data);
    }

    public function row(Employee $employee, PayrollPeriod $period, int $number = 1): array
    {
        $employee->loadMissing(['designation', 'department', 'branch', 'employeeDeductions.deduction']);

        $settings = PayrollCalculationSetting::forPeriod($period);
        $dtrs = $this->dtrs($employee, $period);
        $rateType = $this->rateType($employee);
        $isDaily = $rateType === 'Daily';
        $workDaysPerMonth = $settings->divisor('regular_work_days_per_month');
        $halfMonthDays = $settings->value('regular_half_month_days');
        $workHoursPerDay = $settings->divisor('work_hours_per_day');
        $halfDayValue = $settings->value('half_day_work_day_value');
        $overtimeMultiplier = $settings->value('overtime_rate_multiplier');
        $ratePerDay = $this->ratePerDay($employee, $dtrs, $settings);
        $hasStoredDailyRate = $this->storedDailyRate($dtrs) > 0;
        $monthlyRate = $isDaily ? null : $this->money($hasStoredDailyRate
            ? $ratePerDay * $workDaysPerMonth
            : ($employee->monthly_rate ?: ($ratePerDay * $workDaysPerMonth)));
        $halfMonthPay = $isDaily ? null : $this->money(($monthlyRate ?? 0) / 2);
        $ratePerHour = $this->money($ratePerDay / $workHoursPerDay);

        $absenceDays = $this->absenceDays($dtrs);
        $halfDayCount = $this->approvedHalfDayCount($employee, $period);
        $dtrWorkDays = $isDaily ? $this->workedDtrEntries($dtrs) : $this->workedDtrDays($dtrs);
        $daysWorked = $isDaily
            ? max(0, $dtrWorkDays - ($halfDayCount * $halfDayValue))
            : max(0, $halfMonthDays - $absenceDays - ($halfDayCount * $halfDayValue));

        $basePay = $isDaily
            ? $this->money($ratePerDay * $daysWorked)
            : $this->money($ratePerDay * $halfMonthDays);

        $deductibleDtrs = $this->deductibleDtrs($dtrs);
        $lateMinutes = $this->sumMinutes($deductibleDtrs, 'late');
        $undertimeMinutes = $this->sumMinutes($deductibleDtrs, 'undertime');
        $overtimeMinutes = $this->sumMinutes($dtrs, 'credited_overtime');
        $overtimeHours = $this->hours($overtimeMinutes);
        $overtimeAmount = $this->money($overtimeHours * $ratePerHour * $overtimeMultiplier);
        $regularHolidayAmount = $this->money(
            $this->holidayAmount($employee, $dtrs, $ratePerDay, 'regular', $settings)
            + $this->unworkedRegularHolidayAmount($employee, $period, $dtrs, $ratePerDay, $settings)
        );
        $specialHolidayAmount = $this->holidayAmount($employee, $dtrs, $ratePerDay, 'special', $settings);
        $allowance = $this->money($employee->allowance ?? 0);
        $salaryAdjustment = $this->money($employee->salary_adjustment ?? 0);

        $undertimeAmount = $this->money(($undertimeMinutes / 60) * $ratePerHour);
        $lateAmount = $this->money(($lateMinutes / 60) * $ratePerHour);
        $halfDayAmount = $this->money($halfDayCount * ($ratePerDay * $halfDayValue));
        $absentAmount = $isDaily ? 0.0 : $this->money($absenceDays * $ratePerDay);
        $deductions = $this->deductions($employee);

        $grossPay = $this->money(
            $basePay
            + $salaryAdjustment
            + $allowance
            + $overtimeAmount
            + $regularHolidayAmount
            + $specialHolidayAmount
        );

        $totalDeductions = $this->money(
            $undertimeAmount
            + $halfDayAmount
            + $absentAmount
            + $lateAmount
            + $deductions['shortages']
            + $deductions['uniform']
            + $deductions['other_deductions']
            + $deductions['sss_loan']
            + $deductions['sss_ee']
            + $deductions['hdmf_loan']
            + $deductions['hdmf_ee']
            + $deductions['phic_ee']
        );

        return [
            'number' => $number,
            'employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
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
            'regular_holiday' => $regularHolidayAmount,
            'special_holiday' => $specialHolidayAmount,
            'gross_pay' => $grossPay,
            'undertime_minutes' => $undertimeMinutes,
            'undertime_amount' => $undertimeAmount,
            'halfday' => $halfDayAmount,
            'absent' => $absentAmount,
            'late' => $lateAmount,
            'shortages' => $deductions['shortages'],
            'uniform' => $deductions['uniform'],
            'other_deductions' => $deductions['other_deductions'],
            'sss_loan' => $deductions['sss_loan'],
            'sss_ee' => $deductions['sss_ee'],
            'hdmf_loan' => $deductions['hdmf_loan'],
            'hdmf_ee' => $deductions['hdmf_ee'],
            'phic_ee' => $deductions['phic_ee'],
            'total_deductions' => $totalDeductions,
            'net_pay' => $this->money($grossPay - $totalDeductions),
            'signature' => '',
        ];
    }

    public function branchSummaryRows(PayrollPeriod $period): Collection
    {
        $previousPeriod = $this->previousPeriod($period);
        $previousNetByBranch = $previousPeriod
            ? $this->rows($previousPeriod)
                ->groupBy(fn (array $row): string => $row['branch'] ?: 'No Branch')
                ->map(fn (Collection $rows): float => $this->money($rows->sum('net_pay')))
            : collect();

        return $this->rows($period)
            ->groupBy(fn (array $row): string => $row['branch'] ?: 'No Branch')
            ->map(function (Collection $rows, string $branch) use ($previousNetByBranch): array {
                $netPay = $this->money($rows->sum('net_pay'));
                $previousNetPay = $this->money($previousNetByBranch->get($branch, 0));

                return [
                    'branch' => $branch,
                    'workers' => $rows->count(),
                    'gross_pay' => $this->money($rows->sum('gross_pay')),
                    'total_deductions' => $this->money($rows->sum('total_deductions')),
                    'net_pay' => $netPay,
                    'previous_net_pay' => $previousNetPay,
                    'variance' => $this->money($netPay - $previousNetPay),
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
            'total_pay' => 'TOTAL PAY',
            'salary_adjustment' => 'SALARY ADJUSTMENT',
            'allowance' => 'ALLOWANCE',
            'overtime_hours' => 'OVERTIME HRS',
            'overtime_amount' => 'OVERTIME AMOUNT',
            'regular_holiday' => 'REGULAR HOLIDAY',
            'special_holiday' => 'SPECIAL HOLIDAY',
            'gross_pay' => 'GROSS PAY',
            'undertime_minutes' => 'UNDERTIME MINUTES',
            'undertime_amount' => 'UNDERTIME AMOUNT',
            'halfday' => 'HALFDAY',
            'absent' => 'ABSENT',
            'late' => 'LATE',
            'shortages' => 'SHORTAGES',
            'uniform' => 'UNIFORM',
            'other_deductions' => 'OTHER DEDUCTIONS',
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

    public function summaryHeaders(): array
    {
        return [
            'number' => '#',
            'branch' => 'BRANCHES',
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
        $fingerprintId = $employee->fingerprint_id ?: $employee->uid;

        if (blank($fingerprintId) || blank($employee->branch_id)) {
            return collect();
        }

        return Dtr::query()
            ->with(['holiday.type'])
            ->where('payroll_period_id', $period->id)
            ->where('branch_id', $employee->branch_id)
            ->where('fingerprint_id', $fingerprintId)
            ->get();
    }

    protected function rateType(Employee $employee): string
    {
        return Str::contains(Str::lower((string) $employee->rate_type), 'daily') ? 'Daily' : 'Monthly';
    }

    protected function ratePerDay(Employee $employee, ?Collection $dtrs = null, ?PayrollCalculationSetting $settings = null): float
    {
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
        return (float) $this->payableWorkDtrs($dtrs)
            ->unique(fn (Dtr $dtr): string => (string) $dtr->date_in)
            ->count();
    }

    protected function workedDtrEntries(Collection $dtrs): float
    {
        return (float) $this->payableWorkDtrs($dtrs)
            ->count();
    }

    protected function payableWorkDtrs(Collection $dtrs): Collection
    {
        return $dtrs
            ->reject(fn (Dtr $dtr): bool => (bool) $dtr->is_absent)
            ->reject(fn (Dtr $dtr): bool => Str::lower((string) $dtr->schedule_type) === 'overtime')
            ->filter(fn (Dtr $dtr): bool => filled($dtr->date_in));
    }

    protected function deductibleDtrs(Collection $dtrs): Collection
    {
        return $dtrs
            ->reject(fn (Dtr $dtr): bool => (bool) $dtr->is_absent)
            ->reject(fn (Dtr $dtr): bool => Str::lower((string) $dtr->schedule_type) === 'overtime');
    }

    protected function absenceDays(Collection $dtrs): float
    {
        return (float) $dtrs
            ->filter(fn (Dtr $dtr): bool => (bool) $dtr->is_absent)
            ->filter(fn (Dtr $dtr): bool => filled($dtr->date_in))
            ->unique(fn (Dtr $dtr): string => (string) $dtr->date_in)
            ->count();
    }

    protected function approvedHalfDayCount(Employee $employee, PayrollPeriod $period): float
    {
        if (blank($period->date_start) || blank($period->date_end)) {
            return 0.0;
        }

        return (float) Leave::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->where('is_half_day', true)
            ->whereDate('leave_from', '<=', $period->date_end)
            ->where(function (Builder $query) use ($period): void {
                $query
                    ->whereDate('leave_to', '>=', $period->date_start)
                    ->orWhere(function (Builder $query) use ($period): void {
                        $query
                            ->whereNull('leave_to')
                            ->whereDate('leave_from', '>=', $period->date_start);
                    });
            })
            ->count();
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
                    $dailyRate = (float) ($dtr->daily_rate ?: $ratePerDay);
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

        return $this->money(
            app(HolidayEntitlementService::class)
                ->regularHolidaysForEmployeeRange($employee, $period->date_start, $period->date_end, $employee->branch_id)
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
        $titleToKey = collect(self::DEFAULT_DEDUCTION_TITLES)
            ->mapWithKeys(fn (string $title, string $key): array => [$this->normalizeTitle($title) => $key])
            ->all();

        foreach (app(EmployeeDeductionService::class)->activeEmployeeDeductions($employee) as $employeeDeduction) {
            $title = $this->normalizeTitle((string) $employeeDeduction->deduction?->title);
            $amount = $this->money($employeeDeduction->amount ?? 0);
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

    protected function number(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}
