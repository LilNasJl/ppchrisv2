<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequestApproval extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_hr' => 'boolean', 'activated_at' => 'datetime', 'acted_at' => 'datetime', 'reminded_at' => 'datetime'];

    public function leave()
    {
        return $this->belongsTo(Leave::class);
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_employee_id');
    }
}
