<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeLoan extends Model
{
    use HasPublicUuid, SoftDeletes;

    public const STATUS_ACTIVE = 'Active';

    public const STATUS_PAID = 'Paid';

    public const STATUS_CANCELLED = 'Cancelled';

    public const SCHEDULE_FIRST_QUINCENA = 'Every 1st Quincena';

    public const SCHEDULE_SECOND_QUINCENA = 'Every 2nd Quincena';

    public const SCHEDULE_EVERY_PAYROLL = 'Every Payroll';

    /**
     * Existing loans keep their original meaning: one term equals one payroll
     * deduction. New loans use a calendar month as one term.
     */
    public const TERMS_BASIS_PAYROLL_PERIOD = 'payroll_period';

    public const TERMS_BASIS_CALENDAR_MONTH = 'calendar_month';

    protected $fillable = [
        'employee_id',
        'amortization_start_payroll_period_id',
        'loan_type',
        'loan_date',
        'loan_amount',
        'loan_interest',
        'interest_rate',
        'loan_terms_months',
        'terms_basis',
        'payment_amount',
        'paid_amount',
        'schedule',
        'status',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'loan_amount' => 'decimal:2',
        'loan_interest' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'payment_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'loan_terms_months' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (EmployeeLoan $loan): void {
            $loan->loan_terms_months = max(1, (int) ($loan->loan_terms_months ?: 1));
            $loan->loan_amount = max(0, (float) ($loan->loan_amount ?? 0));
            $loan->loan_interest = max(0, (float) ($loan->loan_interest ?? 0));
            $loan->interest_rate = filled($loan->interest_rate)
                ? max(0, (float) $loan->interest_rate)
                : null;
            $loan->terms_basis = self::normalizeTermsBasis($loan->terms_basis);
            if ($loan->usesCalendarMonthTerms() && $loan->interest_rate !== null) {
                $loan->loan_interest = self::flatAddOnInterest(
                    $loan->loan_amount,
                    $loan->interest_rate,
                    $loan->loan_terms_months,
                );
            }
            $loan->payment_amount = $loan->usesCalendarMonthTerms()
                ? self::plannedPaymentAmount(
                    $loan->loan_amount,
                    $loan->loan_interest,
                    $loan->loan_terms_months,
                    $loan->schedule,
                    $loan->terms_basis,
                )
                : max(0, (float) ($loan->payment_amount ?? 0));
            $loan->paid_amount = max(0, (float) ($loan->paid_amount ?? 0));
            $loan->status ??= self::STATUS_ACTIVE;
            $loan->schedule = self::normalizeSchedule($loan->schedule);
        });
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => self::STATUS_ACTIVE,
            self::STATUS_PAID => self::STATUS_PAID,
            self::STATUS_CANCELLED => self::STATUS_CANCELLED,
        ];
    }

    public static function loanTypeOptions(): array
    {
        return [
            'Company Loan' => 'Company Loan',
            'SSS Salary Loan' => 'SSS Salary Loan',
            'HDMF Loan' => 'HDMF Loan',
            'Emergency Loan' => 'Emergency Loan',
            'Cash Advance' => 'Cash Advance',
        ];
    }

    public static function scheduleOptions(): array
    {
        return [
            self::SCHEDULE_FIRST_QUINCENA => self::SCHEDULE_FIRST_QUINCENA,
            self::SCHEDULE_SECOND_QUINCENA => self::SCHEDULE_SECOND_QUINCENA,
            self::SCHEDULE_EVERY_PAYROLL => self::SCHEDULE_EVERY_PAYROLL,
        ];
    }

    public static function normalizeSchedule(?string $schedule): string
    {
        $schedule = trim((string) $schedule);

        return array_key_exists($schedule, self::scheduleOptions())
            ? $schedule
            : self::SCHEDULE_EVERY_PAYROLL;
    }

    public static function normalizeTermsBasis(?string $termsBasis): string
    {
        return $termsBasis === self::TERMS_BASIS_CALENDAR_MONTH
            ? self::TERMS_BASIS_CALENDAR_MONTH
            : self::TERMS_BASIS_PAYROLL_PERIOD;
    }

    public static function scheduledDeductionsCount(
        int $terms,
        ?string $schedule,
        ?string $termsBasis,
    ): int {
        $terms = max(1, $terms);

        if (self::normalizeTermsBasis($termsBasis) !== self::TERMS_BASIS_CALENDAR_MONTH) {
            return $terms;
        }

        return self::normalizeSchedule($schedule) === self::SCHEDULE_EVERY_PAYROLL
            ? $terms * 2
            : $terms;
    }

    public static function plannedPaymentAmount(
        float|int|string|null $loanAmount,
        float|int|string|null $loanInterest,
        int $terms,
        ?string $schedule,
        ?string $termsBasis,
    ): float {
        $total = max(0, (float) $loanAmount) + max(0, (float) $loanInterest);
        $deductions = self::scheduledDeductionsCount($terms, $schedule, $termsBasis);

        return round($total / max(1, $deductions), 2);
    }

    public static function flatAddOnInterest(
        float|int|string|null $loanAmount,
        float|int|string|null $monthlyInterestRate,
        int $terms,
    ): float {
        return round(
            max(0, (float) $loanAmount)
            * (max(0, (float) $monthlyInterestRate) / 100)
            * max(1, $terms),
            2,
        );
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function amortizationStartPayrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class, 'amortization_start_payroll_period_id')->withTrashed();
    }

    public function payments()
    {
        return $this->hasMany(EmployeeLoanPayment::class);
    }

    public function request()
    {
        return $this->hasOne(EmployeeLoanRequest::class, 'approved_employee_loan_id');
    }

    public function postedPayments(): HasMany
    {
        return $this->payments()
            ->where('status', EmployeeLoanPayment::STATUS_POSTED);
    }

    public function getTotalAmountAttribute(): float
    {
        return round((float) $this->loan_amount + (float) $this->loan_interest, 2);
    }

    public function getPaymentAmountAttribute(mixed $value): float
    {
        if (filled($value)) {
            return round((float) $value, 2);
        }

        if ($this->usesCalendarMonthTerms()) {
            return $this->expected_payment_amount;
        }

        return round($this->total_amount / max(1, (int) $this->loan_terms_months), 2);
    }

    public function usesCalendarMonthTerms(): bool
    {
        return self::normalizeTermsBasis($this->terms_basis) === self::TERMS_BASIS_CALENDAR_MONTH;
    }

    public function getTermsLabelAttribute(): string
    {
        return $this->usesCalendarMonthTerms() ? 'month(s)' : 'payroll period(s)';
    }

    public function getScheduledDeductionsCountAttribute(): int
    {
        return self::scheduledDeductionsCount(
            (int) $this->loan_terms_months,
            $this->schedule,
            $this->terms_basis,
        );
    }

    public function getExpectedPaymentAmountAttribute(): float
    {
        return self::plannedPaymentAmount(
            $this->loan_amount,
            $this->loan_interest,
            (int) $this->loan_terms_months,
            $this->schedule,
            $this->terms_basis,
        );
    }

    public function getBalanceAmountAttribute(): float
    {
        return round(max(0, $this->total_amount - (float) $this->paid_amount), 2);
    }

    public function postedPaymentsCount(): int
    {
        if ($this->relationLoaded('postedPayments')) {
            return $this->postedPayments->count();
        }

        return $this->postedPayments()->count();
    }

    public function hasRemainingTerms(): bool
    {
        $maximumDeductions = $this->usesCalendarMonthTerms()
            ? $this->scheduled_deductions_count
            : max(1, (int) $this->loan_terms_months);

        return $this->postedPaymentsCount() < $maximumDeductions;
    }

    public function postedPaymentForPeriod(PayrollPeriod $period): ?EmployeeLoanPayment
    {
        return $this->payments()
            ->where('payroll_period_id', $period->id)
            ->where('status', EmployeeLoanPayment::STATUS_POSTED)
            ->first();
    }

    public function paymentAmountForPeriod(PayrollPeriod $period): float
    {
        $postedPayment = $this->postedPaymentForPeriod($period);

        if ($postedPayment) {
            return round((float) $postedPayment->amount, 2);
        }

        if ($this->status !== self::STATUS_ACTIVE) {
            return 0.0;
        }

        if (! $this->canPostPaymentForPeriod($period)) {
            return 0.0;
        }

        $balance = $this->balance_amount;

        if ($balance <= 0 || $this->payment_amount <= 0) {
            return 0.0;
        }

        return round(min($this->payment_amount, $balance), 2);
    }

    public function hasStartedForPeriod(PayrollPeriod $period): bool
    {
        if (! $this->amortization_start_payroll_period_id) {
            return true;
        }

        $startPeriod = $this->relationLoaded('amortizationStartPayrollPeriod')
            ? $this->amortizationStartPayrollPeriod
            : $this->amortizationStartPayrollPeriod()->first();

        if (! $startPeriod?->date_start || ! $period->date_start) {
            return false;
        }

        return $startPeriod->date_start->startOfDay()->lessThanOrEqualTo($period->date_start->startOfDay());
    }

    public function isScheduledForPeriod(PayrollPeriod $period): bool
    {
        if (! $this->hasStartedForPeriod($period)) {
            return false;
        }

        if ($this->schedule === self::SCHEDULE_EVERY_PAYROLL) {
            return true;
        }

        $startDay = (int) $period->date_start?->format('j');
        $endDay = (int) $period->date_end?->format('j');

        return match ($this->schedule) {
            self::SCHEDULE_FIRST_QUINCENA => $startDay === 26 && $endDay === 10,
            self::SCHEDULE_SECOND_QUINCENA => $startDay === 11 && $endDay === 25,
            default => true,
        };
    }

    public function canPostPaymentForPeriod(PayrollPeriod $period): bool
    {
        if (! $this->isScheduledForPeriod($period)) {
            return false;
        }

        return $this->hasRemainingTerms();
    }

    public static function isValidMonthlyTermStartPeriod(PayrollPeriod $period, ?string $schedule): bool
    {
        return ! $period->is_locked && filled($period->date_start);
    }

    public static function resolveMonthlyTermStartPeriod(PayrollPeriod $selectedPeriod, ?string $schedule): ?PayrollPeriod
    {
        if ($selectedPeriod->is_locked || ! $selectedPeriod->date_start) {
            return null;
        }

        return $selectedPeriod;
    }

    public function termKeyForPeriod(PayrollPeriod $period): ?string
    {
        $date = $period->date_end ?: $period->date_payout ?: $period->date_start;

        return $date?->format('Y-m');
    }
}
