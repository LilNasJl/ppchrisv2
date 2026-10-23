<?php

namespace App\Services;

use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanAdjustment;
use App\Models\EmployeeLoanPayment;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LoanAdjustmentService
{
    public function adjust(EmployeeLoan $loan, array $data, User $actor): EmployeeLoanAdjustment
    {
        app(LoanAccessService::class)->authorizeManagement($actor);

        $values = Validator::make($data, [
            'type' => ['required', 'in:reschedule,correct_total'],
            'new_total' => ['required_if:type,correct_total', 'nullable', 'numeric', 'gt:0', 'max:9999999999.99'],
            'new_loan_amount' => ['nullable', 'numeric', 'gt:0', 'max:9999999999.99'],
            'remaining_deductions' => ['required', 'integer', 'min:1', 'max:240'],
            'effective_payroll_period_id' => ['required', 'integer', 'exists:payroll_periods,id'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ])->validate();

        return DB::transaction(function () use ($loan, $values, $actor): EmployeeLoanAdjustment {
            $loan = EmployeeLoan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();
            $period = PayrollPeriod::query()
                ->whereKey($values['effective_payroll_period_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($loan->status !== EmployeeLoan::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['type' => 'Only active loans can be adjusted.']);
            }

            if ($period->is_locked || ! $period->date_start || ! $loan->hasStartedForPeriod($period)) {
                throw ValidationException::withMessages(['effective_payroll_period_id' => 'Select an open period on or after the loan start.']);
            }

            if (PayrollPeriod::query()->where('is_locked', true)
                ->whereDate('date_start', '>=', $period->date_start)->exists()) {
                throw ValidationException::withMessages(['effective_payroll_period_id' => 'A later payroll period is locked. Choose a period after it or unlock that period first.']);
            }

            $latest = $loan->adjustments()->with('effectivePayrollPeriod')->latest('id')->first();
            if ($latest?->effectivePayrollPeriod?->date_start?->greaterThan($period->date_start)) {
                throw ValidationException::withMessages(['effective_payroll_period_id' => 'Choose a period on or after the previous adjustment.']);
            }

            $postedCents = (int) round((float) $loan->payments()
                ->where('status', EmployeeLoanPayment::STATUS_POSTED)->sum('amount') * 100);
            if ($postedCents !== (int) round((float) $loan->paid_amount * 100)) {
                throw ValidationException::withMessages(['type' => 'Loan payments and paid balance disagree. Reconcile the ledger before adjusting this loan.']);
            }

            $previousTotalCents = (int) round($loan->total_amount * 100);
            $previousLoanAmountCents = (int) round((float) $loan->loan_amount * 100);
            $newTotalCents = $values['type'] === EmployeeLoanAdjustment::TYPE_CORRECT_TOTAL
                ? (int) round((float) $values['new_total'] * 100)
                : $previousTotalCents;
            $newLoanAmountCents = $values['type'] === EmployeeLoanAdjustment::TYPE_CORRECT_TOTAL
                ? (int) round((float) ($values['new_loan_amount'] ?? min((float) $loan->loan_amount, $newTotalCents / 100)) * 100)
                : $previousLoanAmountCents;
            if ($newLoanAmountCents > $newTotalCents) {
                throw ValidationException::withMessages(['new_loan_amount' => 'Loan amount cannot exceed the corrected total owed.']);
            }
            $balanceCents = $newTotalCents - $postedCents;
            $deductions = (int) $values['remaining_deductions'];
            $paymentCents = (int) round($balanceCents / $deductions);

            if ($balanceCents <= 0 || $paymentCents < 1) {
                throw ValidationException::withMessages(['new_total' => 'The corrected total must exceed posted payments and support at least one centavo per deduction.']);
            }

            $adjustment = $loan->adjustments()->create([
                'effective_payroll_period_id' => $period->id,
                'changed_by' => $actor->id,
                'type' => $values['type'],
                'previous_total' => $previousTotalCents / 100,
                'new_total' => $newTotalCents / 100,
                'previous_loan_amount' => $previousLoanAmountCents / 100,
                'new_loan_amount' => $newLoanAmountCents / 100,
                'paid_at_change' => $postedCents / 100,
                'remaining_deductions' => $deductions,
                'payment_amount' => $paymentCents / 100,
                'reason' => trim($values['reason']),
            ]);

            $loan->unsetRelation('adjustments');

            return $adjustment;
        }, 3);
    }
}
