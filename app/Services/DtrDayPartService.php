<?php

namespace App\Services;

use App\Models\Dtr;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DtrDayPartService
{
    public const WHOLE_DAY = 'whole_day';

    public const MORNING = 'morning';

    public const AFTERNOON = 'afternoon';

    public const UNCLASSIFIED = 'unclassified';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_IMPORTED = 'imported';

    public const SOURCE_LEAVE = 'leave';

    public const SOURCE_ABSENCE = 'absence';

    public const SOURCE_ON_FIELD_DTR = 'on_field_dtr';

    /**
     * @return array<string, string>
     */
    public function dayPartOptions(): array
    {
        return [
            self::MORNING => 'Morning',
            self::AFTERNOON => 'Afternoon',
        ];
    }

    public function normalize(?string $dayPart): string
    {
        $value = Str::of($dayPart ?? self::WHOLE_DAY)
            ->lower()
            ->replace([' ', '-'], '_')
            ->toString();

        return in_array($value, [self::MORNING, self::AFTERNOON, self::UNCLASSIFIED], true)
            ? $value
            : self::WHOLE_DAY;
    }

    public function label(?string $dayPart): string
    {
        return match ($this->normalize($dayPart)) {
            self::MORNING => 'Morning',
            self::AFTERNOON => 'Afternoon',
            self::UNCLASSIFIED => 'Review Required',
            default => 'Whole Day',
        };
    }

    public function isRegularScheduleType(?string $scheduleType): bool
    {
        $normalized = Str::of($scheduleType ?? '')
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();

        return in_array($normalized, [
            'regular',
            'regular1',
            'regular2',
            'regularsched',
            'regularsched1',
            'regularsched2',
            'regularschedule',
            'regularschedule1',
            'regularschedule2',
        ], true);
    }

    public function classifyRegularPunch(
        string $dateIn,
        ?string $timeIn,
        ?string $dateOut,
        ?string $timeOut,
        ?string $scheduleStart,
        ?string $scheduleEnd,
        ?string $scheduleType,
    ): string {
        if (! $this->isRegularScheduleType($scheduleType)
            || blank($timeIn)
            || blank($dateOut)
            || blank($timeOut)
            || blank($scheduleStart)
            || blank($scheduleEnd)
        ) {
            return self::WHOLE_DAY;
        }

        $attendanceDate = Carbon::parse($dateIn)->startOfDay();

        if ($attendanceDate->isWeekend() || ! $attendanceDate->isSameDay(Carbon::parse($dateOut))) {
            return self::WHOLE_DAY;
        }

        $actualStart = Carbon::parse("{$dateIn} {$timeIn}");
        $actualEnd = Carbon::parse("{$dateOut} {$timeOut}");
        $scheduledStart = Carbon::parse("{$dateIn} {$scheduleStart}");
        $scheduledEnd = Carbon::parse("{$dateIn} {$scheduleEnd}");

        if ($actualEnd->lessThanOrEqualTo($actualStart) || $scheduledEnd->lessThanOrEqualTo($scheduledStart)) {
            return self::UNCLASSIFIED;
        }

        $breakStart = Carbon::parse("{$dateIn} 12:00:00");
        $breakEnd = Carbon::parse("{$dateIn} 13:00:00");
        $morningEnd = $scheduledEnd->lessThan($breakStart) ? $scheduledEnd : $breakStart;
        $afternoonStart = $scheduledStart->greaterThan($breakEnd) ? $scheduledStart : $breakEnd;
        $hasMorning = $morningEnd->greaterThan($scheduledStart)
            && $this->overlapMinutes($actualStart, $actualEnd, $scheduledStart, $morningEnd) > 0;
        $hasAfternoon = $scheduledEnd->greaterThan($afternoonStart)
            && $this->overlapMinutes($actualStart, $actualEnd, $afternoonStart, $scheduledEnd) > 0;

        // Punches beginning at 11:31 or later are afternoon-side records.
        // A record that never reaches afternoon work remains review-required.
        $afternoonCutoff = Carbon::parse("{$dateIn} 11:31:00");

        if ($actualStart->greaterThanOrEqualTo($afternoonCutoff)) {
            return $hasAfternoon ? self::AFTERNOON : self::UNCLASSIFIED;
        }

        return match (true) {
            $hasMorning && $hasAfternoon => self::WHOLE_DAY,
            $hasMorning => self::MORNING,
            $hasAfternoon => self::AFTERNOON,
            default => self::UNCLASSIFIED,
        };
    }

    /**
     * @param  Collection<int, Dtr>  $records
     */
    public function openHalfDayPart(Collection $records): ?string
    {
        $parts = $records
            ->map(fn (Dtr $record): string => $this->normalize($record->day_part ?? null))
            ->unique()
            ->values();

        if ($parts->contains(self::WHOLE_DAY)) {
            return null;
        }

        if ($parts->contains(self::MORNING) && ! $parts->contains(self::AFTERNOON)) {
            return self::AFTERNOON;
        }

        if ($parts->contains(self::AFTERNOON) && ! $parts->contains(self::MORNING)) {
            return self::MORNING;
        }

        return null;
    }

    /**
     * @param  Collection<int, Dtr>  $records
     */
    public function conflictsWith(Collection $records, ?string $dayPart): bool
    {
        $newPart = $this->normalize($dayPart);

        return $records->contains(function (Dtr $record) use ($newPart): bool {
            $existingPart = $this->normalize($record->day_part ?? null);

            return $newPart === self::WHOLE_DAY
                || $newPart === self::UNCLASSIFIED
                || $existingPart === self::WHOLE_DAY
                || $existingPart === self::UNCLASSIFIED
                || $existingPart === $newPart;
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function scheduleWindow(
        string $date,
        ?string $scheduleStart,
        ?string $scheduleEnd,
        ?string $dayPart,
    ): array {
        $dayPart = $this->normalize($dayPart);

        if (in_array($dayPart, [self::WHOLE_DAY, self::UNCLASSIFIED], true) || blank($scheduleStart) || blank($scheduleEnd)) {
            return [$scheduleStart, $scheduleEnd];
        }

        $start = Carbon::parse("{$date} {$scheduleStart}");
        $end = Carbon::parse("{$date} {$scheduleEnd}");

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $breakStart = Carbon::parse("{$date} 12:00:00");
        $breakEnd = Carbon::parse("{$date} 13:00:00");

        if ($dayPart === self::MORNING && $end->lessThanOrEqualTo($breakStart)) {
            return [$start->format('H:i:s'), $end->format('H:i:s')];
        }

        if ($dayPart === self::AFTERNOON && $start->greaterThanOrEqualTo($breakEnd)) {
            return [$start->format('H:i:s'), $end->format('H:i:s')];
        }

        if (! $this->windowOverlaps($start, $end, $breakStart, $breakEnd)) {
            $midpoint = $start->copy()->addMinutes((int) floor($start->diffInMinutes($end) / 2));

            return $dayPart === self::MORNING
                ? [$start->format('H:i:s'), $midpoint->format('H:i:s')]
                : [$midpoint->format('H:i:s'), $end->format('H:i:s')];
        }

        return $dayPart === self::MORNING
            ? [$start->format('H:i:s'), $breakStart->format('H:i:s')]
            : [$breakEnd->format('H:i:s'), $end->format('H:i:s')];
    }

    public function payableMinutes(string $date, ?string $scheduleStart, ?string $scheduleEnd, bool $deductNoonBreak): int
    {
        if (blank($scheduleStart) || blank($scheduleEnd)) {
            return 0;
        }

        $start = Carbon::parse("{$date} {$scheduleStart}");
        $end = Carbon::parse("{$date} {$scheduleEnd}");

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $minutes = (int) $start->diffInMinutes($end);

        if (! $deductNoonBreak) {
            return max(0, $minutes);
        }

        $breakStart = Carbon::parse("{$date} 12:00:00");
        $breakEnd = Carbon::parse("{$date} 13:00:00");

        return max(0, $minutes - $this->overlapMinutes($start, $end, $breakStart, $breakEnd));
    }

    public function absenceMinutes(string $date, ?string $dayPart): int
    {
        $dayPart = $this->normalize($dayPart);

        if ($dayPart === self::UNCLASSIFIED) {
            return 0;
        }

        if (Carbon::parse($date)->isSaturday()) {
            return $dayPart === self::WHOLE_DAY ? 180 : 0;
        }

        return $dayPart === self::WHOLE_DAY ? 480 : 240;
    }

    protected function windowOverlaps(Carbon $start, Carbon $end, Carbon $windowStart, Carbon $windowEnd): bool
    {
        return $this->overlapMinutes($start, $end, $windowStart, $windowEnd) > 0;
    }

    protected function overlapMinutes(Carbon $start, Carbon $end, Carbon $windowStart, Carbon $windowEnd): int
    {
        $overlapStart = $start->greaterThan($windowStart) ? $start : $windowStart;
        $overlapEnd = $end->lessThan($windowEnd) ? $end : $windowEnd;

        return $overlapEnd->greaterThan($overlapStart)
            ? (int) $overlapStart->diffInMinutes($overlapEnd)
            : 0;
    }

    public function complementaryDayPart(?string $dayPart): ?string
    {
        return match ($this->normalize($dayPart)) {
            self::MORNING => self::AFTERNOON,
            self::AFTERNOON => self::MORNING,
            default => null,
        };
    }

    /**
     * @param  Collection<int, Dtr>|null  $dateRecords
     * @return array{status: string, is_approved: bool, leave_type: string, day_part: string, is_direct_leave: bool, label: string}|null
     */
    public function findLeaveForRecord(Dtr $record, ?Collection $dateRecords = null): ?array
    {
        if (filled($record->leave_id) || $record->schedule_type === 'Leave' || $record->entry_source === self::SOURCE_LEAVE) {
            $leave = $record->leave ?: (filled($record->leave_id) ? Leave::find($record->leave_id) : null);
            $status = $leave?->status ?? 'Approved';
            $isApproved = $status === 'Approved';
            $type = $leave?->leave_type ?? 'Leave';
            $part = $this->normalize($record->day_part ?: ($leave?->half_day_period ?? self::WHOLE_DAY));
            $partLabel = match ($part) {
                self::MORNING => ' (Morning)',
                self::AFTERNOON => ' (Afternoon)',
                default => '',
            };

            return [
                'status' => $status,
                'is_approved' => $isApproved,
                'leave_type' => $type,
                'day_part' => $part,
                'is_direct_leave' => true,
                'label' => ($isApproved ? 'Approved: ' : ($status . ': ')) . $type . $partLabel,
            ];
        }

        $dayPart = $this->normalize($record->day_part);
        $missingPart = $this->complementaryDayPart($dayPart);
        if (! $missingPart || blank($record->date_in) || blank($record->employee_id)) {
            return null;
        }

        $dateStr = Carbon::parse($record->date_in)->toDateString();

        if ($dateRecords) {
            $matchingDtr = $dateRecords
                ->filter(fn (Dtr $r): bool => Carbon::parse($r->date_in)->toDateString() === $dateStr)
                ->first(function (Dtr $r) use ($missingPart): bool {
                    $isLeave = filled($r->leave_id)
                        || $r->schedule_type === 'Leave'
                        || $r->entry_source === self::SOURCE_LEAVE;
                    if (! $isLeave) {
                        return false;
                    }
                    $part = $this->normalize($r->day_part);

                    return $part === self::WHOLE_DAY || $part === $missingPart;
                });

            if ($matchingDtr) {
                $leave = $matchingDtr->leave ?: (filled($matchingDtr->leave_id) ? Leave::find($matchingDtr->leave_id) : null);
                $type = $leave?->leave_type ?? 'Leave';

                return [
                    'status' => 'Approved',
                    'is_approved' => true,
                    'leave_type' => $type,
                    'day_part' => $missingPart,
                    'is_direct_leave' => false,
                    'label' => 'Approved: ' . $type . ' (' . ucfirst($missingPart) . ')',
                ];
            }
        }

        $leave = Leave::query()
            ->where('employee_id', $record->employee_id)
            ->whereDate('leave_from', '<=', $dateStr)
            ->whereDate('leave_to', '>=', $dateStr)
            ->get()
            ->first(fn (Leave $l): bool => $l->coversDayPart($missingPart));

        if ($leave) {
            $isApproved = $leave->status === 'Approved';
            $partLabel = ' (' . ucfirst($missingPart) . ')';

            return [
                'status' => $leave->status,
                'is_approved' => $isApproved,
                'leave_type' => $leave->leave_type,
                'day_part' => $missingPart,
                'is_direct_leave' => false,
                'label' => ($isApproved ? 'Approved: ' : ($leave->status . ': ')) . $leave->leave_type . $partLabel,
            ];
        }

        return null;
    }

    /**
     * @param  Collection<int, Dtr>|null  $dateRecords
     */
    public function leaveApprovalLabel(Dtr $record, ?Collection $dateRecords = null): string
    {
        $info = $this->findLeaveForRecord($record, $dateRecords);

        return $info ? $info['label'] : '-';
    }

    public function unworkedHalfDayMinutes(Dtr $record, string $missingPart): int
    {
        if (filled($record->date_in) && Carbon::parse($record->date_in)->isSaturday()) {
            return 0;
        }

        return 240;
    }

    /**
     * @param  Collection<int, Dtr>|null  $dateRecords
     */
    public function effectiveLate(Dtr $record, ?Collection $dateRecords = null, bool $forDailyPayroll = false): int
    {
        if ((bool) $record->is_absent || $record->schedule_type === 'Leave' || $record->entry_source === self::SOURCE_LEAVE) {
            return 0;
        }

        $dayPart = $this->normalize($record->day_part);
        $storedLate = (int) ($record->late ?? 0);

        if ($dayPart !== self::AFTERNOON) {
            return $storedLate;
        }

        $unworked = $this->unworkedHalfDayMinutes($record, self::MORNING);
        $punchLate = $storedLate >= $unworked ? $storedLate - $unworked : $storedLate;

        $leaveInfo = $this->findLeaveForRecord($record, $dateRecords);
        $isExcused = ($leaveInfo['is_approved'] ?? false) === true;

        if ($isExcused || $forDailyPayroll) {
            return $punchLate;
        }

        return $unworked + $punchLate;
    }

    /**
     * @param  Collection<int, Dtr>|null  $dateRecords
     */
    public function effectiveUndertime(Dtr $record, ?Collection $dateRecords = null, bool $forDailyPayroll = false): int
    {
        if ((bool) $record->is_absent || $record->schedule_type === 'Leave' || $record->entry_source === self::SOURCE_LEAVE) {
            return 0;
        }

        $dayPart = $this->normalize($record->day_part);
        $storedUndertime = (int) ($record->undertime ?? 0);

        if ($dayPart !== self::MORNING) {
            return $storedUndertime;
        }

        $unworked = $this->unworkedHalfDayMinutes($record, self::AFTERNOON);
        $punchUndertime = $storedUndertime >= $unworked ? $storedUndertime - $unworked : $storedUndertime;

        $leaveInfo = $this->findLeaveForRecord($record, $dateRecords);
        $isExcused = ($leaveInfo['is_approved'] ?? false) === true;

        if ($isExcused || $forDailyPayroll) {
            return $punchUndertime;
        }

        return $unworked + $punchUndertime;
    }
}
