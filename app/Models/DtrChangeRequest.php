<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DtrChangeRequest extends Model
{
    use HasPublicUuid;

    public const STATUS_PENDING = 'Pending';

    public const STATUS_APPROVED = 'Approved';

    public const STATUS_REJECTED = 'Rejected';

    public const TYPE_OVERTIME_APPROVAL = 'overtime_approval';

    public const TYPE_MISSING_PUNCH = 'missing_time_in_or_out';

    public const TYPE_FORGOT_TO_PUNCH = 'forgot_to_punch';

    public const TYPE_WRONG_SCHEDULE = 'wrong_shift_or_schedule';

    public const TYPE_DUPLICATE_ENTRY = 'duplicate_dtr_entry';

    public const TYPE_WRONG_PERIOD = 'wrong_payroll_period';

    public const TYPE_SCHEDULE_NOT_SHOWING = 'schedule_not_showing';

    public const TYPE_PRINT_ISSUE = 'print_pdf_issue';

    public const TYPE_OTHERS = 'others';

    protected $fillable = [
        'employee_id',
        'branch_id',
        'payroll_period_id',
        'assigned_sic_rc_account_id',
        'reviewed_by_sic_rc_account_id',
        'employee_name_snapshot',
        'employee_company_id_snapshot',
        'branch_name_snapshot',
        'payroll_period_title_snapshot',
        'date_from',
        'date_to',
        'request_type',
        'description',
        'status',
        'reviewer_remarks',
        'reviewed_at',
        'employee_seen_at',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'reviewed_at' => 'datetime',
        'employee_seen_at' => 'datetime',
    ];

    public static function requestTypeOptions(): array
    {
        return [
            self::TYPE_OVERTIME_APPROVAL => 'Overtime Approval',
            self::TYPE_MISSING_PUNCH => 'Missing Time In or Time Out',
            self::TYPE_FORGOT_TO_PUNCH => 'Forgot to Punch Entry',
            self::TYPE_WRONG_SCHEDULE => 'Wrong Shift or Schedule Applied',
            self::TYPE_DUPLICATE_ENTRY => 'Duplicate D.T.R Entry',
            self::TYPE_WRONG_PERIOD => 'D.T.R Entry Assigned to the Wrong Payroll Period',
            self::TYPE_SCHEDULE_NOT_SHOWING => 'Schedule Start / End Not Showing Correctly',
            self::TYPE_PRINT_ISSUE => 'Print / PDF D.T.R Issue',
            self::TYPE_OTHERS => 'Others',
        ];
    }

    public function getRequestTypeLabelAttribute(): string
    {
        return self::requestTypeOptions()[$this->request_type] ?? str($this->request_type)->replace('_', ' ')->title()->toString();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeReviewed(Builder $query): Builder
    {
        return $query->whereNotNull('reviewed_at');
    }

    public function scopeUnseenByEmployee(Builder $query): Builder
    {
        return $query->whereNull('employee_seen_at');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class)->withTrashed();
    }

    public function assignedSicRcAccount()
    {
        return $this->belongsTo(SicRcAccount::class, 'assigned_sic_rc_account_id')->withTrashed();
    }

    public function reviewedBySicRcAccount()
    {
        return $this->belongsTo(SicRcAccount::class, 'reviewed_by_sic_rc_account_id')->withTrashed();
    }
}
