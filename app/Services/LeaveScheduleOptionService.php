<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Str;

class LeaveScheduleOptionService
{
    /**
     * @return array<string, string>
     */
    public function optionsForEmployee(?Employee $employee): array
    {
        $branch = $employee?->branch;

        if (! $branch) {
            return [];
        }

        $pairs = [
            'regular' => ['Regular', 'reg_sched_start', 'reg_sched_end'],
            'shift1' => ['1st Shift', 'shift1_start', 'shift1_end'],
            'shift2' => ['2nd Shift', 'shift2_start', 'shift2_end'],
            'shift3' => ['3rd Shift', 'shift3_start', 'shift3_end'],
            'broken_shift1' => ['1st Broken Shift', 'broken_shift1_start', 'broken_shift1_end'],
            'broken_shift2' => ['2nd Broken Shift', 'broken_shift2_start', 'broken_shift2_end'],
            'broken_shift3' => ['3rd Broken Shift', 'broken_shift3_start', 'broken_shift3_end'],
        ];

        $options = [];

        foreach ($pairs as $key => [$label, $startColumn, $endColumn]) {
            $start = $branch->{$startColumn} ?? null;
            $end = $branch->{$endColumn} ?? null;

            if (blank($start) || blank($end)) {
                continue;
            }

            $options[$key] = "{$label} ({$this->timeLabel($start)} - {$this->timeLabel($end)})";
        }

        return $options;
    }

    /**
     * @return array{start: string, end: string, deduct_noon_break: bool, type: string}
     */
    public function scheduleForLeave(Employee $employee, ?string $scheduleKey = null): array
    {
        $scheduleKey = $this->normalizeScheduleKey($scheduleKey);
        $branch = $employee->branch;

        $columns = match ($scheduleKey) {
            'shift1' => ['shift1_start', 'shift1_end'],
            'shift2' => ['shift2_start', 'shift2_end'],
            'shift3' => ['shift3_start', 'shift3_end'],
            'broken_shift1' => ['broken_shift1_start', 'broken_shift1_end'],
            'broken_shift2' => ['broken_shift2_start', 'broken_shift2_end'],
            'broken_shift3' => ['broken_shift3_start', 'broken_shift3_end'],
            default => ['reg_sched_start', 'reg_sched_end'],
        };

        $start = $branch?->{$columns[0]} ?? null;
        $end = $branch?->{$columns[1]} ?? null;

        if (filled($start) && filled($end)) {
            return [
                'start' => $this->timeValue($start),
                'end' => $this->timeValue($end),
                'deduct_noon_break' => $scheduleKey === 'regular',
                'type' => $scheduleKey,
            ];
        }

        return $this->fallbackSchedule($employee);
    }

    public function isDailyRateEmployee(?Employee $employee): bool
    {
        return Str::of($employee?->rate_type ?? '')->lower()->contains('daily');
    }

    public function normalizeScheduleKey(?string $scheduleKey): string
    {
        $scheduleKey = Str::of($scheduleKey ?? 'regular')
            ->lower()
            ->replace([' ', '-'], '_')
            ->toString();

        return in_array($scheduleKey, [
            'regular',
            'shift1',
            'shift2',
            'shift3',
            'broken_shift1',
            'broken_shift2',
            'broken_shift3',
        ], true)
            ? $scheduleKey
            : 'regular';
    }

    /**
     * @return array{start: string, end: string, deduct_noon_break: bool, type: string}
     */
    protected function fallbackSchedule(Employee $employee): array
    {
        $designation = Str::lower((string) $employee->designation?->title);

        if ($this->isDailyRateEmployee($employee) && Str::contains($designation, ['cashier/forecourt attendant', 'forecourt attendant'])) {
            return [
                'start' => '04:40:00',
                'end' => '12:30:00',
                'deduct_noon_break' => false,
                'type' => 'fallback_forecourt',
            ];
        }

        return [
            'start' => '08:00:00',
            'end' => '18:00:00',
            'deduct_noon_break' => ! $this->isDailyRateEmployee($employee),
            'type' => 'fallback_regular',
        ];
    }

    protected function timeLabel(mixed $time): string
    {
        return date('h:i A', strtotime((string) $time));
    }

    protected function timeValue(mixed $time): string
    {
        return date('H:i:s', strtotime((string) $time));
    }
}
