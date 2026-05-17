<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Memo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'memo_type_id',
        'title',
        'description',
        'attachment_path',
        'attachment_original_name',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function type()
    {
        return $this->belongsTo(MemoType::class, 'memo_type_id');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (blank($this->attachment_path)) {
            return null;
        }

        return route('memo.attachments.show', $this, false);
    }

    public function getAttachmentNameAttribute(): ?string
    {
        if (blank($this->attachment_path)) {
            return null;
        }

        return $this->attachment_original_name ?: basename($this->attachment_path);
    }
}
