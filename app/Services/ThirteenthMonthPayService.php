<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\PayrollPeriod;
use App\Models\PayrollSnapshot;
use App\Models\ThirteenthMonthRelease;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ThirteenthMonthPayService
{
    public const MIDYEAR = 'midyear';

    public const YEAR_END = 'year_end';

    public const WHOLE_YEAR = 'whole_year';

    public const DIVISOR = 6;

    public function segmentOptions(): array
    {
        return [
            self::MIDYEAR => 'Midyear (January to June)',
            self::YEAR_END => 'Year End (July to December)',
            self::WHOLE_YEAR => 'Whole Year (January to December)',
        ];
    }

    public function yearOptions(): array
    {
        $years = PayrollPeriod::query()
            ->where('is_locked', true)
            ->whereNotNull('date_payout')
            ->orderByDesc('date_payout')
            ->pluck('date_payout')
            ->map(fn ($date): int => Carbon::parse($date)->year)
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        return $years->mapWithKeys(fn (int $year): array => [$year => (string) $year])->all();
    }

    public function branchOptions(): array
    {
        return Branch::query()
            ->orderBy('branch_name')
            ->pluck('branch_name', 'id')
            ->all();
    }

    public function rows(int $year, string $segment, ?int $branchId = null): Collection
    {
        $this->validateSegment($segment);

        [$dateFrom, $dateTo] = $this->dateRange($year, $segment);

        $periods = PayrollPeriod::query()
            ->where('is_locked', true)
            ->whereNotNull('locked_at')
            ->whereBetween('date_payout', [$dateFrom, $dateTo])
            ->orderBy('date_payout')
            ->get()
            ->keyBy('id');

        if ($periods->isEmpty()) {
            return collect();
        }

        $snapshots = PayrollSnapshot::query()
            ->with(['employee.branch', 'employee.designation'])
            ->whereIn('payroll_period_id', $periods->keys())
            ->whereNotNull('employee_id')
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('row_number')
            ->get();

        $releases = ThirteenthMonthRelease::query()
            ->where('year', $year)
            ->whereIn('segment', array_keys($this->segmentOptions()))
            ->whereIn('employee_id', $snapshots->pluck('employee_id')->unique())
            ->get()
            ->groupBy('employee_id');

        $months = $this->monthNumbers($segment);
        $periodColumns = $this->periodColumns($year, $segment);

        return $snapshots
            ->groupBy('employee_id')
            ->map(function (Collection $employeeSnapshots, int|string $employeeId) use ($periods, $releases, $months, $periodColumns, $segment): ?array {
                $first = $employeeSnapshots->first();
                $employee = $first?->employee;

                if (! $first || ! $employee) {
                    return null;
                }

                $monthAmounts = collect($months)
                    ->mapWithKeys(fn (int $month): array => [$month => 0.0])
                    ->all();
                $periodAmounts = collect(array_keys($periodColumns))
                    ->mapWithKeys(fn (string $key): array => [$key => 0.0])
                    ->all();
                $periodLines = [];

                foreach ($employeeSnapshots as $snapshot) {
                    $period = $periods->get($snapshot->payroll_period_id);

                    if (! $period) {
                        continue;
                    }

                    $basis = $this->eligibleBasis((array) $snapshot->data);
                    $month = (int) $period->date_payout->month;
                    $periodKey = $this->periodColumnKey($period->date_payout);
                    $monthAmounts[$month] = $this->money(($monthAmounts[$month] ?? 0) + $basis);
                    $periodAmounts[$periodKey] = $this->money(($periodAmounts[$periodKey] ?? 0) + $basis);
                    $periodLines[] = [
                        'payroll_period_id' => $period->id,
                        'payroll_period' => $period->title,
                        'payout_date' => $period->date_payout->toDateString(),
                        'eligible_basis' => $basis,
                    ];
                }

                $basisTotal = $this->money(array_sum($monthAmounts));
                $divisor = $this->divisor($segment);
                $calculatedAmount = $this->money($basisTotal / $divisor);
                $employeeReleases = $releases->get((int) $employeeId, collect());
                $currentReleases = $employeeReleases->where('segment', $segment);
                $wholeYearRelease = $employeeReleases->firstWhere('segment', self::WHOLE_YEAR);
                $halfYearReleases = $employeeReleases->whereIn('segment', [self::MIDYEAR, self::YEAR_END]);
                $releasedAmount = $this->money($currentReleases->sum('released_amount'));
                $pendingAmount = $this->money(max(0, $calculatedAmount - $releasedAmount));
                $isReleased = $currentReleases->isNotEmpty() && $pendingAmount <= 0;
                $isPartiallyReleased = $currentReleases->isNotEmpty() && ! $isReleased;
                $releaseStatus = $isReleased ? 'Released' : ($isPartiallyReleased ? 'Partially Released' : 'Pending');
                $latestRelease = $currentReleases->sortByDesc('released_at')->first();

                if ($segment === self::WHOLE_YEAR && $currentReleases->isEmpty() && $halfYearReleases->isNotEmpty()) {
                    $releasedAmount = $this->money($halfYearReleases->sum('released_amount'));
                    $pendingAmount = 0.0;
                    $isReleased = $halfYearReleases->pluck('segment')->unique()->count() === 2;
                    $isPartiallyReleased = ! $isReleased;
                    $releaseStatus = $isReleased ? 'Released by Midyear & Year End' : 'Half-Year Release Exists';
                    $latestRelease = $halfYearReleases->sortByDesc('released_at')->first();
                }

                if ($segment !== self::WHOLE_YEAR && $currentReleases->isEmpty() && $wholeYearRelease) {
                    $releasedAmount = $calculatedAmount;
                    $pendingAmount = 0.0;
                    $isReleased = true;
                    $isPartiallyReleased = false;
                    $releaseStatus = 'Released via Whole Year';
                    $latestRelease = $wholeYearRelease;
                }

                return [
                    'employee_id' => (int) $employeeId,
                    'employee_uid' => $employee->uid ?: '-',
                    'employee_name' => (string) (($first->data['name'] ?? null) ?: $employee->full_name),
                    'date_hired' => $employee->hired_date?->format('M d, Y') ?: '-',
                    'branch_id' => $first->branch_id,
                    'branch' => (string) (($first->data['branch'] ?? null) ?: $employee->branch?->branch_name ?: 'Unassigned'),
                    'designation' => (string) (($first->data['designation'] ?? null) ?: $employee->designation?->title ?: '-'),
                    'employment_status' => $employee->employment_type ?: 'Active',
                    'months' => $monthAmounts,
                    'period_amounts' => $periodAmounts,
                    'period_lines' => $periodLines,
                    'basis_total' => $basisTotal,
                    'calculated_amount' => $calculatedAmount,
                    'released' => $isReleased,
                    'partially_released' => $isPartiallyReleased,
                    'release_status' => $releaseStatus,
                    'released_amount' => $releasedAmount,
                    'pending_amount' => $pendingAmount,
                    'released_at' => $latestRelease?->released_at?->format('M d, Y h:i A'),
                ];
            })
            ->filter()
            ->sortBy('employee_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(function (array $row, int $index): array {
                $row['number'] = $index + 1;

                return $row;
            });
    }

    public function summaryRows(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn (array $row): string => ($row['branch_id'] ?? 'none').'|'.$row['branch'])
            ->map(function (Collection $branchRows): array {
                return [
                    'branch' => $branchRows->first()['branch'],
                    'employees' => $branchRows->count(),
                    'basis_total' => $this->money($branchRows->sum('basis_total')),
                    'calculated_total' => $this->money($branchRows->sum('calculated_amount')),
                    'released_count' => $branchRows->where('released', true)->count(),
                    'partial_count' => $branchRows->where('partially_released', true)->count(),
                    'released_total' => $this->money($branchRows->sum('released_amount')),
                    'pending_count' => $branchRows->where('pending_amount', '>', 0)->count(),
                    'pending_total' => $this->money($branchRows->sum('pending_amount')),
                ];
            })
            ->sortBy('branch', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(function (array $row, int $index): array {
                $row['number'] = $index + 1;

                return $row;
            });
    }

    public function release(array $row, int $year, string $segment, ?int $userId): ThirteenthMonthRelease
    {
        $this->validateSegment($segment);

        if (($row['calculated_amount'] ?? 0) <= 0) {
            throw new InvalidArgumentException('The employee has no eligible amount to release.');
        }

        return DB::transaction(function () use ($row, $year, $segment, $userId): ThirteenthMonthRelease {
            $yearReleases = ThirteenthMonthRelease::query()
                ->where('year', $year)
                ->lockForUpdate()
                ->get(['segment']);

            if ($segment === self::WHOLE_YEAR && $yearReleases->whereIn('segment', [self::MIDYEAR, self::YEAR_END])->isNotEmpty()) {
                throw new InvalidArgumentException('Whole Year cannot be released because Midyear or Year End results have already been released.');
            }

            if ($segment !== self::WHOLE_YEAR && $yearReleases->contains('segment', self::WHOLE_YEAR)) {
                throw new InvalidArgumentException('Half-year results cannot be released because Whole Year results have already been released.');
            }

            return ThirteenthMonthRelease::query()->updateOrCreate(
                [
                    'employee_id' => $row['employee_id'],
                    'year' => $year,
                    'segment' => $segment,
                ],
                [
                    'branch_id' => $row['branch_id'],
                    'basis_amount' => $row['basis_total'],
                    'released_amount' => $row['calculated_amount'],
                    'calculation_data' => [
                        'divisor' => $this->divisor($segment),
                        'months' => $row['months'],
                        'period_amounts' => $row['period_amounts'],
                        'periods' => $row['period_lines'],
                    ],
                    'released_at' => now(),
                    'released_by' => $userId,
                ],
            );
        });
    }

    public function setReleaseStatus(Collection $rows, int $year, string $segment, ?int $userId, string $status): int
    {
        $this->validateSegment($segment);

        if (! in_array($status, ['released', 'pending'], true)) {
            throw new InvalidArgumentException('Invalid release status.');
        }

        $employeeIds = $rows
            ->pluck('employee_id')
            ->filter()
            ->unique()
            ->values();

        if ($employeeIds->isEmpty()) {
            return 0;
        }

        if ($status === 'pending') {
            return ThirteenthMonthRelease::query()
                ->where('year', $year)
                ->where('segment', $segment)
                ->whereIn('employee_id', $employeeIds)
                ->delete();
        }

        return DB::transaction(function () use ($rows, $year, $segment, $userId): int {
            return $rows
                ->filter(fn (array $row): bool => ($row['calculated_amount'] ?? 0) > 0)
                ->each(fn (array $row) => $this->release($row, $year, $segment, $userId))
                ->count();
        });
    }

    public function releaseConflict(int $year, string $segment): ?string
    {
        $this->validateSegment($segment);

        $segments = ThirteenthMonthRelease::query()
            ->where('year', $year)
            ->pluck('segment')
            ->unique();

        if ($segment === self::WHOLE_YEAR && $segments->intersect([self::MIDYEAR, self::YEAR_END])->isNotEmpty()) {
            return 'Whole Year release is unavailable because Midyear or Year End results have already been released for this year.';
        }

        if ($segment !== self::WHOLE_YEAR && $segments->contains(self::WHOLE_YEAR)) {
            return 'This half-year release is unavailable because Whole Year results have already been released for this year.';
        }

        return null;
    }

    public function divisor(string $segment): int
    {
        $this->validateSegment($segment);

        return $segment === self::WHOLE_YEAR ? 12 : self::DIVISOR;
    }

    public function monthLabels(string $segment): array
    {
        return collect($this->monthNumbers($segment))
            ->mapWithKeys(fn (int $month): array => [$month => Carbon::create(2000, $month, 1)->format('M')])
            ->all();
    }

    public function periodColumns(int $year, string $segment): array
    {
        $this->validateSegment($segment);
        [$dateFrom, $dateTo] = $this->dateRange($year, $segment);

        $periods = PayrollPeriod::query()
            ->whereBetween('date_payout', [$dateFrom, $dateTo])
            ->orderBy('date_payout')
            ->get()
            ->groupBy(fn (PayrollPeriod $period): string => $this->periodColumnKey($period->date_payout));

        return collect($this->monthNumbers($segment))
            ->flatMap(function (int $month) use ($periods, $year): array {
                return collect(['first', 'second'])
                    ->mapWithKeys(function (string $slot) use ($periods, $year, $month): array {
                        $key = $month.'_'.$slot;
                        $period = $periods->get($key)?->first();
                        $expectedDate = $slot === 'first'
                            ? Carbon::create($year, $month, 15)
                            : Carbon::create($year, $month, 1)->endOfMonth();
                        $isFinalized = (bool) ($period?->is_locked && $period?->locked_at);

                        return [$key => [
                            'key' => $key,
                            'month' => $month,
                            'month_label' => Carbon::create(2000, $month, 1)->format('M'),
                            'slot' => $slot,
                            'period_label' => ($period?->date_payout ?? $expectedDate)->format('d'),
                            'title' => $period?->title ?? 'Payroll period not created',
                            'status' => ! $period ? 'Not Created' : ($isFinalized ? 'Locked' : 'Open'),
                            'is_locked' => $isFinalized,
                            'payroll_period_id' => $period?->id,
                        ]];
                    })
                    ->all();
            })
            ->all();
    }

    public function segmentLabel(string $segment): string
    {
        $this->validateSegment($segment);

        return match ($segment) {
            self::MIDYEAR => 'Midyear Pay',
            self::YEAR_END => 'Year End Pay',
            self::WHOLE_YEAR => 'Whole Year Pay',
        };
    }

    public function periodLabel(int $year, string $segment): string
    {
        $this->validateSegment($segment);

        return match ($segment) {
            self::MIDYEAR => "January to June {$year}",
            self::YEAR_END => "July to December {$year}",
            self::WHOLE_YEAR => "January to December {$year}",
        };
    }

    protected function eligibleBasis(array $data): float
    {
        $isDaily = strcasecmp((string) ($data['rate'] ?? ''), 'Daily') === 0;
        $basePay = $isDaily
            ? (float) ($data['total_pay'] ?? 0)
            : (float) ($data['half_month_pay'] ?? 0);

        $deductions = (float) ($data['undertime_amount'] ?? 0)
            + (float) ($data['late'] ?? 0);

        if (! $isDaily) {
            $deductions += (float) ($data['halfday'] ?? 0)
                + (float) ($data['absent'] ?? 0);
        }

        return $this->money(max(0, $basePay - $deductions));
    }

    protected function monthNumbers(string $segment): array
    {
        return match ($segment) {
            self::MIDYEAR => range(1, 6),
            self::YEAR_END => range(7, 12),
            self::WHOLE_YEAR => range(1, 12),
        };
    }

    protected function dateRange(int $year, string $segment): array
    {
        return match ($segment) {
            self::MIDYEAR => ["{$year}-01-01", "{$year}-06-30"],
            self::YEAR_END => ["{$year}-07-01", "{$year}-12-31"],
            self::WHOLE_YEAR => ["{$year}-01-01", "{$year}-12-31"],
        };
    }

    protected function periodColumnKey(Carbon|string $date): string
    {
        $date = Carbon::parse($date);

        return $date->month.'_'.($date->day <= 15 ? 'first' : 'second');
    }

    protected function validateSegment(string $segment): void
    {
        if (! array_key_exists($segment, $this->segmentOptions())) {
            throw new InvalidArgumentException('Invalid 13th-month calculation type.');
        }
    }

    protected function money(float|int|string|null $value): float
    {
        return round((float) $value, 2);
    }
}
