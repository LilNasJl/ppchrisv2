<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollPeriod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'date_start',
        'date_end',
        'date_payout',
        'description',
        'is_locked',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'date_payout' => 'date',
        'is_locked' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (PayrollPeriod $payrollPeriod): void {
            Dtr::query()
                ->where('payroll_period_id', $payrollPeriod->id)
                ->update(['is_locked' => $payrollPeriod->is_locked]);
        });

        static::deleting(function (PayrollPeriod $payrollPeriod): void {
            $query = Dtr::withTrashed()
                ->where('payroll_period_id', $payrollPeriod->id);

            if ($payrollPeriod->isForceDeleting()) {
                $query->forceDelete();

                return;
            }

            $query->delete();
        });

        static::restoring(function (PayrollPeriod $payrollPeriod): void {
            Dtr::withTrashed()
                ->where('payroll_period_id', $payrollPeriod->id)
                ->restore();
        });
    }

    public function dtrs(): HasMany
    {
        return $this->hasMany(Dtr::class);
    }
}
