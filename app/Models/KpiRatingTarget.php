<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiRatingTarget extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'kpi_rating_cycle_id',
        'target_type',
        'target_key',
        'branch_id',
        'employee_id',
        'target_name',
        'branch_name',
        'department_name',
        'designation_name',
        'status',
        'rating_payload',
    ];

    protected $casts = [
        'rating_payload' => 'array',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(KpiRatingCycle::class, 'kpi_rating_cycle_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
