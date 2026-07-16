<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRequest;
use App\Models\PayrollPeriod;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EmployeeLoanRequestService
{
    public const MAX_PENDING_REQUESTS = 3;

    public function create(Employee $employee, array $data): EmployeeLoanRequest
    {
        if ($employee->hasEndedEmployment()) {
            throw ValidationException::withMessages([
                'loan_type' => 'Employees with ended employment cannot request a loan.',
            ]);
        }

        if ($employee->loanRequests()->where('status', EmployeeLoanRequest::STATUS_PENDING)->count() >= self::MAX_PENDING_REQUESTS) {
            throw ValidationException::withMessages([
                'loan_type' => 'You already have three pending loan requests. Please wait for HR to review an existing request.',
            ]);
        }

        $termsBasis = EmployeeLoan::TERMS_BASIS_CALENDAR_MONTH;
        $loanData = $this->validateLoanData($data, 'preferred_start_payroll_period_id', $termsBasis);

        return EmployeeLoanRequest::query()->create([
            'employee_id' => $employee->id,
            'preferred_start_payroll_period_id' => $loanData['preferred_start_payroll_period_id'],
            'loan_type' => $loanData['loan_type'],
            'request_date' => now()->toDateString(),
            'loan_amount' => $loanData['loan_amount'],
            'loan_interest' => $loanData['loan_interest'],
            'interest_rate' => $loanData['interest_rate'],
            'loan_terms_months' => $loanData['loan_terms_months'],
            'terms_basis' => $termsBasis,
            'payment_amount' => $loanData['payment_amount'],
            'schedule' => $loanData['schedule'],
            'reason' => $loanData['reason'],
            'status' => EmployeeLoanRequest::STATUS_PENDING,
        ]);
    }

    public function approve(EmployeeLoanRequest $request, array $data, User $reviewer): EmployeeLoan
    {
        return DB::transaction(function () use ($request, $data, $reviewer): EmployeeLoan {
            $request = EmployeeLoanRequest::query()
                ->with('employee')
                ->lockForUpdate()
                ->findOrFail($request->id);

            if ($request->status !== EmployeeLoanRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'loan_type' => 'Only pending loan requests can be approved.',
                ]);
            }

            if (! $request->employee || $request->employee->hasEndedEmployment()) {
                throw ValidationException::withMessages([
                    'loan_type' => 'This employee is no longer eligible for loan approval.',
                ]);
            }

            $termsBasis = EmployeeLoan::normalizeTermsBasis($request->terms_basis);
            $loanData = $this->validateLoanData($data, 'amortization_start_payroll_period_id', $termsBasis);

            $loan = EmployeeLoan::query()->create([
                'employee_id' => $request->employee_id,
                'amortization_start_payroll_period_id' => $loanData['amortization_start_payroll_period_id'],
                'loan_type' => $loanData['loan_type'],
                'loan_date' => $loanData['loan_date'] ?? now()->toDateString(),
                'loan_amount' => $loanData['loan_amount'],
                'loan_interest' => $loanData['loan_interest'],
                'interest_rate' => $loanData['interest_rate'],
                'loan_terms_months' => $loanData['loan_terms_months'],
                'terms_basis' => $termsBasis,
                'payment_amount' => $loanData['payment_amount'],
                'paid_amount' => 0,
                'schedule' => $loanData['schedule'],
                'status' => EmployeeLoan::STATUS_ACTIVE,
            ]);

            $request->forceFill([
                'approved_employee_loan_id' => $loan->id,
                'status' => EmployeeLoanRequest::STATUS_APPROVED,
                'hr_comment' => $data['hr_comment'] ?? null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            $this->notifyEmployee(
                $request,
                'Loan request approved',
                "Your {$request->loan_type} request was approved.",
                'success',
            );

            return $loan;
        });
    }

    public function reject(EmployeeLoanRequest $request, string $comment, User $reviewer): void
    {
        DB::transaction(function () use ($request, $comment, $reviewer): void {
            $request = EmployeeLoanRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($request->status !== EmployeeLoanRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'hr_comment' => 'Only pending loan requests can be rejected.',
                ]);
            }

            $comment = trim($comment);

            if ($comment === '') {
                throw ValidationException::withMessages([
                    'hr_comment' => 'An HR comment is required when rejecting a loan request.',
                ]);
            }

            $request->forceFill([
                'status' => EmployeeLoanRequest::STATUS_REJECTED,
                'hr_comment' => $comment,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            $this->notifyEmployee(
                $request,
                'Loan request rejected',
                "Your {$request->loan_type} request was rejected. View the request history for HR remarks.",
                'danger',
            );
        });
    }

    public function cancel(EmployeeLoanRequest $request, Employee $employee): void
    {
        DB::transaction(function () use ($request, $employee): void {
            $request = EmployeeLoanRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ((int) $request->employee_id !== (int) $employee->id) {
                throw ValidationException::withMessages([
                    'loan_type' => 'You cannot cancel another employee’s loan request.',
                ]);
            }

            if ($request->status !== EmployeeLoanRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'loan_type' => 'Only pending loan requests can be cancelled.',
                ]);
            }

            $request->forceFill([
                'status' => EmployeeLoanRequest::STATUS_CANCELLED,
            ])->save();
        });
    }

    public function delete(EmployeeLoanRequest $request, Employee $employee): void
    {
        DB::transaction(function () use ($request, $employee): void {
            $request = EmployeeLoanRequest::withTrashed()->lockForUpdate()->findOrFail($request->id);

            if ((int) $request->employee_id !== (int) $employee->id) {
                throw ValidationException::withMessages([
                    'loan_type' => 'You cannot delete another employee\'s loan request.',
                ]);
            }

            if ($request->status === EmployeeLoanRequest::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'loan_type' => 'Approved loan requests are retained as part of the loan record.',
                ]);
            }

            $request->forceDelete();
        });
    }

    protected function validateLoanData(array $data, string $periodField, string $termsBasis): array
    {
        $validator = Validator::make($data, [
            'loan_type' => ['required', 'string', 'max:191'],
            'loan_date' => ['nullable', 'date'],
            'loan_amount' => ['required', 'numeric', 'gt:0'],
            'loan_interest' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'loan_terms_months' => ['required', 'integer', 'min:1'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'schedule' => ['required', 'in:'.implode(',', array_keys(EmployeeLoan::scheduleOptions()))],
            $periodField => ['required', 'integer', 'exists:payroll_periods,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'hr_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $validator->after(function ($validator) use ($data, $periodField, $termsBasis): void {
            $terms = max(1, (int) ($data['loan_terms_months'] ?? 1));
            $schedule = EmployeeLoan::normalizeSchedule($data['schedule'] ?? null);
            $isCalendarMonthTerms = EmployeeLoan::normalizeTermsBasis($termsBasis) === EmployeeLoan::TERMS_BASIS_CALENDAR_MONTH;
            $loanInterest = $isCalendarMonthTerms
                ? EmployeeLoan::flatAddOnInterest(
                    (float) ($data['loan_amount'] ?? 0),
                    (float) ($data['interest_rate'] ?? 0),
                    $terms,
                )
                : (float) ($data['loan_interest'] ?? 0);
            $total = (float) ($data['loan_amount'] ?? 0) + $loanInterest;
            $deductions = EmployeeLoan::scheduledDeductionsCount($terms, $schedule, $termsBasis);
            $scheduledTotal = (float) ($data['payment_amount'] ?? 0) * $deductions;

            if (
                ! $isCalendarMonthTerms
                && $scheduledTotal + 0.001 < $total
            ) {
                $validator->errors()->add(
                    'payment_amount',
                    'Payment multiplied by the scheduled deductions must cover the total loan amount.',
                );
            }

            $periodId = $data[$periodField] ?? null;

            $period = filled($periodId)
                ? PayrollPeriod::query()->whereKey($periodId)->first()
                : null;

            if (! $period || $period->is_locked) {
                $validator->errors()->add($periodField, 'Select an open payroll period.');

                return;
            }

        });

        $validated = $validator->validate();
        $validated['loan_amount'] = round((float) $validated['loan_amount'], 2);
        $validated['loan_terms_months'] = max(1, (int) $validated['loan_terms_months']);
        $validated['schedule'] = EmployeeLoan::normalizeSchedule($validated['schedule']);
        $validated['interest_rate'] = filled($validated['interest_rate'] ?? null)
            ? round((float) $validated['interest_rate'], 4)
            : null;

        if (EmployeeLoan::normalizeTermsBasis($termsBasis) === EmployeeLoan::TERMS_BASIS_CALENDAR_MONTH) {
            $validated['loan_interest'] = EmployeeLoan::flatAddOnInterest(
                $validated['loan_amount'],
                $validated['interest_rate'] ?? 0,
                $validated['loan_terms_months'],
            );
            $validated['payment_amount'] = EmployeeLoan::plannedPaymentAmount(
                $validated['loan_amount'],
                $validated['loan_interest'],
                $validated['loan_terms_months'],
                $validated['schedule'],
                $termsBasis,
            );

        } else {
            $validated['loan_interest'] = round((float) ($validated['loan_interest'] ?? 0), 2);
            $validated['payment_amount'] = round((float) $validated['payment_amount'], 2);
        }

        return $validated;
    }

    protected function notifyEmployee(EmployeeLoanRequest $request, string $title, string $body, string $status): void
    {
        $user = $request->employee?->user;

        if (! $user || (bool) $user->is_disabled) {
            return;
        }

        $notification = FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->status($status);

        LaravelNotification::sendNow([$user], $notification->toDatabase());
    }
}
