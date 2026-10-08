<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HolidayResolver
{
    public function resolveForDate(CarbonInterface|string $date, ?int $branchId = null, bool $includeNational = true): ?Holiday
    {
        $date = Carbon::parse($date)->startOfDay();
        $dateString = $date->toDateString();
        $monthDay = $date->format('m-d');

        return $this->baseMatchQuery($dateString, $monthDay, $branchId, $includeNational)
            ->with(['type', 'branch'])
            ->orderByRaw('CASE WHEN branch_id IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByRaw('CASE WHEN DATE(`date`) = ? THEN 1 ELSE 0 END DESC', [$dateString])
            ->first();
    }

    public function holidaysForRange(CarbonInterface|string $start, CarbonInterface|string $end, ?int $branchId = null, bool $includeNational = true): Collection
    {
        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->startOfDay();
        $dates = [];
        $monthDays = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $dateString = $date->toDateString();
            $monthDay = $date->format('m-d');
            $dates[$dateString] = $monthDay;
            $monthDays[$monthDay] = $monthDay;
        }

        if ($dates === []) {
            return collect();
        }

        $candidates = Holiday::query()
            ->with(['type', 'branch'])
            ->where(function (Builder $query) use ($branchId, $includeNational): void {
                if ($branchId) {
                    $query->where('branch_id', $branchId);

                    if ($includeNational) {
                        $query->orWhereNull('branch_id');
                    }

                    return;
                }

                $query->whereNull('branch_id');
            })
            ->where(function (Builder $query) use ($startDate, $endDate, $monthDays): void {
                $query
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhere(function (Builder $query) use ($monthDays): void {
                        $query
                            ->where('is_recurring', true)
                            ->whereIn('month_day', array_values($monthDays));
                    });
            })
            ->get();

        return $this->resolveDatesFromCandidates($dates, $candidates);
    }

    /**
     * Resolve holidays across a date range for multiple branches in a single database query.
     *
     * @param  array<int, int>  $branchIds
     * @return array<int, Collection<string, Holiday>>
     */
    public function holidaysForRangeByBranches(CarbonInterface|string $start, CarbonInterface|string $end, array $branchIds): array
    {
        $branchIds = array_values(array_unique(array_map('intval', array_filter($branchIds))));

        if ($branchIds === []) {
            return [];
        }

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->startOfDay();
        $dates = [];
        $monthDays = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $dateString = $date->toDateString();
            $monthDay = $date->format('m-d');
            $dates[$dateString] = $monthDay;
            $monthDays[$monthDay] = $monthDay;
        }

        if ($dates === []) {
            return array_fill_keys($branchIds, collect());
        }

        $allCandidates = Holiday::query()
            ->with(['type', 'branch'])
            ->where(function (Builder $query) use ($branchIds): void {
                $query
                    ->whereIn('branch_id', $branchIds)
                    ->orWhereNull('branch_id');
            })
            ->where(function (Builder $query) use ($startDate, $endDate, $monthDays): void {
                $query
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhere(function (Builder $query) use ($monthDays): void {
                        $query
                            ->where('is_recurring', true)
                            ->whereIn('month_day', array_values($monthDays));
                    });
            })
            ->get();

        if ($allCandidates->isEmpty()) {
            return array_fill_keys($branchIds, collect());
        }

        $result = [];

        foreach ($branchIds as $branchId) {
            $branchCandidates = $allCandidates->filter(
                fn (Holiday $h): bool => $h->branch_id === null || (int) $h->branch_id === $branchId
            );
            $result[$branchId] = $this->resolveDatesFromCandidates($dates, $branchCandidates);
        }

        return $result;
    }

    /**
     * @param  array<string, string>  $dates
     * @param  Collection<int, Holiday>  $candidates
     * @return Collection<string, Holiday>
     */
    protected function resolveDatesFromCandidates(array $dates, Collection $candidates): Collection
    {
        if ($candidates->isEmpty()) {
            return collect();
        }

        $holidays = collect();

        foreach ($dates as $dateString => $monthDay) {
            $matched = $candidates
                ->filter(function (Holiday $holiday) use ($dateString, $monthDay): bool {
                    $holidayDate = $holiday->date instanceof CarbonInterface
                        ? $holiday->date->toDateString()
                        : (filled($holiday->date) ? Carbon::parse($holiday->date)->toDateString() : null);

                    return $holidayDate === $dateString
                        || ((bool) $holiday->is_recurring && (string) $holiday->month_day === $monthDay);
                })
                ->sort(function (Holiday $a, Holiday $b) use ($dateString): int {
                    $aBranchRank = $a->branch_id === null ? 0 : 1;
                    $bBranchRank = $b->branch_id === null ? 0 : 1;

                    if ($aBranchRank !== $bBranchRank) {
                        return $bBranchRank <=> $aBranchRank;
                    }

                    $aDate = $a->date instanceof CarbonInterface
                        ? $a->date->toDateString()
                        : (filled($a->date) ? Carbon::parse($a->date)->toDateString() : null);
                    $bDate = $b->date instanceof CarbonInterface
                        ? $b->date->toDateString()
                        : (filled($b->date) ? Carbon::parse($b->date)->toDateString() : null);

                    $aExact = $aDate === $dateString ? 1 : 0;
                    $bExact = $bDate === $dateString ? 1 : 0;

                    if ($aExact !== $bExact) {
                        return $bExact <=> $aExact;
                    }

                    return (int) $a->id <=> (int) $b->id;
                })
                ->first();

            if ($matched) {
                $holidays->put($dateString, clone $matched);
            }
        }

        return $holidays;
    }

    public function upcomingNationalHolidays(int $limit = 5): Collection
    {
        $today = now()->startOfDay();

        return $this->holidaysForRange($today, $today->copy()->addYear(), null, includeNational: false)
            ->map(function (Holiday $holiday, string $date): Holiday {
                return $this->withOccurrenceDate($holiday, $date);
            })
            ->sortBy('occurrence_date')
            ->take($limit)
            ->values();
    }

    public function nationalHolidaysForMonth(CarbonInterface|string $month): Collection
    {
        $month = Carbon::parse($month)->startOfMonth();

        return $this->holidaysForRange($month, $month->copy()->endOfMonth(), null, includeNational: false)
            ->map(function (Holiday $holiday, string $date): Holiday {
                return $this->withOccurrenceDate($holiday, $date);
            })
            ->sortBy('occurrence_date')
            ->values();
    }

    protected function baseMatchQuery(string $dateString, string $monthDay, ?int $branchId, bool $includeNational): Builder
    {
        return Holiday::query()
            ->where(function (Builder $query) use ($branchId, $includeNational): void {
                if ($branchId) {
                    $query->where('branch_id', $branchId);

                    if ($includeNational) {
                        $query->orWhereNull('branch_id');
                    }

                    return;
                }

                $query->whereNull('branch_id');
            })
            ->where(function (Builder $query) use ($dateString, $monthDay): void {
                $query
                    ->whereDate('date', $dateString)
                    ->orWhere(function (Builder $query) use ($monthDay): void {
                        $query
                            ->where('is_recurring', true)
                            ->where('month_day', $monthDay);
                    });
            });
    }

    protected function withOccurrenceDate(Holiday $holiday, string $date): Holiday
    {
        $holiday->setAttribute('occurrence_date', Carbon::parse($date)->toDateString());

        return $holiday;
    }
}
