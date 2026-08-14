<?php

namespace App\Services;

use App\Exceptions\PendingOvertimeApprovalsException;
use App\Models\Deduction;
use App\Models\EmployeeDeduction;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanPayment;
use App\Models\PayrollPeriod;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollPeriodLockService
{
    public function setLocked(PayrollPeriod $period, bool $locked): void
    {
        if ($locked) {
            $this->lock($period);

            return;
        }

        $this->unlock($period);
    }

    public function lock(PayrollPeriod $period): void
    {
        DB::transaction(function () use ($period): void {
            $period->refresh();

            if (! $period->is_locked) {
                if (app(OvertimeApprovalService::class)->hasPendingForPeriod($period)) {
                    throw new PendingOvertimeApprovalsException;
                }

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

            if (blank($period->loan_payments_processed_at)) {
                $this->processLoanPayments($period);

                $period->forceFill([
                    'loan_payments_processed_at' => now(),
                ])->save();
            }
        });
    }

    public function unlock(PayrollPeriod $period): void
    {
        DB::transaction(function () use ($period): void {
            $period->refresh();

            $this->rollbackLoanPayments($period);

            $period->forceFill([
                'is_locked' => false,
                'loan_payments_processed_at' => null,
            ])->save();
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
                try {
                    $this->lock($period);
                    $locked++;
                } catch (PendingOvertimeApprovalsException $exception) {
                    Log::warning('Automatic payroll lock skipped because overtime approval is pending.', [
                        'payroll_period_id' => $period->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
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

    protected function processLoanPayments(PayrollPeriod $period): void
    {
        $employeeIds = app(PayrollCalculator::class)->includedEmployeeIds($period);

        if ($employeeIds->isEmpty()) {
            return;
        }

        EmployeeLoan::query()
            ->with('amortizationStartPayrollPeriod')
            ->whereIn('employee_id', $employeeIds)
            ->where('status', EmployeeLoan::STATUS_ACTIVE)
            ->where('payment_amount', '>', 0)
            ->get()
            ->filter(fn (EmployeeLoan $loan): bool => $loan->canPostPaymentForPeriod($period))
            ->each(function (EmployeeLoan $loan) use ($period): void {
                $existingPayment = EmployeeLoanPayment::query()
                    ->where('employee_loan_id', $loan->id)
                    ->where('payroll_period_id', $period->id)
                    ->first();

                if ($existingPayment?->status === EmployeeLoanPayment::STATUS_POSTED) {
                    return;
                }

                $balance = $loan->balance_amount;

                if ($balance <= 0) {
                    $loan->forceFill(['status' => EmployeeLoan::STATUS_PAID])->save();

                    return;
                }

                $payment = min((float) $loan->payment_amount, $balance);
                $balanceAfter = round(max(0, $balance - $payment), 2);

                $paymentRecord = $existingPayment ?: new EmployeeLoanPayment([
                    'employee_loan_id' => $loan->id,
                    'payroll_period_id' => $period->id,
                ]);

                $paymentRecord->fill([
                    'amount' => $payment,
                    'balance_after' => $balanceAfter,
                    'processed_at' => now(),
                    'status' => EmployeeLoanPayment::STATUS_POSTED,
                    'voided_at' => null,
                    'void_reason' => null,
                ]);
                $paymentRecord->save();

                $loan->forceFill([
                    'paid_amount' => round((float) $loan->paid_amount + $payment, 2),
                    'status' => $balanceAfter <= 0 ? EmployeeLoan::STATUS_PAID : EmployeeLoan::STATUS_ACTIVE,
                ])->save();
            });
    }

    protected function rollbackLoanPayments(PayrollPeriod $period): void
    {
        EmployeeLoanPayment::query()
            ->with('loan')
            ->where('payroll_period_id', $period->id)
            ->where('status', EmployeeLoanPayment::STATUS_POSTED)
            ->get()
            ->each(function (EmployeeLoanPayment $payment): void {
                $loan = $payment->loan;

                if (! $loan) {
                    return;
                }

                $loan->forceFill([
                    'paid_amount' => round(max(0, (float) $loan->paid_amount - (float) $payment->amount), 2),
                    'status' => EmployeeLoan::STATUS_ACTIVE,
                ])->save();

                $payment->forceFill([
                    'status' => EmployeeLoanPayment::STATUS_VOIDED,
                    'voided_at' => now(),
                    'void_reason' => 'Payroll period unlocked',
                ])->save();
            });
    }
}
