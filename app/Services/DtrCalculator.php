<?php

namespace App\Services;

use Carbon\Carbon;

class DtrCalculator
{
    public function calculate(
        string $dateIn,
        string $timeIn,
        string $dateOut,
        string $timeOut,
        ?string $scheduleStart,
        ?string $scheduleEnd,
        ?string $scheduleStartColumn = null,
        ?string $scheduleType = null,
        bool $overtimeOnly = false,
    ): array {
        $actualIn = Carbon::parse("{$dateIn} {$timeIn}");
        $actualOut = Carbon::parse("{$dateOut} {$timeOut}");

        if ($actualOut->lessThan($actualIn)) {
            $actualOut->addDay();
        }

        if ($overtimeOnly) {
            return $this->overtimeOnlyCalculation($actualIn, $actualOut);
        }

        if (blank($scheduleStart) || blank($scheduleEnd)) {
            return $this->emptyCalculationData();
        }

        $scheduleStartAt = Carbon::parse("{$dateIn} {$scheduleStart}");
        $scheduleEndAt = Carbon::parse("{$dateIn} {$scheduleEnd}");

        if ($scheduleEndAt->lessThanOrEqualTo($scheduleStartAt)) {
            $scheduleEndAt->addDay();
        }

        $breaks = $this->breaksFor($scheduleStartColumn, $scheduleType, $scheduleStartAt, $scheduleEndAt);
        $scheduledMinutes = $this->payableMinutesBetween($scheduleStartAt, $scheduleEndAt, $breaks);

        $late = $actualIn->greaterThan($scheduleStartAt)
            ? $this->payableMinutesBetween($scheduleStartAt, $actualIn->min($scheduleEndAt), $breaks)
            : 0;

        $undertime = $actualOut->lessThan($scheduleEndAt)
            ? $this->payableMinutesBetween($actualOut->max($scheduleStartAt), $scheduleEndAt, $breaks)
            : 0;

        $earlyClockIn = $actualIn->lessThan($scheduleStartAt)
            ? (int) $actualIn->diffInMinutes($scheduleStartAt)
            : 0;

        $overtime = $actualOut->greaterThan($scheduleEndAt)
            ? (int) $scheduleEndAt->diffInMinutes($actualOut)
            : 0;

        $workMinutes = max(0, $scheduledMinutes - $late - $undertime + $earlyClockIn + $overtime);
        $creditedWorkMinutes = max(0, $scheduledMinutes - $late - $undertime);
        $hasPendingOvertime = $overtime >= 30;

        return [
            'late' => $late,
            'undertime' => $undertime,
            'overtime' => $overtime,
            'early_clock_in' => $earlyClockIn,
            'credited_overtime' => 0,
            'work_hrs' => $workMinutes,
            'credited_work_hrs' => $creditedWorkMinutes,
            'overtime_status' => $hasPendingOvertime ? 'Pending' : 'n/a',
            'early_clock_in_approved' => false,
            'overtime_approved' => false,
        ];
    }

    public function emptyCalculationData(bool $isAbsent = false): array
    {
        return [
            'late' => 0,
            'undertime' => 0,
            'early_clock_in' => 0,
            'overtime' => 0,
            'credited_overtime' => 0,
            'work_hrs' => 0,
            'credited_work_hrs' => 0,
            'overtime_status' => 'n/a',
            'early_clock_in_approved' => false,
            'overtime_approved' => false,
            'is_absent' => $isAbsent,
        ];
    }

    protected function overtimeOnlyCalculation(Carbon $actualIn, Carbon $actualOut): array
    {
        $overtime = max(0, (int) $actualIn->diffInMinutes($actualOut));

        return [
            'late' => 0,
            'undertime' => 0,
            'overtime' => $overtime,
            'early_clock_in' => 0,
            'credited_overtime' => 0,
            'work_hrs' => $overtime,
            'credited_work_hrs' => 0,
            'overtime_status' => $overtime >= 30 ? 'Pending' : 'n/a',
            'early_clock_in_approved' => false,
            'overtime_approved' => false,
        ];
    }

    /**
     * @return array<int, array{0: Carbon, 1: Carbon}>
     */
    protected function breaksFor(?string $scheduleStartColumn, ?string $scheduleType, Carbon $scheduleStart, Carbon $scheduleEnd): array
    {
        if ($scheduleStartColumn !== 'reg_sched_start') {
            return [];
        }

        if (str($scheduleType ?? '')->lower()->contains('saturday')) {
            return [];
        }

        $breakStart = $scheduleStart->copy()->setTime(12, 0);
        $breakEnd = $scheduleStart->copy()->setTime(13, 0);

        return $this->overlapMinutes($scheduleStart, $scheduleEnd, $breakStart, $breakEnd) > 0
            ? [[$breakStart, $breakEnd]]
            : [];
    }

    /**
     * @param  array<int, array{0: Carbon, 1: Carbon}>  $breaks
     */
    protected function payableMinutesBetween(Carbon $start, Carbon $end, array $breaks = []): int
    {
        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        $minutes = (int) $start->diffInMinutes($end);

        foreach ($breaks as [$breakStart, $breakEnd]) {
            $minutes -= $this->overlapMinutes($start, $end, $breakStart, $breakEnd);
        }

        return max(0, $minutes);
    }

    protected function overlapMinutes(Carbon $start, Carbon $end, Carbon $windowStart, Carbon $windowEnd): int
    {
        $overlapStart = $start->greaterThan($windowStart) ? $start : $windowStart;
        $overlapEnd = $end->lessThan($windowEnd) ? $end : $windowEnd;

        return $overlapEnd->greaterThan($overlapStart)
            ? (int) $overlapStart->diffInMinutes($overlapEnd)
            : 0;
    }
}
