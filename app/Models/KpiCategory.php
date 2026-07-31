<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiCategory extends Model
{
    use HasPublicUuid;

    protected $fillable = ['kpi_indicator_id', 'name', 'weight'];

    protected function casts(): array
    {
        return ['weight' => 'decimal:2'];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(KpiIndicator::class, 'kpi_indicator_id');
    }
}
