<?php

namespace App\Services;

use App\Models\Dtr;
use Illuminate\Support\Collection;

class Shift3PremiumService
{
    public const RATE = 0.10;

    public function qualifies(Dtr $record): bool
    {
        return $record->getTable() === (new Dtr)->getTable()
            && (bool) $record->is_imported
            && $record->entry_source === DtrDayPartService::SOURCE_IMPORTED
            && ! $record->is_absent
            && ! $record->requiresAttendanceApproval()
            && $this->normalizeScheduleType($record->schedule_type) === 'shift3'
            && $this->regularWorkMinutes($record) > 0;
    }

    public function amountForRecord(Dtr $record, float $fallbackDailyRate, float $workHoursPerDay): float
    {
        if (! $this->qualifies($record) || $workHoursPerDay <= 0) {
            return 0.0;
        }

        $dailyRate = (float) ($record->daily_rate ?: $fallbackDailyRate);

        if ($dailyRate <= 0) {
            return 0.0;
        }

        $regularMinutes = min(
            $this->regularWorkMinutes($record),
            (int) round($workHoursPerDay * 60),
        );
        $regularGrossPay = ($regularMinutes / 60) * ($dailyRate / $workHoursPerDay);

        return $this->money($regularGrossPay * self::RATE);
    }

    /**
     * @param  Collection<int, Dtr>  $records
     */
    public function total(Collection $records, float $fallbackDailyRate, float $workHoursPerDay): float
    {
        return $this->money(
            $records->sum(fn (Dtr $record): float => $this->amountForRecord(
                $record,
                $fallbackDailyRate,
                $workHoursPerDay,
            ))
        );
    }

    public function regularWorkMinutes(Dtr $record): int
    {
        $creditedWorkMinutes = max(0, (int) ($record->credited_work_hrs ?? 0));
        $creditedOvertimeMinutes = max(0, (int) ($record->credited_overtime ?? 0));

        return max(0, $creditedWorkMinutes - min($creditedWorkMinutes, $creditedOvertimeMinutes));
    }

    protected function normalizeScheduleType(mixed $scheduleType): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', strtolower(trim((string) $scheduleType)));
    }

    protected function money(float $amount): float
    {
        return round($amount + 0.0000001, 2);
    }
}
