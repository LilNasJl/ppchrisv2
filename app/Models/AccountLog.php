<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountLog extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'user_id',
        'actor_user_id',
        'event',
        'panel',
        'username',
        'account_name',
        'role',
        'ip_address',
        'session_id',
        'user_agent',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function getAccountLabelAttribute(): string
    {
        return $this->account_name
            ?: $this->username
            ?: 'Account';
    }

    public function getActorLabelAttribute(): string
    {
        return $this->actor?->employee?->full_name
            ?: $this->actor?->name
            ?: $this->actor?->username
            ?: 'System';
    }
}
