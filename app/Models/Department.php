<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'acronym',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function kpiIndicators(): HasMany
    {
        return $this->hasMany(KpiIndicator::class);
    }
}
