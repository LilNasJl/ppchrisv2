<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollPeriod extends Model
{
    use HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'title',
        'date_start',
        'date_end',
        'date_payout',
        'description',
        'is_locked',
        'locked_at',
        'deductions_processed_at',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'date_payout' => 'date',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
        'deductions_processed_at' => 'datetime',
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

    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('date_start')
            ->orderByDesc('date_end')
            ->orderByDesc('id');
    }

    public function dtrs(): HasMany
    {
        return $this->hasMany(Dtr::class);
    }

    public function employeeExclusions(): HasMany
    {
        return $this->hasMany(PayrollPeriodEmployeeExclusion::class);
    }

    public function branchExclusions(): HasMany
    {
        return $this->hasMany(PayrollPeriodBranchExclusion::class);
    }

    public function calculationSetting(): HasOne
    {
        return $this->hasOne(PayrollCalculationSetting::class);
    }
}
