<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class EmployeeLoanPayment extends Model
{
    use HasPublicUuid;

    public const STATUS_POSTED = 'Posted';

    public const STATUS_VOIDED = 'Voided';

    protected $fillable = [
        'employee_loan_id',
        'payroll_period_id',
        'amount',
        'balance_after',
        'processed_at',
        'status',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'processed_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(EmployeeLoan::class, 'employee_loan_id');
    }

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }
}
