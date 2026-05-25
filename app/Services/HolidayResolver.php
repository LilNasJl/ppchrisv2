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
        $holidays = collect();

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $holiday = $this->resolveForDate($date, $branchId, $includeNational);

            if ($holiday) {
                $holidays->put($date->toDateString(), $holiday);
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
