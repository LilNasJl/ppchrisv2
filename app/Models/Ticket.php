<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasPublicUuid, SoftDeletes;

    public const STATUS_PENDING = 'Pending';

    public const STATUS_DONE = 'Done';

    protected $fillable = [
        'employee_id',
        'title',
        'description',
        'employee_attachment_path',
        'employee_attachment_original_name',
        'status',
        'hr_comment',
        'hr_attachment_path',
        'hr_attachment_original_name',
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

    public function getEmployeeAttachmentUrlAttribute(): ?string
    {
        return filled($this->employee_attachment_path)
            ? route('ticket.attachments.show', [$this, 'employee'], false)
            : null;
    }

    public function getHrAttachmentUrlAttribute(): ?string
    {
        return filled($this->hr_attachment_path)
            ? route('ticket.attachments.show', [$this, 'hr'], false)
            : null;
    }

    public function getEmployeeAttachmentNameAttribute(): ?string
    {
        return filled($this->employee_attachment_path)
            ? ($this->employee_attachment_original_name ?: basename($this->employee_attachment_path))
            : null;
    }

    public function getHrAttachmentNameAttribute(): ?string
    {
        return filled($this->hr_attachment_path)
            ? ($this->hr_attachment_original_name ?: basename($this->hr_attachment_path))
            : null;
    }
}
