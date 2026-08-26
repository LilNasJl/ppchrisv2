<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DtrSubmission extends Model
{
    public const TYPE_DTR = 'dtr';

    public const TYPE_PROOF = 'proof';

    public const STATUS_PENDING = 'Pending';

    public const STATUS_APPROVED = 'Approved';

    public const STATUS_REJECTED = 'Rejected';

    use HasPublicUuid;
    use SoftDeletes;

    protected $fillable = [
        'sic_rc_account_id',
        'employee_id',
        'employee_name_snapshot',
        'employee_company_id_snapshot',
        'payroll_period_id',
        'branch_id',
        'branch_name_snapshot',
        'date_in',
        'time_in',
        'date_out',
        'time_out',
        'file_path',
        'file_name',
        'file_size',
        'comments',
        'description',
        'is_new',
        'submission_type',
        'mime_type',
        'file_hash',
        'viewed_at',
        'status',
        'reviewed_by_user_id',
        'reviewer_remarks',
        'reviewed_at',
        'generated_dtr_id',
        'generated_visible_dtr_id',
        'generated_dtr_deleted_at',
        'generated_dtr_deleted_by_user_id',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_new' => 'boolean',
        'viewed_at' => 'datetime',
        'date_in' => 'date',
        'date_out' => 'date',
        'reviewed_at' => 'datetime',
        'generated_dtr_deleted_at' => 'datetime',
    ];

    public function sicRcAccount(): BelongsTo
    {
        return $this->belongsTo(SicRcAccount::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id')->withTrashed();
    }

    public function generatedDtr(): BelongsTo
    {
        return $this->belongsTo(Dtr::class, 'generated_dtr_id')->withTrashed();
    }

    public function generatedVisibleDtr(): BelongsTo
    {
        return $this->belongsTo(EmployeeVisibleDtr::class, 'generated_visible_dtr_id')->withTrashed();
    }

    public function generatedDtrDeletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_dtr_deleted_by_user_id')->withTrashed();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function submittedEmployeeName(): string
    {
        if (filled($this->employee_name_snapshot)) {
            return $this->employee_name_snapshot;
        }

        if ($this->employee) {
            return trim($this->employee->lastname.', '.$this->employee->firstname);
        }

        return $this->sicRcAccount?->username ?? 'Unknown';
    }

    public function submittedBranchName(): string
    {
        return $this->branch_name_snapshot
            ?: $this->branch?->branch_name
            ?: 'Unknown';
    }
}
