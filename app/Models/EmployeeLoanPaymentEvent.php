<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLoanPaymentEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['occurred_at' => 'datetime', 'amount' => 'decimal:2', 'balance_after' => 'decimal:2'];

    public function payment()
    {
        return $this->belongsTo(EmployeeLoanPayment::class, 'employee_loan_payment_id');
    }
}
