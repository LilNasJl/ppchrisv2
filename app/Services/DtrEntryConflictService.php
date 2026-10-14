<?php

namespace App\Services;

use App\Models\Dtr;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DtrEntryConflictService
{
    /**
     * @param  Collection<int, Dtr>  $records
     * @param  array<string, mixed>  $candidate
     */
    public function conflictsWith(Collection $records, array $candidate, ?string $dayPart): bool
    {
        $records = $records->reject(fn (Dtr $record): bool => $record->entry_source === AutomaticAbsenceService::SOURCE);
        if ($records->isEmpty()) {
            return false;
        }

        $dateIn = Carbon::parse($candidate['date_in'])->toDateString();
        $administrativeRecords = $records
            ->filter(fn (Dtr $record): bool => $this->isAdministrativeDtr($record))
            ->filter(fn (Dtr $record): bool => Carbon::parse($record->date_in)->toDateString() === $dateIn)
            ->values();

        if (app(DtrDayPartService::class)->conflictsWith($administrativeRecords, $dayPart)) {
            return true;
        }

        $newInterval = $this->interval($candidate);
        $newStart = $this->start($candidate);

        foreach ($records->reject(fn (Dtr $record): bool => $this->isAdministrativeDtr($record)) as $record) {
            $existingInterval = $this->interval($record);
            $existingStart = $this->start($record);

            if ($existingInterval && $newInterval) {
                if (
                    $newInterval[0]->lessThan($existingInterval[1])
                    && $existingInterval[0]->lessThan($newInterval[1])
                ) {
                    return true;
                }

                continue;
            }

            if (! $existingStart || ! $newStart) {
                return true;
            }

            // A later open punch is valid after a completed session, but an
            // earlier open punch cannot be followed by another session yet.
            if ($existingInterval && ! $newInterval) {
                if ($newStart->greaterThanOrEqualTo($existingInterval[1])) {
                    continue;
                }

                return true;
            }

            if (! $existingInterval && $newInterval) {
                if ($newInterval[1]->lessThanOrEqualTo($existingStart)) {
                    continue;
                }

                return true;
            }

            return true;
        }

        return false;
    }

    protected function isAdministrativeDtr(Dtr $record): bool
    {
        if (in_array($record->entry_source, [
            DtrDayPartService::SOURCE_LEAVE,
            DtrDayPartService::SOURCE_ABSENCE,
        ], true)) {
            return true;
        }

        $scheduleType = str($record->schedule_type ?? '')->lower();

        return $scheduleType->contains('leave') || $scheduleType->contains('absent');
    }

    /** @param array<string, mixed>|Dtr $record */
    protected function start(array|Dtr $record): ?Carbon
    {
        $dateIn = $record instanceof Dtr ? $record->date_in : ($record['date_in'] ?? null);
        $timeIn = $record instanceof Dtr ? $record->time_in : ($record['time_in'] ?? null);

        return blank($dateIn) || blank($timeIn) ? null : Carbon::parse("{$dateIn} {$timeIn}");
    }

    /**
     * @param  array<string, mixed>|Dtr  $record
     * @return array{0: Carbon, 1: Carbon}|null
     */
    protected function interval(array|Dtr $record): ?array
    {
        $start = $this->start($record);
        if (! $start) {
            return null;
        }

        $dateOut = $record instanceof Dtr ? $record->date_out : ($record['date_out'] ?? null);
        $timeOut = $record instanceof Dtr ? $record->time_out : ($record['time_out'] ?? null);
        if (blank($dateOut) || blank($timeOut)) {
            $scheduleType = $record instanceof Dtr ? $record->schedule_type : ($record['schedule_type'] ?? null);

            // Finalized forgot-to-punch records end at the calendar cutoff.
            return str($scheduleType ?? '')->lower()->contains('forgot')
                ? [$start, $start->copy()->endOfDay()]
                : null;
        }

        $end = Carbon::parse("{$dateOut} {$timeOut}");
        if ($end->lessThan($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }
}
