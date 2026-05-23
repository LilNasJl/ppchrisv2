<?php

namespace App\Services;

use App\Models\Deduction;
use App\Models\EmployeeDeduction;
use App\Models\PayrollPeriod;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class PayrollPeriodLockService
{
    public function setLocked(PayrollPeriod $period, bool $locked): void
    {
        if ($locked) {
            $this->lock($period);

            return;
        }

        $period->forceFill([
            'is_locked' => false,
        ])->save();
    }

    public function lock(PayrollPeriod $period): void
    {
        DB::transaction(function () use ($period): void {
            $period->refresh();

            if (! $period->is_locked) {
                app(PayrollCalculator::class)->snapshotPeriod($period);

                $period->forceFill([
                    'is_locked' => true,
                    'locked_at' => $period->locked_at ?: now(),
                ])->save();
            }

            if (blank($period->deductions_processed_at)) {
                $this->processDeductionTerms($period);

                $period->forceFill([
                    'deductions_processed_at' => now(),
                ])->save();
            }
        });
    }

    public function lockPastPayoutPeriods(?CarbonInterface $date = null): int
    {
        $today = ($date ?: now('Asia/Manila'))->copy()->timezone('Asia/Manila')->toDateString();
        $locked = 0;

        PayrollPeriod::query()
            ->where('is_locked', false)
            ->whereDate('date_payout', '<', $today)
            ->orderBy('date_payout')
            ->get()
            ->each(function (PayrollPeriod $period) use (&$locked): void {
                $this->lock($period);
                $locked++;
            });

        return $locked;
    }

    protected function processDeductionTerms(PayrollPeriod $period): void
    {
        $employeeIds = app(PayrollCalculator::class)->includedEmployeeIds($period);

        if ($employeeIds->isEmpty()) {
            return;
        }

        EmployeeDeduction::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('active', true)
            ->where('term_type', Deduction::TERM_FIXED)
            ->where('amount', '>', 0)
            ->where('remaining_terms', '>', 0)
            ->get()
            ->each(function (EmployeeDeduction $employeeDeduction): void {
                $remainingTerms = max(0, ((int) $employeeDeduction->remaining_terms) - 1);

                $employeeDeduction->forceFill([
                    'remaining_terms' => $remainingTerms,
                    'active' => $remainingTerms > 0,
                    'completed_at' => $remainingTerms > 0 ? null : now(),
                ])->save();
            });
    }
}
