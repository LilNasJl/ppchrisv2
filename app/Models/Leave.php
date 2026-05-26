<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Leave extends Model
{
    use HasPublicUuid, SoftDeletes;

    public const BIRTHDAY_LEAVE = 'Birthday Leave';

    public const HALF_DAY_LEAVE = 'Halfday';

    protected $fillable = [
        'employee_id',
        'leave_type',
        'leave_from',
        'leave_to',
        'is_half_day',
        'reason',
        'hr_comment',
        'status',
        'deducted_leave_credits',
        'deducted_birthday_leave_credits',
        'status_updated_at',
        'reviewed_by',
        'reviewed_at',
        'attachment_path',
        'attachment_original_name',
    ];

    protected $casts = [
        'leave_from' => 'date',
        'leave_to' => 'date',
        'is_half_day' => 'boolean',
        'deducted_leave_credits' => 'decimal:2',
        'deducted_birthday_leave_credits' => 'decimal:2',
        'status_updated_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (blank($this->attachment_path)) {
            return null;
        }

        return route('leave.attachments.show', $this, false);
    }

    public function getAttachmentNameAttribute(): ?string
    {
        if (blank($this->attachment_path)) {
            return null;
        }

        return $this->attachment_original_name ?: basename($this->attachment_path);
    }

    public function getRequestedLeaveDays(): float
    {
        if ($this->is_half_day) {
            if (
                Carbon::parse($this->leave_from)->toDateString() !==
                Carbon::parse($this->leave_to ?: $this->leave_from)->toDateString()
            ) {
                throw new RuntimeException('For half-day leave, Leave From and Leave To must be the same date.');
            }

            return 0.5;
        }

        $from = Carbon::parse($this->leave_from);
        $to = Carbon::parse($this->leave_to ?: $this->leave_from);

        if ($to->lessThan($from)) {
            return 0;
        }

        return (float) ($from->diffInDays($to) + 1);
    }

    public static function validateCanCreateRequest(Employee $employee, string $leaveType, mixed $leaveFrom, mixed $leaveTo, bool $isHalfDay = false): void
    {
        $employee->resetLeaveCreditsIfNeeded();
        $employee->refresh();

        $probe = new self([
            'employee_id' => $employee->id,
            'leave_type' => $leaveType,
            'leave_from' => $leaveFrom,
            'leave_to' => $leaveTo,
            'is_half_day' => $isHalfDay,
        ]);

        $leaveDays = $probe->getRequestedLeaveDays();

        if ($leaveDays <= 0) {
            throw new RuntimeException('Leave date range is invalid.');
        }

        if ($leaveType === self::BIRTHDAY_LEAVE) {
            $alreadyUsedBirthdayLeave = static::query()
                ->where('employee_id', $employee->id)
                ->where('leave_type', self::BIRTHDAY_LEAVE)
                ->whereIn('status', ['Pending', 'Approved'])
                ->whereYear('leave_from', Carbon::parse($leaveFrom)->year)
                ->exists();

            if ($alreadyUsedBirthdayLeave || (float) $employee->birthday_leave_credits < 1) {
                throw new RuntimeException('Birthday leave is no longer available for this year.');
            }

            return;
        }

        if ((float) $employee->leave_credits < $leaveDays) {
            throw new RuntimeException('Employee does not have enough leave credits.');
        }
    }

    public function approveRequest(?string $comment = null, ?int $reviewedBy = null): void
    {
        DB::transaction(function () use ($comment, $reviewedBy): void {
            $leave = static::query()->lockForUpdate()->findOrFail($this->id);

            if ($leave->status === 'Approved') {
                return;
            }

            $employee = Employee::query()->lockForUpdate()->findOrFail($leave->employee_id);
            $employee->resetLeaveCreditsIfNeeded();
            $employee->refresh();

            $leaveDays = $leave->getRequestedLeaveDays();

            if ($leaveDays <= 0) {
                throw new RuntimeException('Leave date range is invalid.');
            }

            if ($leave->leave_type === self::BIRTHDAY_LEAVE) {
                $alreadyUsedBirthdayLeave = static::query()
                    ->where('employee_id', $employee->id)
                    ->where('leave_type', self::BIRTHDAY_LEAVE)
                    ->where('status', 'Approved')
                    ->whereYear('leave_from', Carbon::parse($leave->leave_from)->year)
                    ->whereKeyNot($leave->id)
                    ->exists();

                if ($alreadyUsedBirthdayLeave || (float) $employee->birthday_leave_credits < 1) {
                    throw new RuntimeException('Birthday leave has already been used for this year.');
                }

                $employee->decrement('birthday_leave_credits', 1);

                $leave->forceFill([
                    'status' => 'Approved',
                    'hr_comment' => $comment,
                    'deducted_leave_credits' => 0,
                    'deducted_birthday_leave_credits' => 1,
                    'status_updated_at' => now(),
                    'reviewed_by' => $reviewedBy,
                    'reviewed_at' => now(),
                ])->save();

                return;
            }

            if ((float) $employee->leave_credits < $leaveDays) {
                throw new RuntimeException('Employee does not have enough leave credits.');
            }

            $employee->decrement('leave_credits', $leaveDays);

            $leave->forceFill([
                'status' => 'Approved',
                'hr_comment' => $comment,
                'deducted_leave_credits' => $leaveDays,
                'deducted_birthday_leave_credits' => 0,
                'status_updated_at' => now(),
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
            ])->save();
        });

        $this->refresh();
    }

    public function rejectRequest(?string $comment = null, ?int $reviewedBy = null): void
    {
        DB::transaction(function () use ($comment, $reviewedBy): void {
            $leave = static::query()->lockForUpdate()->findOrFail($this->id);
            $employee = Employee::query()->lockForUpdate()->find($leave->employee_id);

            if ($employee && $leave->status === 'Approved') {
                if ((float) $leave->deducted_leave_credits > 0) {
                    $employee->increment('leave_credits', (float) $leave->deducted_leave_credits);
                }

                if ((float) $leave->deducted_birthday_leave_credits > 0) {
                    $employee->increment('birthday_leave_credits', (float) $leave->deducted_birthday_leave_credits);
                }
            }

            $leave->forceFill([
                'status' => 'Rejected',
                'hr_comment' => $comment,
                'deducted_leave_credits' => 0,
                'deducted_birthday_leave_credits' => 0,
                'status_updated_at' => now(),
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
            ])->save();
        });

        $this->refresh();
    }
}
