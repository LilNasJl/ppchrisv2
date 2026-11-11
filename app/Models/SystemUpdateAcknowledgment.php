<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemUpdateAcknowledgment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'system_update_id',
        'user_id',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }

    public function systemUpdate(): BelongsTo
    {
        return $this->belongsTo(SystemUpdate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
