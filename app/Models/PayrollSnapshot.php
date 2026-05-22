<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSnapshot extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'branch_id',
        'row_number',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
