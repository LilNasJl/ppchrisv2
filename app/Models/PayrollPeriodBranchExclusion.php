<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollPeriodBranchExclusion extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'payroll_period_id',
        'branch_id',
    ];

    protected $casts = [
        'payroll_period_id' => 'integer',
        'branch_id' => 'integer',
    ];

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
