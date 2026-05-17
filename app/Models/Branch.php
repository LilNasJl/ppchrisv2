<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_name',
        'branch_address',
        'mobile_no',
        'employee_id',
        'no_of_shifts',
        'reg_sched_start',
        'reg_sched_end',
        'is_24hrs',
        'opening_hrs',
        'closed_hrs',
        'shift1_start',
        'shift1_end',
        'shift2_start',
        'shift2_end',
        'shift3_start',
        'shift3_end',
        'has_broken_time',
        'broken_shift1_start',
        'broken_shift1_end',
        'broken_shift2_start',
        'broken_shift2_end',
        'broken_shift3_start',
        'broken_shift4_end',
        'scheduling'
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Models\Employee::class, 'employee_id');
    }

}
