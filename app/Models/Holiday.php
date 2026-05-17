<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    protected $fillable = [
        'date',
        'holiday_type_id',
        'title',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(HolidayType::class, 'holiday_type_id');
    }
}
