<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriodEmployeeAdjustment extends Model
{
    use HasPublicUuid;

    public const OVERRIDABLE_FIELDS = [
        'days_worked' => 'days_worked_override',
        'allowance' => 'allowance_override',
        'overtime_amount' => 'overtime_amount_override',
        'shift3_premium' => 'shift3_override',
        'regular_holiday' => 'regular_holiday_override',
        'special_holiday' => 'special_holiday_override',
        'undertime_amount' => 'undertime_amount_override',
        'late' => 'late_override',
        'absent' => 'absent_override',
        'halfday' => 'halfday_override',
        'uniform' => 'uniform_override',
        'other_deductions' => 'other_deductions_override',
        'sss_loan' => 'sss_loan_override',
        'sss_ee' => 'sss_ee_override',
        'hdmf_loan' => 'hdmf_loan_override',
        'hdmf_ee' => 'hdmf_ee_override',
        'phic_ee' => 'phic_ee_override',
    ];

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'salary_adjustment',
        'shortages',
        'days_worked_override',
        'allowance_override',
        'overtime_amount_override',
        'shift3_override',
        'regular_holiday_override',
        'special_holiday_override',
        'undertime_amount_override',
        'late_override',
        'absent_override',
        'halfday_override',
        'uniform_override',
        'other_deductions_override',
        'sss_loan_override',
        'sss_ee_override',
        'hdmf_loan_override',
        'hdmf_ee_override',
        'phic_ee_override',
        'notes',
    ];

    protected $casts = [
        'payroll_period_id' => 'integer',
        'employee_id' => 'integer',
        'salary_adjustment' => 'decimal:2',
        'shortages' => 'decimal:2',
        'days_worked_override' => 'decimal:2',
        'allowance_override' => 'decimal:2',
        'overtime_amount_override' => 'decimal:2',
        'shift3_override' => 'decimal:2',
        'regular_holiday_override' => 'decimal:2',
        'special_holiday_override' => 'decimal:2',
        'undertime_amount_override' => 'decimal:2',
        'late_override' => 'decimal:2',
        'absent_override' => 'decimal:2',
        'halfday_override' => 'decimal:2',
        'uniform_override' => 'decimal:2',
        'other_deductions_override' => 'decimal:2',
        'sss_loan_override' => 'decimal:2',
        'sss_ee_override' => 'decimal:2',
        'hdmf_loan_override' => 'decimal:2',
        'hdmf_ee_override' => 'decimal:2',
        'phic_ee_override' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (PayrollPeriodEmployeeAdjustment $adjustment): void {
            $adjustment->salary_adjustment = max(0, (float) ($adjustment->salary_adjustment ?? 0));
            $adjustment->shortages = max(0, (float) ($adjustment->shortages ?? 0));

            foreach (self::OVERRIDABLE_FIELDS as $column) {
                $val = $adjustment->{$column};
                if ($val === '' || $val === null) {
                    $adjustment->{$column} = null;
                } else {
                    $adjustment->{$column} = max(0, round((float) $val, 2));
                }
            }
        });
    }

    public function hasOverrides(): bool
    {
        foreach (self::OVERRIDABLE_FIELDS as $column) {
            if ($this->{$column} !== null) {
                return true;
            }
        }

        return false;
    }

    public function hasCustomAdjustments(): bool
    {
        return (float) ($this->salary_adjustment ?? 0) > 0
            || (float) ($this->shortages ?? 0) > 0
            || $this->hasOverrides();
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
