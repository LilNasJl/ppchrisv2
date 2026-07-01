<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Holiday extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'branch_id',
        'date',
        'month_day',
        'holiday_type_id',
        'title',
        'is_recurring',
        'source',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(HolidayType::class, 'holiday_type_id');
    }

    public function employeeExclusions(): HasMany
    {
        return $this->hasMany(HolidayEmployeeExclusion::class);
    }
}
