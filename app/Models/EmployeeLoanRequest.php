<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeLoanRequest extends Model
{
    use HasPublicUuid, SoftDeletes;

    public const STATUS_PENDING = 'Pending';

    public const STATUS_APPROVED = 'Approved';

    public const STATUS_REJECTED = 'Rejected';

    public const STATUS_CANCELLED = 'Cancelled';

    protected $fillable = [
        'employee_id',
        'preferred_start_payroll_period_id',
        'approved_employee_loan_id',
        'loan_type',
        'request_date',
        'loan_amount',
        'loan_interest',
        'interest_rate',
        'loan_terms_months',
        'terms_basis',
        'payment_amount',
        'schedule',
        'reason',
        'status',
        'hr_comment',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'loan_amount' => 'decimal:2',
        'loan_interest' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'loan_terms_months' => 'integer',
        'payment_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (EmployeeLoanRequest $request): void {
            $request->loan_amount = max(0, (float) ($request->loan_amount ?? 0));
            $request->loan_interest = max(0, (float) ($request->loan_interest ?? 0));
            $request->interest_rate = filled($request->interest_rate)
                ? max(0, (float) $request->interest_rate)
                : null;
            $request->loan_terms_months = max(1, (int) ($request->loan_terms_months ?? 1));
            $request->schedule = EmployeeLoan::normalizeSchedule($request->schedule);
            $request->terms_basis = EmployeeLoan::normalizeTermsBasis($request->terms_basis);
            if ($request->terms_basis === EmployeeLoan::TERMS_BASIS_CALENDAR_MONTH && $request->interest_rate !== null) {
                $request->loan_interest = EmployeeLoan::flatAddOnInterest(
                    $request->loan_amount,
                    $request->interest_rate,
                    $request->loan_terms_months,
                );
            }
            $request->payment_amount = $request->terms_basis === EmployeeLoan::TERMS_BASIS_CALENDAR_MONTH
                ? EmployeeLoan::plannedPaymentAmount(
                    $request->loan_amount,
                    $request->loan_interest,
                    $request->loan_terms_months,
                    $request->schedule,
                    $request->terms_basis,
                )
                : max(0, (float) ($request->payment_amount ?? 0));
            $request->status ??= self::STATUS_PENDING;
        });
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => self::STATUS_PENDING,
            self::STATUS_APPROVED => self::STATUS_APPROVED,
            self::STATUS_REJECTED => self::STATUS_REJECTED,
            self::STATUS_CANCELLED => self::STATUS_CANCELLED,
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function preferredStartPayrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class, 'preferred_start_payroll_period_id')->withTrashed();
    }

    public function approvedLoan()
    {
        return $this->belongsTo(EmployeeLoan::class, 'approved_employee_loan_id')->withTrashed();
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public function getTotalAmountAttribute(): float
    {
        return round((float) $this->loan_amount + (float) $this->loan_interest, 2);
    }

    public function usesCalendarMonthTerms(): bool
    {
        return EmployeeLoan::normalizeTermsBasis($this->terms_basis) === EmployeeLoan::TERMS_BASIS_CALENDAR_MONTH;
    }

    public function getTermsLabelAttribute(): string
    {
        return $this->usesCalendarMonthTerms() ? 'month(s)' : 'payroll period(s)';
    }
}
