<?php

namespace App\Services;

use App\Models\Dtr;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DtrAttendanceUnitService
{
    /**
     * Calendar attendance days, capped at one workday per date.
     *
     * @param  Collection<int, Dtr>  $records
     */
    public function attendanceDays(Collection $records): float
    {
        return (float) $this->payableRecords($records)
            ->groupBy(fn (Dtr $record): string => Carbon::parse($record->date_in)->toDateString())
            ->sum(function (Collection $dateRecords): float {
                $hasFullDayRecord = $dateRecords->contains(
                    fn (Dtr $record): bool => $this->brokenSegment($record) === null,
                );

                return $hasFullDayRecord ? 1.0 : $this->brokenUnits($dateRecords);
            });
    }

    /**
     * Pay units for daily-rate employees. Existing non-broken entries remain
     * individually payable, while each unique broken segment is half a day.
     *
     * @param  Collection<int, Dtr>  $records
     */
    public function dailyRatePayUnits(Collection $records): float
    {
        return (float) $this->payableRecords($records)
            ->groupBy(fn (Dtr $record): string => Carbon::parse($record->date_in)->toDateString())
            ->sum(function (Collection $dateRecords): float {
                $fullDayEntries = $dateRecords
                    ->filter(fn (Dtr $record): bool => $this->brokenSegment($record) === null)
                    ->count();

                return $fullDayEntries + $this->brokenUnits($dateRecords);
            });
    }

    /**
     * @param  Collection<int, Dtr>  $records
     */
    protected function payableRecords(Collection $records): Collection
    {
        return $records
            ->reject(fn (Dtr $record): bool => (bool) $record->is_absent)
            ->reject(fn (Dtr $record): bool => filled($record->leave_id))
            ->reject(fn (Dtr $record): bool => in_array($record->entry_source, [
                DtrDayPartService::SOURCE_LEAVE,
                DtrDayPartService::SOURCE_ABSENCE,
            ], true))
            ->reject(function (Dtr $record): bool {
                $scheduleType = Str::lower((string) $record->schedule_type);

                return in_array($scheduleType, ['leave', 'overtime', 'forgot to punch'], true);
            })
            ->filter(fn (Dtr $record): bool => filled($record->date_in))
            ->values();
    }

    /**
     * @param  Collection<int, Dtr>  $records
     */
    protected function brokenUnits(Collection $records): float
    {
        $segments = $records
            ->map(fn (Dtr $record): ?string => $this->brokenSegment($record))
            ->filter()
            ->unique()
            ->count();

        return min(1.0, $segments * 0.5);
    }

    protected function brokenSegment(Dtr $record): ?string
    {
        $scheduleType = Str::of((string) $record->schedule_type)
            ->lower()
            ->replace([' ', '_', '-'], '')
            ->toString();

        if (! str_contains($scheduleType, 'broken') && ! str_contains($scheduleType, 'brkn')) {
            return null;
        }

        if (str_contains($scheduleType, '1')) {
            return 'broken1';
        }

        if (str_contains($scheduleType, '2')) {
            return 'broken2';
        }

        return 'broken-'.($record->getKey() ?: spl_object_id($record));
    }
}
