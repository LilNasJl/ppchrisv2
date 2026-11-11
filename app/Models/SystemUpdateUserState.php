<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemUpdateUserState extends Model
{
    protected $fillable = [
        'user_id',
        'last_popup_shown_at',
    ];

    protected function casts(): array
    {
        return [
            'last_popup_shown_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
