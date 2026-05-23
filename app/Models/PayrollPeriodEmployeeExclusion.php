<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPeriodEmployeeExclusion extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'employee_id',
    ];

    protected $casts = [
        'payroll_period_id' => 'integer',
        'employee_id' => 'integer',
    ];

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
