<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class ActionHistory extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'actor_id',
        'actor_name',
        'actor_role',
        'action',
        'model_type',
        'model_id',
        'model_label',
        'record_label',
        'summary',
        'before_data',
        'after_data',
        'changed_data',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
        'changed_data' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
