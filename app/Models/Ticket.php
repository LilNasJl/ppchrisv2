<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'Pending';

    public const STATUS_DONE = 'Done';

    protected $fillable = [
        'employee_id',
        'title',
        'description',
        'status',
        'hr_comment',
        'handled_by_user_id',
        'done_at',
    ];

    protected $casts = [
        'done_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function handledBy()
    {
        return $this->belongsTo(User::class, 'handled_by_user_id')->withTrashed();
    }
}
