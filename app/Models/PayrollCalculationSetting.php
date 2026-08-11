<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollCalculationSetting extends Model
{
    use HasPublicUuid;

    public const DEFAULTS = [
        'regular_work_days_per_month' => 26.0,
        'regular_half_month_days' => 13.0,
        'work_hours_per_day' => 8.0,
        'late_grace_minutes' => 2,
        'half_day_work_day_value' => 0.5,
        'overtime_rate_multiplier' => 1.0,
        'regular_holiday_rate' => 200.0,
        'special_holiday_rate' => 30.0,
        'holiday_overtime_premium_rate' => 30.0,
        'unworked_regular_holiday_pay_enabled' => true,
    ];

    protected $fillable = [
        'payroll_period_id',
        'regular_work_days_per_month',
        'regular_half_month_days',
        'work_hours_per_day',
        'late_grace_minutes',
        'half_day_work_day_value',
        'overtime_rate_multiplier',
        'regular_holiday_rate',
        'special_holiday_rate',
        'holiday_overtime_premium_rate',
        'unworked_regular_holiday_pay_enabled',
    ];

    protected $casts = [
        'payroll_period_id' => 'integer',
        'regular_work_days_per_month' => 'decimal:2',
        'regular_half_month_days' => 'decimal:2',
        'work_hours_per_day' => 'decimal:2',
        'late_grace_minutes' => 'integer',
        'half_day_work_day_value' => 'decimal:2',
        'overtime_rate_multiplier' => 'decimal:2',
        'regular_holiday_rate' => 'decimal:2',
        'special_holiday_rate' => 'decimal:2',
        'holiday_overtime_premium_rate' => 'decimal:2',
        'unworked_regular_holiday_pay_enabled' => 'boolean',
    ];

    public static function forPeriod(PayrollPeriod $period): self
    {
        $setting = static::query()->firstOrNew([
            'payroll_period_id' => $period->id,
        ]);

        foreach (self::DEFAULTS as $key => $value) {
            if ($setting->{$key} === null) {
                $setting->{$key} = $value;
            }
        }

        return $setting;
    }

    public function value(string $key): float
    {
        return max(0, (float) ($this->{$key} ?? self::DEFAULTS[$key] ?? 0));
    }

    public function divisor(string $key): float
    {
        $value = $this->value($key);

        return $value > 0 ? $value : (float) self::DEFAULTS[$key];
    }

    public function enabled(string $key): bool
    {
        return (bool) ($this->{$key} ?? self::DEFAULTS[$key] ?? false);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }
}
