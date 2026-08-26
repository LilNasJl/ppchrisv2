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
                $hasOtherFullDayRecord = $dateRecords->contains(
                    fn (Dtr $record): bool => ! $this->isRegular($record)
                        && ! $this->isSaturday($record)
                        && $this->brokenSegment($record) === null,
                );

                return $hasOtherFullDayRecord
                    ? 1.0
                    : min(
                        1.0,
                        $this->regularUnits($dateRecords)
                            + $this->brokenUnits($dateRecords)
                            + $this->saturdayUnits($dateRecords),
                    );
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
                    ->filter(fn (Dtr $record): bool => ! $this->isRegular($record)
                        && ! $this->isSaturday($record)
                        && $this->brokenSegment($record) === null)
                    ->count();

                return $fullDayEntries
                    + $this->regularUnits($dateRecords)
                    + $this->brokenUnits($dateRecords)
                    + $this->saturdayUnits($dateRecords);
            });
    }

    public function dayPartForRecord(Dtr $record): string
    {
        if (! $this->isRegular($record)) {
            return app(DtrDayPartService::class)->normalize($record->day_part);
        }

        if (filled($record->date_in) && filled($record->time_in) && filled($record->date_out) && filled($record->time_out)) {
            return app(DtrDayPartService::class)->classifyRegularPunch(
                dateIn: (string) $record->date_in,
                timeIn: (string) $record->time_in,
                dateOut: (string) $record->date_out,
                timeOut: (string) $record->time_out,
                scheduleStart: (string) $record->schedule_start,
                scheduleEnd: (string) $record->schedule_end,
                scheduleType: (string) $record->schedule_type,
            );
        }

        return app(DtrDayPartService::class)->normalize($record->day_part);
    }

    public function recordAttendanceUnits(Dtr $record): float
    {
        if ($this->payableRecords(collect([$record]))->isEmpty()) {
            return 0.0;
        }

        if ($this->isRegular($record)) {
            return match ($this->dayPartForRecord($record)) {
                DtrDayPartService::MORNING, DtrDayPartService::AFTERNOON => 0.5,
                DtrDayPartService::UNCLASSIFIED => 0.0,
                default => 1.0,
            };
        }

        if ($this->isSaturday($record)) {
            return 0.5;
        }

        return $this->brokenSegment($record) === null ? 1.0 : 0.5;
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

    /**
     * A Saturday schedule represents the company's three-hour half day.
     * Duplicate rows on the same date must not add another half day.
     *
     * @param  Collection<int, Dtr>  $records
     */
    protected function saturdayUnits(Collection $records): float
    {
        return $records->contains(fn (Dtr $record): bool => $this->isSaturday($record))
            ? 0.5
            : 0.0;
    }

    protected function regularUnits(Collection $records): float
    {
        $parts = $records
            ->filter(fn (Dtr $record): bool => $this->isRegular($record))
            ->map(fn (Dtr $record): string => $this->dayPartForRecord($record))
            ->values();

        if ($parts->contains(DtrDayPartService::WHOLE_DAY)) {
            return 1.0;
        }

        return min(1.0, $parts
            ->filter(fn (string $part): bool => in_array($part, [
                DtrDayPartService::MORNING,
                DtrDayPartService::AFTERNOON,
            ], true))
            ->unique()
            ->count() * 0.5);
    }

    protected function isRegular(Dtr $record): bool
    {
        return app(DtrDayPartService::class)->isRegularScheduleType($record->schedule_type);
    }

    protected function isSaturday(Dtr $record): bool
    {
        $scheduleType = Str::of((string) $record->schedule_type)
            ->lower()
            ->replace([' ', '_', '-'], '')
            ->toString();

        return $scheduleType === 'saturday';
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
