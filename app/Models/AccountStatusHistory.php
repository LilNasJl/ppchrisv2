<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountStatusHistory extends Model
{
    protected $fillable = [
        'user_id',
        'changed_by_user_id',
        'is_disabled',
        'remarks',
    ];

    protected $casts = [
        'is_disabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id')->withTrashed();
    }
}
