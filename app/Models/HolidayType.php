<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HolidayType extends Model
{
    protected $fillable = [
        'type',
        'rate',
        'description',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
    ];

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }
}
