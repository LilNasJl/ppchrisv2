<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriodEmployeeExclusion extends Model
{
    use HasPublicUuid;

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
