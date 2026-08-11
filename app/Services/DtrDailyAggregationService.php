<?php

namespace App\Services;

use App\Models\Dtr;
use App\Models\PayrollCalculationSetting;
use App\Models\PayrollPeriod;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Collection;

class DtrDailyAggregationService
{
    private static int $suspensionDepth = 0;

    private bool $recalculating = false;

    public function __construct(protected DtrDayPartService $dayPartService) {}

    public static function automaticRecalculationEnabled(): bool
    {
        return self::$suspensionDepth === 0;
    }

    public function withoutAutomaticRecalculation(Closure $callback): mixed
    {
        self::$suspensionDepth++;

        try {
            return $callback();
        } finally {
            self::$suspensionDepth--;
        }
    }

    /**
     * @param  Collection<int, Dtr>  $records
     * @return array<string, mixed>|null
     */
    public function calculate(
        Collection $records,
        float $workHoursPerDay = 8,
        int $lateGraceMinutes = 2,
    ): ?array {
        $completed = $this->completedRegularRecords($records);

        if ($completed->isEmpty()) {
            return null;
        }

        $date = Carbon::parse($completed->first()->date_in)->toDateString();
        $scheduleStarts = $completed
            ->map(fn (Dtr $record): ?int => $this->timeToSeconds($record->schedule_start))
            ->filter(fn (?int $seconds): bool => $seconds !== null);
        $scheduleEnds = $completed
            ->map(fn (Dtr $record): ?int => $this->timeToSeconds($record->schedule_end))
            ->filter(fn (?int $seconds): bool => $seconds !== null);

        if ($scheduleStarts->isEmpty() || $scheduleEnds->isEmpty()) {
            return null;
        }

        $scheduleStart = (int) $scheduleStarts->min();
        $scheduleEnd = (int) $scheduleEnds->max();

        if ($scheduleEnd <= $scheduleStart) {
            return null;
        }

        $intervals = $completed
            ->map(function (Dtr $record): ?array {
                $start = $this->timeToSeconds($record->time_in);
                $end = $this->timeToSeconds($record->time_out);

                return $start !== null && $end !== null && $end > $start
                    ? [$start, $end]
                    : null;
            })
            ->filter()
            ->values();

        if ($intervals->isEmpty()) {
            return null;
        }

        $isSaturday = Carbon::parse($date)->isSaturday();
        $morningSeconds = $this->mergedWindowSeconds(
            $intervals,
            $scheduleStart,
            min($scheduleEnd, 12 * 3600),
        );
        $afternoonSeconds = $this->mergedWindowSeconds(
            $intervals,
            max($scheduleStart, 13 * 3600),
            $scheduleEnd,
        );
        $hasMorning = $morningSeconds > 0;
        $hasAfternoon = $afternoonSeconds > 0;
        $dayPart = match (true) {
            $isSaturday, $hasMorning && $hasAfternoon => DtrDayPartService::WHOLE_DAY,
            $hasMorning => DtrDayPartService::MORNING,
            $hasAfternoon => DtrDayPartService::AFTERNOON,
            default => DtrDayPartService::UNCLASSIFIED,
        };
        $dayCount = match ($dayPart) {
            DtrDayPartService::WHOLE_DAY => 1.0,
            DtrDayPartService::MORNING, DtrDayPartService::AFTERNOON => 0.5,
            default => 0.0,
        };

        $workdayMinutes = max(0, (int) round($workHoursPerDay * 60));
        $capacitySeconds = $this->partCapacitySeconds(
            $dayPart,
            $scheduleStart,
            $scheduleEnd,
            $isSaturday,
        );
        $requiredMinutes = min(
            (int) round($workdayMinutes * $dayCount),
            (int) floor($capacitySeconds / 60),
        );
        $workedSeconds = $this->payableSeconds($intervals, $scheduleStart, $scheduleEnd, $isSaturday);
        $workedMinutes = (int) floor($workedSeconds / 60);
        $creditedWorkMinutes = min($requiredMinutes, $workedMinutes);
        $shortageMinutes = max(0, $requiredMinutes - $creditedWorkMinutes);
        $earliest = (int) $intervals->min(fn (array $interval): int => $interval[0]);
        $latest = (int) $intervals->max(fn (array $interval): int => $interval[1]);
        $effectiveStart = $dayPart === DtrDayPartService::AFTERNOON
            ? max($scheduleStart, 13 * 3600)
            : $scheduleStart;
        $effectiveEnd = $dayPart === DtrDayPartService::MORNING
            ? min($scheduleEnd, 12 * 3600)
            : $scheduleEnd;
        $lateSeconds = max(0, $earliest - $effectiveStart);
        $lateCandidate = $lateSeconds <= max(0, $lateGraceMinutes) * 60
            ? 0
            : (int) floor($this->payableSeconds(
                collect([[$effectiveStart, min($earliest, $effectiveEnd)]]),
                $effectiveStart,
                $effectiveEnd,
                $isSaturday || $dayPart !== DtrDayPartService::WHOLE_DAY,
            ) / 60);
        $late = min($shortageMinutes, $lateCandidate);
        $undertime = max(0, $shortageMinutes - $late);
        $earlyClockIn = $dayPart === DtrDayPartService::AFTERNOON
            ? 0
            : (int) floor(max(0, $effectiveStart - $earliest) / 60);
        $overtime = $dayPart === DtrDayPartService::MORNING
            ? 0
            : (int) floor(max(0, $latest - $effectiveEnd) / 60);
        $representative = $completed
            ->sortBy(fn (Dtr $record): string => sprintf('%s %s', $record->date_out, $record->time_out))
            ->last();

        return [
            'date' => $date,
            'day_part' => $dayPart,
            'day_count' => $dayCount,
            'schedule_start' => $this->secondsToTime($scheduleStart),
            'schedule_end' => $this->secondsToTime($scheduleEnd),
            'first_time_in' => $this->secondsToTime($earliest),
            'last_time_out' => $this->secondsToTime($latest),
            'required_minutes' => $requiredMinutes,
            'worked_minutes' => $workedMinutes,
            'credited_work_minutes' => $creditedWorkMinutes,
            'late' => $late,
            'undertime' => $undertime,
            'early_clock_in' => $earlyClockIn,
            'overtime' => $overtime,
            'overtime_status' => $overtime >= 30 ? 'Pending' : 'n/a',
            'representative' => $representative,
            'completed_records' => $completed,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function recalculateRows(array $rows): int
    {
        $keys = collect($rows)
            ->map(fn (array $row): array => [
                'payroll_period_id' => $row['payroll_period_id'] ?? null,
                'branch_id' => $row['branch_id'] ?? null,
                'fingerprint_id' => $row['fingerprint_id'] ?? null,
                'date_in' => $row['date_in'] ?? null,
            ])
            ->filter(fn (array $key): bool => $this->validKey($key))
            ->unique(fn (array $key): string => $this->keyString($key))
            ->values();

        $keys->each(fn (array $key) => $this->recalculateKey($key));

        return $keys->count();
    }

    public function recalculateRecord(Dtr $record): void
    {
        $this->recalculateKey($this->recordKey($record));
    }

    public function recalculateChangedRecord(Dtr $record): void
    {
        $keys = collect([
            $this->recordKey($record),
            $this->recordKey($record, true),
        ])->filter(fn (array $key): bool => $this->validKey($key))
            ->unique(fn (array $key): string => $this->keyString($key));

        $keys->each(fn (array $key) => $this->recalculateKey($key));
    }

    public function recalculatePeriod(?int $payrollPeriodId = null): int
    {
        $keys = Dtr::query()
            ->when($payrollPeriodId, fn ($query) => $query->where('payroll_period_id', $payrollPeriodId))
            ->whereNotNull('payroll_period_id')
            ->whereNotNull('branch_id')
            ->whereNotNull('fingerprint_id')
            ->whereNotNull('date_in')
            ->get(['payroll_period_id', 'branch_id', 'fingerprint_id', 'date_in'])
            ->map(fn (Dtr $record): array => $this->recordKey($record))
            ->unique(fn (array $key): string => $this->keyString($key))
            ->values();

        $keys->each(fn (array $key) => $this->recalculateKey($key));

        return $keys->count();
    }

    /**
     * @param  array<string, mixed>  $key
     */
    public function recalculateKey(array $key): void
    {
        if ($this->recalculating || ! $this->validKey($key)) {
            return;
        }

        $this->recalculating = true;

        try {
            $records = Dtr::query()
                ->where('payroll_period_id', $key['payroll_period_id'])
                ->where('branch_id', $key['branch_id'])
                ->where('fingerprint_id', (string) $key['fingerprint_id'])
                ->whereDate('date_in', $key['date_in'])
                ->get();
            $period = PayrollPeriod::query()->find($key['payroll_period_id']);
            $settings = $period
                ? PayrollCalculationSetting::forPeriod($period)
                : new PayrollCalculationSetting(PayrollCalculationSetting::DEFAULTS);

            $this->apply(
                $records,
                $settings->divisor('work_hours_per_day'),
                (int) round($settings->value('late_grace_minutes')),
            );
        } finally {
            $this->recalculating = false;
        }
    }

    /**
     * @param  Collection<int, Dtr>  $records
     */
    protected function apply(Collection $records, float $workHoursPerDay, int $lateGraceMinutes): void
    {
        $forgotten = $records->filter(fn (Dtr $record): bool => $this->isForgotToPunch($record));
        $aggregate = $this->calculate($records, $workHoursPerDay, $lateGraceMinutes);

        if (! $aggregate) {
            $forgotten->each(function (Dtr $record) use ($workHoursPerDay): void {
                $absenceMinutes = Carbon::parse($record->date_in)->isSaturday()
                    ? 180
                    : (int) round($workHoursPerDay * 60);
                $record->forceFill([
                    'is_absent' => true,
                    'absence_minutes' => $absenceMinutes,
                    'day_part' => DtrDayPartService::WHOLE_DAY,
                ])->saveQuietly();
            });

            return;
        }

        /** @var Collection<int, Dtr> $completed */
        $completed = $aggregate['completed_records'];
        /** @var Dtr $representative */
        $representative = $aggregate['representative'];
        $overtimeApproved = $completed->contains(fn (Dtr $record): bool => (bool) $record->overtime_approved);
        $earlyApproved = $completed->contains(fn (Dtr $record): bool => (bool) $record->early_clock_in_approved);

        $completed->each(function (Dtr $record): void {
            $record->forceFill([
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
                'is_absent' => false,
                'absence_minutes' => 0,
            ])->saveQuietly();
        });

        $representative->forceFill([
            'schedule_start' => $aggregate['schedule_start'],
            'schedule_end' => $aggregate['schedule_end'],
            'day_part' => $aggregate['day_part'],
            'late' => $aggregate['late'],
            'undertime' => $aggregate['undertime'],
            'early_clock_in' => $aggregate['early_clock_in'],
            'overtime' => $aggregate['overtime'],
            'credited_overtime' => $overtimeApproved ? $aggregate['overtime'] : 0,
            'work_hrs' => $aggregate['credited_work_minutes'] + $aggregate['early_clock_in'] + $aggregate['overtime'],
            'credited_work_hrs' => $aggregate['credited_work_minutes'],
            'overtime_status' => $aggregate['overtime'] >= 30
                ? ($overtimeApproved ? 'Approved' : 'Pending')
                : 'n/a',
            'early_clock_in_approved' => $earlyApproved,
            'overtime_approved' => $overtimeApproved,
            'is_absent' => false,
            'absence_minutes' => 0,
        ])->saveQuietly();

        $forgotten->each(function (Dtr $record): void {
            $record->forceFill([
                'is_absent' => false,
                'absence_minutes' => 0,
            ])->saveQuietly();
        });
    }

    /**
     * @param  Collection<int, Dtr>  $records
     * @return Collection<int, Dtr>
     */
    protected function completedRegularRecords(Collection $records): Collection
    {
        return $records
            ->reject(fn (Dtr $record): bool => (bool) $record->is_absent && ! $this->isForgotToPunch($record))
            ->filter(fn (Dtr $record): bool => $this->dayPartService->isRegularScheduleType($record->schedule_type))
            ->filter(fn (Dtr $record): bool => filled($record->date_in)
                && filled($record->time_in)
                && filled($record->date_out)
                && filled($record->time_out)
                && Carbon::parse($record->date_in)->isSameDay(Carbon::parse($record->date_out)))
            ->values();
    }

    protected function isForgotToPunch(Dtr $record): bool
    {
        return str($record->schedule_type ?? '')->lower()->contains('forgot');
    }

    /**
     * @param  Collection<int, array{0:int, 1:int}>  $intervals
     */
    protected function payableSeconds(Collection $intervals, int $start, int $end, bool $skipLunchDeduction): int
    {
        if ($end <= $start) {
            return 0;
        }

        if ($skipLunchDeduction) {
            return $this->mergedWindowSeconds($intervals, $start, $end);
        }

        return $this->mergedWindowSeconds($intervals, $start, min($end, 12 * 3600))
            + $this->mergedWindowSeconds($intervals, max($start, 13 * 3600), $end);
    }

    protected function partCapacitySeconds(string $dayPart, int $start, int $end, bool $isSaturday): int
    {
        $intervals = collect([[$start, $end]]);

        return match ($dayPart) {
            DtrDayPartService::MORNING => $this->mergedWindowSeconds($intervals, $start, min($end, 12 * 3600)),
            DtrDayPartService::AFTERNOON => $this->mergedWindowSeconds($intervals, max($start, 13 * 3600), $end),
            DtrDayPartService::WHOLE_DAY => $this->payableSeconds($intervals, $start, $end, $isSaturday),
            default => 0,
        };
    }

    /**
     * @param  Collection<int, array{0:int, 1:int}>  $intervals
     */
    protected function mergedWindowSeconds(Collection $intervals, int $windowStart, int $windowEnd): int
    {
        if ($windowEnd <= $windowStart) {
            return 0;
        }

        $clipped = $intervals
            ->map(fn (array $interval): array => [
                max($interval[0], $windowStart),
                min($interval[1], $windowEnd),
            ])
            ->filter(fn (array $interval): bool => $interval[1] > $interval[0])
            ->sortBy(fn (array $interval): string => sprintf('%010d-%010d', $interval[0], $interval[1]))
            ->values();

        if ($clipped->isEmpty()) {
            return 0;
        }

        [$currentStart, $currentEnd] = $clipped->first();
        $total = 0;

        foreach ($clipped->slice(1) as [$start, $end]) {
            if ($start <= $currentEnd) {
                $currentEnd = max($currentEnd, $end);

                continue;
            }

            $total += $currentEnd - $currentStart;
            $currentStart = $start;
            $currentEnd = $end;
        }

        return $total + $currentEnd - $currentStart;
    }

    protected function timeToSeconds(mixed $time): ?int
    {
        if (blank($time)) {
            return null;
        }

        $parts = array_map('intval', explode(':', (string) $time));

        if (count($parts) < 2) {
            return null;
        }

        return $parts[0] * 3600 + $parts[1] * 60 + ($parts[2] ?? 0);
    }

    protected function secondsToTime(int $seconds): string
    {
        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }

    /**
     * @return array<string, mixed>
     */
    protected function recordKey(Dtr $record, bool $original = false): array
    {
        $value = fn (string $key): mixed => $original ? $record->getOriginal($key) : $record->{$key};

        return [
            'payroll_period_id' => $value('payroll_period_id'),
            'branch_id' => $value('branch_id'),
            'fingerprint_id' => $value('fingerprint_id'),
            'date_in' => $value('date_in'),
        ];
    }

    /**
     * @param  array<string, mixed>  $key
     */
    protected function validKey(array $key): bool
    {
        return filled($key['payroll_period_id'] ?? null)
            && filled($key['branch_id'] ?? null)
            && filled($key['fingerprint_id'] ?? null)
            && filled($key['date_in'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $key
     */
    protected function keyString(array $key): string
    {
        return implode('|', [
            $key['payroll_period_id'] ?? '',
            $key['branch_id'] ?? '',
            $key['fingerprint_id'] ?? '',
            filled($key['date_in'] ?? null) ? Carbon::parse($key['date_in'])->toDateString() : '',
        ]);
    }
}
