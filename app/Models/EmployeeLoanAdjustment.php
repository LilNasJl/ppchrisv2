<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLoanAdjustment extends Model
{
    public const TYPE_RESCHEDULE = 'reschedule';

    public const TYPE_CORRECT_TOTAL = 'correct_total';

    protected $fillable = [
        'employee_loan_id',
        'effective_payroll_period_id',
        'changed_by',
        'type',
        'previous_total',
        'new_total',
        'previous_loan_amount',
        'new_loan_amount',
        'paid_at_change',
        'remaining_deductions',
        'payment_amount',
        'reason',
    ];

    protected $casts = [
        'previous_total' => 'decimal:2',
        'new_total' => 'decimal:2',
        'previous_loan_amount' => 'decimal:2',
        'new_loan_amount' => 'decimal:2',
        'paid_at_change' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'remaining_deductions' => 'integer',
    ];

    public function effectivePayrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class, 'effective_payroll_period_id')->withTrashed();
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by')->withTrashed();
    }
}
