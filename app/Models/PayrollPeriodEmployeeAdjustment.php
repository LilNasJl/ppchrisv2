<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriodEmployeeAdjustment extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'salary_adjustment',
        'shortages',
    ];

    protected $casts = [
        'payroll_period_id' => 'integer',
        'employee_id' => 'integer',
        'salary_adjustment' => 'decimal:2',
        'shortages' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (PayrollPeriodEmployeeAdjustment $adjustment): void {
            $adjustment->salary_adjustment = max(0, (float) ($adjustment->salary_adjustment ?? 0));
            $adjustment->shortages = max(0, (float) ($adjustment->shortages ?? 0));
        });
    }

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }
}
