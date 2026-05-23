<?php

namespace App\Services;

use App\Models\Dtr;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\PayrollPeriod;
use App\Models\PayrollSnapshot;
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
        return PayrollPeriod::query()->latest()->first();
    }

    public function periodOptions(): array
    {
        return PayrollPeriod::query()
            ->latest()
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
        return $this->employeesQuery($branchId)
            ->get()
            ->values()
            ->map(fn (Employee $employee, int $index): array => $this->row($employee, $period, $index + 1));
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

        $dtrs = $this->dtrs($employee, $period);
        $rateType = $this->rateType($employee);
        $isDaily = $rateType === 'Daily';
        $ratePerDay = $this->ratePerDay($employee, $dtrs);
        $hasStoredDailyRate = $this->storedDailyRate($dtrs) > 0;
        $monthlyRate = $isDaily ? null : $this->money($hasStoredDailyRate
            ? $ratePerDay * self::REGULAR_WORK_DAYS_PER_MONTH
            : ($employee->monthly_rate ?: ($ratePerDay * self::REGULAR_WORK_DAYS_PER_MONTH)));
        $halfMonthPay = $isDaily ? null : $this->money(($monthlyRate ?? 0) / 2);
        $ratePerHour = $this->money($ratePerDay / 8);

        $absenceDays = $this->absenceDays($dtrs);
        $halfDayCount = $this->approvedHalfDayCount($employee, $period);
        $dtrWorkDays = $isDaily ? $this->workedDtrEntries($dtrs) : $this->workedDtrDays($dtrs);
        $daysWorked = $isDaily
            ? max(0, $dtrWorkDays - ($halfDayCount * 0.5))
            : max(0, self::REGULAR_HALF_MONTH_DAYS - $absenceDays - ($halfDayCount * 0.5));

        $basePay = $isDaily
            ? $this->money($ratePerDay * $daysWorked)
            : $this->money($ratePerDay * self::REGULAR_HALF_MONTH_DAYS);

        $deductibleDtrs = $this->deductibleDtrs($dtrs);
        $lateMinutes = $this->sumMinutes($deductibleDtrs, 'late');
        $undertimeMinutes = $this->sumMinutes($deductibleDtrs, 'undertime');
        $overtimeMinutes = $this->sumMinutes($dtrs, 'credited_overtime');
        $overtimeHours = $this->hours($overtimeMinutes);
        $overtimeAmount = $this->money($overtimeHours * $ratePerHour);
        $regularHolidayAmount = $this->holidayAmount($dtrs, $ratePerDay, 'regular');
        $specialHolidayAmount = $this->holidayAmount($dtrs, $ratePerDay, 'special');
        $allowance = $this->money($employee->allowance ?? 0);
        $salaryAdjustment = 0.0;

        $undertimeAmount = $this->money(($undertimeMinutes / 60) * $ratePerHour);
        $lateAmount = $this->money(($lateMinutes / 60) * $ratePerHour);
        $halfDayAmount = $this->money($halfDayCount * ($ratePerDay / 2));
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
                ->latest()
                ->first();
        }

        return PayrollPeriod::query()
            ->whereDate('date_start', '<', $period->date_start)
            ->latest('date_start')
            ->first()
            ?: PayrollPeriod::query()
                ->whereKeyNot($period->id)
                ->where('id', '<', $period->id)
                ->latest()
                ->first();
    }

    protected function dtrs(Employee $employee, PayrollPeriod $period): Collection
    {
        if (blank($employee->fingerprint_id) || blank($employee->branch_id)) {
            return collect();
        }

        return Dtr::query()
            ->with(['holiday.type'])
            ->where('payroll_period_id', $period->id)
            ->where('branch_id', $employee->branch_id)
            ->where('fingerprint_id', $employee->fingerprint_id)
            ->get();
    }

    protected function rateType(Employee $employee): string
    {
        return Str::contains(Str::lower((string) $employee->rate_type), 'daily') ? 'Daily' : 'Monthly';
    }

    protected function ratePerDay(Employee $employee, ?Collection $dtrs = null): float
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

        return $monthlyRate > 0
            ? $this->money($monthlyRate / self::REGULAR_WORK_DAYS_PER_MONTH)
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

    protected function holidayAmount(Collection $dtrs, float $ratePerDay, string $type): float
    {
        return $this->money(
            $dtrs
                ->filter(fn (Dtr $dtr): bool => (bool) $dtr->is_holiday)
                ->filter(function (Dtr $dtr) use ($type): bool {
                    $holidayType = Str::lower((string) ($dtr->holiday_type ?: $dtr->holiday?->type?->type));
                    $holidayRate = (float) ($dtr->holiday_rate ?: $dtr->holiday?->type?->rate ?: 0);

                    if ($type === 'regular') {
                        return Str::contains($holidayType, 'regular') || $holidayRate >= 100;
                    }

                    return Str::contains($holidayType, 'special') || ($holidayRate > 0 && $holidayRate < 100);
                })
                ->sum(function (Dtr $dtr) use ($ratePerDay): float {
                    $holidayRate = (float) ($dtr->holiday_rate ?: $dtr->holiday?->type?->rate ?: 0);
                    $dailyRate = (float) ($dtr->daily_rate ?: $ratePerDay);
                    $ratePerHour = $dailyRate / 8;
                    $creditedHours = ((float) ($dtr->credited_work_hrs ?? 0)) / 60;
                    $baseWorkedPay = $creditedHours * $ratePerHour;
                    $premiumMultiplier = $holidayRate >= 100
                        ? max(0, ($holidayRate - 100) / 100)
                        : $holidayRate / 100;

                    return $baseWorkedPay * $premiumMultiplier;
                })
        );
    }

    protected function deductions(Employee $employee): array
    {
        $deductions = array_fill_keys(array_keys(self::DEFAULT_DEDUCTION_TITLES), 0.0);
        $deductions['other_deductions'] = 0.0;
        $titleToKey = collect(self::DEFAULT_DEDUCTION_TITLES)
            ->mapWithKeys(fn (string $title, string $key): array => [$this->normalizeTitle($title) => $key])
            ->all();

        foreach ($employee->employeeDeductions as $employeeDeduction) {
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
