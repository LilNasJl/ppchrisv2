<?php

namespace App\Services;

use App\Models\Dtr;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Resolve leave coverage once per date; never mutate the source rows during calculation. */
class LeavePayService
{
    public function isLeave(Dtr $row): bool
    {
        return filled($row->leave_id) || $row->entry_source === DtrDayPartService::SOURCE_LEAVE
            || Str::lower((string) $row->schedule_type) === 'leave';
    }

    public function isApproved(Dtr $row): bool
    {
        // Finalized legacy manual leave rows have no leave_id.
        return $this->isLeave($row) && (! $row->leave_id || $row->leave?->status === 'Approved');
    }

    public function effectiveRecords(Collection $records): Collection
    {
        $approved = $records->filter(fn (Dtr $row): bool => filled($row->date_in) && $this->isApproved($row));
        $coverage = $approved->groupBy(fn (Dtr $row): string => Carbon::parse($row->date_in)->toDateString())
            ->map(fn (Collection $rows): int => $this->coverage($rows));

        $actualDates = $records->filter(fn (Dtr $row): bool => filled($row->date_in)
            && ! $row->is_absent && ! $this->isLeave($row))
            ->mapWithKeys(fn (Dtr $row): array => [Carbon::parse($row->date_in)->toDateString() => true]);

        return $records->reject(fn (Dtr $row): bool => $this->isLeave($row) && ! $this->isApproved($row))
            ->map(function (Dtr $row) use ($coverage, $actualDates): ?Dtr {
                if (! $row->is_absent || blank($row->date_in)) {
                    return $row;
                }
                if ($row->entry_source === AutomaticAbsenceService::SOURCE
                    && $actualDates->has(Carbon::parse($row->date_in)->toDateString())) {
                    return null; // historical generated rows cannot duplicate half-day deductions
                }
                $mask = $this->mask($row->day_part);
                $remaining = $mask & ~($coverage->get(Carbon::parse($row->date_in)->toDateString()) ?? 0);
                if ($remaining === 0) {
                    return null;
                }
                if ($remaining === $mask) {
                    return $row;
                }
                $effective = clone $row;
                $effective->day_part = $remaining === 1 ? DtrDayPartService::MORNING : DtrDayPartService::AFTERNOON;
                $effective->absence_minutes = $row->absence_minutes
                    ? (int) round($row->absence_minutes / 2)
                    : app(DtrDayPartService::class)->absenceMinutes($row->date_in, $effective->day_part);

                return $effective;
            })->filter()->values();
    }

    public function totals(Collection $records, float $workHoursPerDay, float $halfDayValue): array
    {
        $totals = ['paid_days' => 0.0, 'unpaid_days' => 0.0, 'unpaid_minutes' => 0.0];
        foreach ($records->filter(fn (Dtr $row): bool => filled($row->date_in))
            ->groupBy(fn (Dtr $row): string => Carbon::parse($row->date_in)->toDateString()) as $date => $rows) {
            $paid = 0;
            $unpaid = 0;
            $worked = 0;
            foreach ($rows as $row) {
                if ($this->isApproved($row)) {
                    if (! $row->leave_id || $row->leave->isPaid()) {
                        $paid |= $this->mask($row->day_part);
                    } else {
                        $unpaid |= $this->mask($row->day_part);
                    }
                } elseif (! $row->is_absent && ! $this->isLeave($row) && ! $row->requiresAttendanceApproval()
                    && Str::lower((string) $row->schedule_type) !== 'overtime') {
                    $worked |= $this->mask(app(DtrAttendanceUnitService::class)->dayPartForRecord($row));
                }
            }
            // Conflicting source data cannot pay or deduct the same worked half twice.
            $paid &= ~$worked;
            $unpaid &= ~($worked | $paid);
            $paidDays = $this->units($paid, 0.5);
            $unpaidDays = $this->units($unpaid, $halfDayValue);
            if (Carbon::parse($date)->isSaturday()) {
                $paidDays = $paid ? 0.5 : 0.0;
                $unpaidDays = $unpaid ? 180 / ($workHoursPerDay * 60) : 0.0;
            }
            $totals['paid_days'] += $paidDays;
            $totals['unpaid_days'] += $unpaidDays;
            $totals['unpaid_minutes'] += $unpaidDays * $workHoursPerDay * 60;
        }

        return $totals;
    }

    private function coverage(Collection $rows): int
    {
        return $rows->reduce(fn (int $mask, Dtr $row): int => $mask | $this->mask($row->day_part), 0);
    }

    private function mask(?string $part): int
    {
        return match (app(DtrDayPartService::class)->normalize($part)) {
            DtrDayPartService::MORNING => 1,
            DtrDayPartService::AFTERNOON => 2,
            DtrDayPartService::UNCLASSIFIED => 0,
            default => 3,
        };
    }

    private function units(int $mask, float $halfDayValue): float
    {
        return $mask === 3 ? 1.0 : ($mask ? $halfDayValue : 0.0);
    }
}
