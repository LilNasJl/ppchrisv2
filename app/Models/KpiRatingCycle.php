<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiRatingCycle extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'kpi_account_id',
        'rating_date',
        'title',
        'scope_type',
        'status',
    ];

    protected $casts = [
        'rating_date' => 'date',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(KpiAccount::class, 'kpi_account_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(KpiRatingTarget::class);
    }
}
