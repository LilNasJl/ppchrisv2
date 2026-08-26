<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class EmployeeVisibleDtr extends Dtr
{
    protected $table = 'employee_visible_dtrs';

    protected $fillable = [
        'sample',
        'leave_id',
        'on_field_dtr_submission_id',
        'payroll_period_id',
        'branch_id',
        'employee_id',
        'fingerprint_id',
        'batch_id',
        'import_name',
        'date_in',
        'time_in',
        'date_out',
        'time_out',
        'schedule_type',
        'day_part',
        'entry_source',
        'schedule_start',
        'schedule_end',
        'late',
        'undertime',
        'overtime',
        'early_clock_in',
        'credited_early_clock_in',
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
        'holiday_excluded',
        'daily_rate',
        'comment',
        'is_absent',
        'absence_minutes',
        'is_imported',
        'is_locked',
        'source_session_id',
        'source_filename',
        'source_file_hash',
        'source_row_hash',
        'latest_source_row_hash',
        'latest_source_payload',
        'is_manually_edited',
        'manual_edited_at',
        'manual_edited_by_sicrc_account_id',
        'needs_review',
        'review_reason',
    ];

    protected $casts = [
        'early_clock_in_approved' => 'boolean',
        'overtime_approved' => 'boolean',
        'is_holiday' => 'boolean',
        'holiday_excluded' => 'boolean',
        'is_absent' => 'boolean',
        'absence_minutes' => 'integer',
        'credited_early_clock_in' => 'integer',
        'is_imported' => 'boolean',
        'is_locked' => 'boolean',
        'daily_rate' => 'decimal:2',
        'latest_source_payload' => 'array',
        'is_manually_edited' => 'boolean',
        'manual_edited_at' => 'datetime',
        'needs_review' => 'boolean',
    ];

    public function scopeForDtrScope(Builder $query, int $payrollPeriodId, int $branchId, string|int $fingerprintId): Builder
    {
        return $query
            ->where('payroll_period_id', $payrollPeriodId)
            ->where('branch_id', $branchId)
            ->where('fingerprint_id', (string) $fingerprintId);
    }

    public function scopeForEmployee(Builder $query, Employee $employee): Builder
    {
        $fingerprintId = trim((string) $employee->fingerprint_id);

        return $query
            ->where('branch_id', $employee->branch_id)
            ->where(function (Builder $query) use ($employee, $fingerprintId): void {
                $query->where('employee_id', $employee->getKey());

                if ($fingerprintId !== '') {
                    $query->orWhere(function (Builder $query) use ($fingerprintId): void {
                        $query->whereNull('employee_id')->where('fingerprint_id', $fingerprintId);
                    });
                }
            });
    }
}
