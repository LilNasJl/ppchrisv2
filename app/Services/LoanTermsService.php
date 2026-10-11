<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\PayrollPeriod;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LoanTermsService
{
    public function validate(array $data, string $periodField, string $termsBasis): array
    {
        $monthly = $termsBasis === EmployeeLoan::TERMS_BASIS_CALENDAR_MONTH;
        $validator = Validator::make($data, [
            'employee_id' => ['sometimes', 'required', 'integer', 'exists:employees,id'],
            'loan_type' => ['required', 'string', 'max:191'],
            'loan_date' => ['nullable', 'date'],
            'loan_amount' => ['required', 'numeric', 'gt:0'],
            'loan_interest' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => [$monthly ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'],
            'loan_terms_months' => ['required', 'integer', 'min:1'],
            'payment_amount' => [$monthly ? 'nullable' : 'required', 'numeric', $monthly ? 'min:0' : 'gt:0'],
            'schedule' => ['required', 'in:'.implode(',', array_keys(EmployeeLoan::scheduleOptions()))],
            $periodField => ['required', 'integer', 'exists:payroll_periods,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'hr_comment' => ['nullable', 'string', 'max:2000'],
        ]);
        $validator->after(function ($validator) use ($data, $periodField, $monthly): void {
            $period = PayrollPeriod::query()->whereKey($data[$periodField] ?? null)->lockForUpdate()->first();
            if (! $period || $period->is_locked || ! $period->date_start) {
                $validator->errors()->add($periodField, 'Select an open payroll period with a start date.');
            }
            if (isset($data['employee_id'])) {
                $employee = Employee::find($data['employee_id']);
                if (! $employee || $employee->hasEndedEmployment()) {
                    $validator->errors()->add('employee_id', 'Select an actively employed employee.');
                }
            }
            if (! $monthly && (float) ($data['payment_amount'] ?? 0) * (int) ($data['loan_terms_months'] ?? 0) + 0.001
                < (float) ($data['loan_amount'] ?? 0) + (float) ($data['loan_interest'] ?? 0)) {
                $validator->errors()->add('payment_amount', 'Payments must cover the total loan within the selected payroll terms.');
            }
        });
        $values = $validator->validate();
        $values['loan_amount'] = round((float) $values['loan_amount'], 2);
        $values['loan_terms_months'] = (int) $values['loan_terms_months'];
        $values['interest_rate'] = isset($values['interest_rate']) ? round((float) $values['interest_rate'], 4) : null;
        $values['loan_interest'] = $monthly
            ? EmployeeLoan::flatAddOnInterest($values['loan_amount'], $values['interest_rate'], $values['loan_terms_months'])
            : round((float) ($values['loan_interest'] ?? 0), 2);
        $values['payment_amount'] = $monthly
            ? EmployeeLoan::plannedPaymentAmount($values['loan_amount'], $values['loan_interest'], $values['loan_terms_months'], $values['schedule'], $termsBasis)
            : round((float) $values['payment_amount'], 2);
        if ($values['loan_amount'] <= 0 || $values['payment_amount'] <= 0) {
            throw ValidationException::withMessages(['loan_amount' => 'The loan amount and scheduled payment must be at least one centavo.']);
        }

        return $values;
    }

    public function preview(array $data, string $periodField): array
    {
        $basis = $data['terms_basis'] ?? EmployeeLoan::TERMS_BASIS_CALENDAR_MONTH;
        $terms = max(1, (int) ($data['loan_terms_months'] ?? 1));
        $principal = max(0, (float) ($data['loan_amount'] ?? 0));
        $interest = $basis === EmployeeLoan::TERMS_BASIS_CALENDAR_MONTH
            ? EmployeeLoan::flatAddOnInterest($principal, $data['interest_rate'] ?? 0, $terms)
            : max(0, (float) ($data['loan_interest'] ?? 0));
        $count = EmployeeLoan::scheduledDeductionsCount($terms, $data['schedule'] ?? null, $basis);
        $payment = $basis === EmployeeLoan::TERMS_BASIS_CALENDAR_MONTH
            ? EmployeeLoan::plannedPaymentAmount($principal, $interest, $terms, $data['schedule'] ?? null, $basis)
            : max(0, (float) ($data['payment_amount'] ?? 0));
        $total = round($principal + $interest, 2);
        $start = PayrollPeriod::find($data[$periodField] ?? null);
        $first = null;
        if ($start?->date_start) {
            $loan = new EmployeeLoan(['schedule' => $data['schedule'] ?? EmployeeLoan::SCHEDULE_EVERY_PAYROLL]);
            $first = PayrollPeriod::query()->where('is_locked', false)->whereDate('date_start', '>=', $start->date_start)
                ->orderBy('date_start')->get()->first(fn ($period) => $loan->isScheduledForPeriod($period));
        }

        return ['interest' => $interest, 'total' => $total, 'deductions' => $count,
            'payment' => $payment, 'final_payment' => round(max(0, $total - $payment * ($count - 1)), 2),
            'first_period' => $first?->title ?? 'Awaiting a matching open payroll period'];
    }
}
