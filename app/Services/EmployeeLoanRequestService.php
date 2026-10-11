<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRequest;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as LaravelNotification;
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
        app(LoanAccessService::class)->authorizeManagement($reviewer);

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
            if ((int) $request->employee->user_id === (int) $reviewer->id) {
                throw ValidationException::withMessages(['loan_type' => 'You cannot approve your own loan request.']);
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
        app(LoanAccessService::class)->authorizeManagement($reviewer);
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
        return app(LoanTermsService::class)->validate($data, $periodField, $termsBasis);
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
