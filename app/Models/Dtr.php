<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dtr extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sample',
        'payroll_period_id',
        'branch_id',
        'fingerprint_id',
        'batch_id',
        'date_in',
        'time_in',
        'date_out',
        'time_out',
        'schedule_type',
        'schedule_start',
        'schedule_end',
        'late',
        'undertime',
        'overtime',
        'early_clock_in',
        'credited_overtime',
        'work_hrs',
        'credited_work_hrs',
        'overtime_status',
        'early_clock_in_approved',
        'overtime_approved',
        'is_holiday',
        'holiday_id',
        'holiday_type',
        'holiday_rate',
        'daily_rate',
        'comment',
        'is_absent',
        'is_imported',
        'is_locked',
    ];

    protected $casts = [
        'early_clock_in_approved' => 'boolean',
        'overtime_approved' => 'boolean',
        'is_holiday' => 'boolean',
        'is_absent' => 'boolean',
        'is_imported' => 'boolean',
        'is_locked' => 'boolean',
        'daily_rate' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function holiday()
    {
        return $this->belongsTo(Holiday::class);
    }
}
