<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Services\DtrDayPartService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Dtr extends Model
{
    use HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'sample',
        'leave_id',
        'on_field_dtr_submission_id',
        'payroll_period_id',
        'branch_id',
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
    ];

    protected static function booted(): void
    {
        static::creating(function (Dtr $dtr): void {
            if ((bool) $dtr->is_locked || $dtr->isPayrollPeriodLocked()) {
                throw new \DomainException('Cannot create a D.T.R entry in a locked payroll period.');
            }
        });

        static::updating(function (Dtr $dtr): void {
            $dirtyKeys = array_values(array_diff(array_keys($dtr->getDirty()), ['is_locked', 'updated_at']));
            if ($dirtyKeys === []) {
                return;
            }

            if (
                (bool) $dtr->getOriginal('is_locked')
                || (bool) $dtr->is_locked
                || $dtr->isPayrollPeriodLocked(checkOriginal: true)
            ) {
                throw new \DomainException('Cannot update a D.T.R entry in a locked payroll period.');
            }
        });

        static::deleting(function (Dtr $dtr): void {
            if (
                (bool) $dtr->getOriginal('is_locked')
                || (bool) $dtr->is_locked
                || $dtr->isPayrollPeriodLocked(checkOriginal: true)
            ) {
                throw new \DomainException('Cannot delete a D.T.R entry in a locked payroll period.');
            }
        });
    }

    public function isPayrollPeriodLocked(bool $checkOriginal = false): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('payroll_periods')) {
            return false;
        }

        $periodIds = array_values(array_unique(array_filter([
            $this->payroll_period_id ? (int) $this->payroll_period_id : null,
            $checkOriginal && $this->getOriginal('payroll_period_id') ? (int) $this->getOriginal('payroll_period_id') : null,
        ])));

        if ($periodIds !== [] && PayrollPeriod::query()->whereIn('id', $periodIds)->where('is_locked', true)->exists()) {
            return true;
        }

        $dates = array_values(array_unique(array_filter([
            filled($this->date_in) ? \Illuminate\Support\Carbon::parse($this->date_in)->toDateString() : null,
            $checkOriginal && filled($this->getOriginal('date_in'))
                ? \Illuminate\Support\Carbon::parse($this->getOriginal('date_in'))->toDateString()
                : null,
        ])));

        foreach ($dates as $dateString) {
            if (
                PayrollPeriod::query()
                    ->where('is_locked', true)
                    ->whereDate('date_start', '<=', $dateString)
                    ->whereDate('date_end', '>=', $dateString)
                    ->exists()
            ) {
                return true;
            }
        }

        return false;
    }

    public function requiresAttendanceApproval(): bool
    {
        $scheduleType = Str::lower(trim((string) $this->schedule_type));

        if (Str::contains($scheduleType, 'forgot')) {
            return true;
        }

        if (! $this->is_imported || in_array($scheduleType, ['absent', 'leave', 'overtime'], true)) {
            return false;
        }

        return blank($this->date_in)
            || blank($this->time_in)
            || blank($this->date_out)
            || blank($this->time_out);
    }

    public function scopeFinalizedAttendance(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('schedule_type')
                    ->orWhereRaw("LOWER(schedule_type) NOT LIKE '%forgot%'");
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('is_imported')
                    ->orWhere('is_imported', false)
                    ->orWhereRaw("LOWER(COALESCE(schedule_type, '')) IN ('absent', 'leave', 'overtime')")
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->whereNotNull('date_in')
                            ->whereNotNull('time_in')
                            ->whereNotNull('date_out')
                            ->whereNotNull('time_out');
                    });
            });
    }

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

    public function leave()
    {
        return $this->belongsTo(Leave::class);
    }

    public function onFieldDtrSubmission()
    {
        return $this->belongsTo(DtrSubmission::class, 'on_field_dtr_submission_id');
    }

    public function isControlledOnFieldDtr(): bool
    {
        return filled($this->on_field_dtr_submission_id)
            || $this->entry_source === DtrDayPartService::SOURCE_ON_FIELD_DTR;
    }
}
