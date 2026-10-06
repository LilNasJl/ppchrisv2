<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Models\User;
use App\Services\StationManagementAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DtrChangeRequest extends Model
{
    use HasPublicUuid;

    public const STATUS_PENDING = 'Pending';

    public const STATUS_APPROVED = 'Approved';

    public const STATUS_REJECTED = 'Rejected';

    public const STATUS_CANCELLED = 'Cancelled';

    public const TYPE_OVERTIME_APPROVAL = 'overtime_approval';

    public const TYPE_FORGOT_TO_PUNCH = 'forgot_to_punch';

    public const TYPE_TIME_DISCREPANCY = 'time_entries_discrepancy';

    public const TYPE_MISSING_DTR_ENTRIES = 'missing_dtr_entries';

    public const TYPE_DELETE_TIME_ENTRY = 'delete_time_entry';

    public const TYPE_MISSING_PUNCH = 'missing_time_in_or_out';

    public const TYPE_WRONG_SCHEDULE = 'wrong_shift_or_schedule';

    public const TYPE_DUPLICATE_ENTRY = 'duplicate_dtr_entry';

    public const TYPE_WRONG_PERIOD = 'wrong_payroll_period';

    public const TYPE_SCHEDULE_NOT_SHOWING = 'schedule_not_showing';

    public const TYPE_PRINT_ISSUE = 'print_pdf_issue';

    public const TYPE_OTHERS = 'others';

    public const OT_SCOPE_BOTH = 'both';

    public const OT_SCOPE_OVERTIME_ONLY = 'overtime_only';

    public const OT_SCOPE_EARLY_ONLY = 'early_only';

    protected $fillable = [
        'employee_id',
        'branch_id',
        'payroll_period_id',
        'dtr_id',
        'employee_visible_dtr_id',
        'dtr_date',
        'assigned_employee_id',
        'reviewed_by_employee_id',
        'employee_name_snapshot',
        'employee_company_id_snapshot',
        'branch_name_snapshot',
        'payroll_period_title_snapshot',
        'date_from',
        'date_to',
        'request_type',
        'description',
        'attachment_path',
        'attachment_original_name',
        'status',
        'current_level',
        'total_levels',
        'current_step_label',
        'workflow_snapshot',
        'approvals_history',
        'original_values',
        'proposed_values',
        'rejection_reason',
        'first_level_editor_id',
        'first_level_edited_at',
        'reviewer_remarks',
        'reviewed_at',
        'employee_seen_at',
        'cancelled_at',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'dtr_date' => 'date',
        'current_level' => 'integer',
        'total_levels' => 'integer',
        'workflow_snapshot' => 'array',
        'approvals_history' => 'array',
        'original_values' => 'array',
        'proposed_values' => 'array',
        'first_level_edited_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'employee_seen_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public static function primaryRequestTypeOptions(): array
    {
        return [
            self::TYPE_OVERTIME_APPROVAL => 'Overtime Approval',
            self::TYPE_FORGOT_TO_PUNCH => 'Forgot To Punch Approval',
            self::TYPE_TIME_DISCREPANCY => 'Time Entries Discrepancy',
            self::TYPE_DELETE_TIME_ENTRY => 'Delete Time Entry',
        ];
    }

    public static function requestTypeOptions(): array
    {
        return [
            self::TYPE_OVERTIME_APPROVAL => 'Overtime Approval',
            self::TYPE_FORGOT_TO_PUNCH => 'Forgot To Punch Approval',
            self::TYPE_TIME_DISCREPANCY => 'Time Entries Discrepancy',
            self::TYPE_MISSING_DTR_ENTRIES => 'Missing DTR Entries',
            self::TYPE_DELETE_TIME_ENTRY => 'Delete Time Entry',
            self::TYPE_MISSING_PUNCH => 'Missing Time In or Time Out',
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

    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === self::STATUS_PENDING) {
            $step = trim((string) ($this->current_step_label ?? ''));

            return $step !== '' ? "Pending – {$step} Approval" : 'Pending';
        }

        return (string) $this->status;
    }

    public function getCurrentApproverNameAttribute(): string
    {
        if ($this->status !== self::STATUS_PENDING) {
            return $this->reviewer_name;
        }

        if ($this->assignedEmployee) {
            return (string) $this->assignedEmployee->full_name;
        }

        $snapshot = $this->workflow_snapshot ?? [];
        foreach ($snapshot as $step) {
            if ((int) ($step['level'] ?? 0) === (int) $this->current_level) {
                return (string) ($step['approver_name'] ?? 'Assigned Approver');
            }
        }

        return 'Pending Assignment';
    }

    public function getIsFirstLevelAttribute(): bool
    {
        return (int) ($this->current_level ?? 1) === 1;
    }

    public function getIsFinalLevelAttribute(): bool
    {
        return (int) ($this->current_level ?? 1) >= (int) ($this->total_levels ?? 1);
    }

    public function getIsEditableTypeAttribute(): bool
    {
        return in_array($this->request_type, [
            self::TYPE_FORGOT_TO_PUNCH,
            self::TYPE_TIME_DISCREPANCY,
            self::TYPE_MISSING_PUNCH,
            self::TYPE_WRONG_SCHEDULE,
        ], true);
    }

    public static function overtimeScopeOptions(): array
    {
        return [
            self::OT_SCOPE_BOTH => 'Both Early Clock In & Overtime',
            self::OT_SCOPE_OVERTIME_ONLY => 'Overtime Only (After-Schedule)',
            self::OT_SCOPE_EARLY_ONLY => 'Early Clock In Overtime Only',
        ];
    }

    public function getIsMissingDtrEntriesTypeAttribute(): bool
    {
        return $this->request_type === self::TYPE_MISSING_DTR_ENTRIES;
    }

    public function getIsDeleteTimeEntryTypeAttribute(): bool
    {
        return $this->request_type === self::TYPE_DELETE_TIME_ENTRY;
    }

    public function getIsOvertimeTypeAttribute(): bool
    {
        return $this->request_type === self::TYPE_OVERTIME_APPROVAL;
    }

    public function getOvertimeRequestScopeAttribute(): string
    {
        return $this->proposed_values['overtime_request_scope']
            ?? $this->proposed_values['scope']
            ?? self::OT_SCOPE_BOTH;
    }

    public function getOvertimeRequestScopeLabelAttribute(): string
    {
        return match ($this->overtime_request_scope) {
            self::OT_SCOPE_OVERTIME_ONLY => 'Overtime Only',
            self::OT_SCOPE_EARLY_ONLY => 'Early Clock In Only',
            default => 'Both (Early Clock In & Overtime)',
        };
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (blank($this->attachment_path)) {
            return null;
        }

        return route('dtr-change-requests.attachment', $this, false);
    }

    public function getAttachmentNameAttribute(): ?string
    {
        if (blank($this->attachment_path)) {
            return null;
        }

        return $this->attachment_original_name ?: basename($this->attachment_path);
    }

    public function arePreviousLevelsApproved(): bool
    {
        $currentLevel = (int) ($this->current_level ?? 1);
        if ($currentLevel <= 1) {
            return true;
        }

        $history = collect($this->approvals_history ?? []);

        for ($level = 1; $level < $currentLevel; $level++) {
            $hasApproval = $history->contains(function ($entry) use ($level) {
                return (int) ($entry['level'] ?? 0) === $level
                    && strtolower((string) ($entry['action'] ?? '')) === 'approved';
            });

            if (! $hasApproval) {
                return false;
            }
        }

        return true;
    }

    public function isPendingAtCurrentLevel(): bool
    {
        return $this->status === self::STATUS_PENDING
            && filled($this->assigned_employee_id)
            && $this->arePreviousLevelsApproved();
    }

    public function canBeEditedBy(User|Employee|null $actor): bool
    {
        if (! $this->isPendingAtCurrentLevel() || ! $this->is_first_level || ! $this->is_editable_type) {
            return false;
        }

        if (! StationManagementAccess::isActiveApproverAccount($actor)) {
            return false;
        }

        $employee = StationManagementAccess::resolveEmployee($actor);
        if (! $employee) {
            return false;
        }

        if ((int) $this->assigned_employee_id !== (int) $employee->id) {
            return false;
        }

        return StationManagementAccess::canManageChangeRequest($actor, $this);
    }

    public function canBeReviewedBy(User|Employee|null $actor): bool
    {
        if (! $this->isPendingAtCurrentLevel()) {
            return false;
        }

        if (! StationManagementAccess::isActiveApproverAccount($actor)) {
            return false;
        }

        $employee = StationManagementAccess::resolveEmployee($actor);
        if (! $employee) {
            return false;
        }

        if ((int) $this->assigned_employee_id !== (int) $employee->id) {
            return false;
        }

        return StationManagementAccess::canManageChangeRequest($actor, $this);
    }

    public function canBeCancelledBy(User|Employee|null $actor): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $employee = $actor instanceof User ? $actor->employee : $actor;
        if (! $employee) {
            return false;
        }

        return (int) $this->employee_id === (int) $employee->id;
    }

    public function targetDtrRecord(): Dtr|EmployeeVisibleDtr|null
    {
        // Missing DTR Entries requests are for creating brand new records; they do not target an existing record.
        if ($this->is_missing_dtr_entries_type) {
            return null;
        }

        if ($this->employee_visible_dtr_id) {
            $record = $this->employeeVisibleDtr;
            if ($record) {
                return $record;
            }
        }

        if ($this->dtr_id) {
            $record = $this->dtr;
            if ($record) {
                return $record;
            }
        }

        // For Delete Time Entry, NEVER fall back to date lookup!
        // The specific UID must be authoritative.
        if ($this->is_delete_time_entry_type) {
            return null;
        }

        if ($this->dtr_date && $this->employee_id && $this->payroll_period_id) {
            $visible = EmployeeVisibleDtr::query()
                ->where('employee_id', $this->employee_id)
                ->where('payroll_period_id', $this->payroll_period_id)
                ->whereDate('date_in', $this->dtr_date)
                ->first();

            if ($visible) {
                return $visible;
            }

            $employee = $this->employee ?: Employee::query()->find($this->employee_id);
            $fingerprintId = (string) ($employee?->fingerprint_id ?: $employee?->uid ?: $employee?->company_id);

            if ($fingerprintId !== '') {
                return Dtr::query()
                    ->where('fingerprint_id', $fingerprintId)
                    ->where('payroll_period_id', $this->payroll_period_id)
                    ->whereDate('date_in', $this->dtr_date)
                    ->first();
            }
        }

        return null;
    }

    public function targetDtrUid(): ?int
    {
        return $this->employee_visible_dtr_id
            ?: $this->dtr_id
            ?: ($this->proposed_values['dtr_uid'] ?? $this->proposed_values['target_id'] ?? null);
    }


    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePendingForApprover(Builder $query, Employee|int|null $approver): Builder
    {
        $approverEmployee = $approver instanceof Employee
            ? $approver
            : ($approver ? Employee::query()->find($approver) : null);

        if (! $approverEmployee || ! StationManagementAccess::canAccessStationManagement($approverEmployee)) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('status', self::STATUS_PENDING)
            ->where('assigned_employee_id', $approverEmployee->id);

        return StationManagementAccess::scopeManagedChangeRequestsQuery($query, $approverEmployee);
    }

    public function scopeReviewed(Builder $query): Builder
    {
        return $query->whereNotNull('reviewed_at');
    }

    public function scopeUnseenByEmployee(Builder $query): Builder
    {
        return $query->whereNull('employee_seen_at');
    }

    public function scopeForStationHistory(Builder $query, User|Employee|null $user): Builder
    {
        if (! StationManagementAccess::canAccessStationManagement($user)) {
            return $query->whereRaw('1 = 0');
        }

        return StationManagementAccess::scopeManagedChangeRequestsQuery($query, $user);
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

    public function dtr()
    {
        return $this->belongsTo(Dtr::class, 'dtr_id');
    }

    public function employeeVisibleDtr()
    {
        return $this->belongsTo(EmployeeVisibleDtr::class, 'employee_visible_dtr_id');
    }

    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id')->withTrashed();
    }

    public function reviewedByEmployee()
    {
        return $this->belongsTo(Employee::class, 'reviewed_by_employee_id')->withTrashed();
    }

    public function firstLevelEditor()
    {
        return $this->belongsTo(Employee::class, 'first_level_editor_id')->withTrashed();
    }

    public function getReviewerNameAttribute(): string
    {
        return $this->reviewedByEmployee?->full_name ?? 'Not reviewed yet';
    }
}
