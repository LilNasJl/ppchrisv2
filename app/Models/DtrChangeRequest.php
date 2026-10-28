<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Models\User;
use App\Services\LeaveApprovalAccess;
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

    public static function isHrReviewer(User|Employee|null $actor): bool
    {
        if (! $actor) {
            return false;
        }

        $user = $actor instanceof User
            ? $actor
            : ($actor->relationLoaded('user') ? $actor->user : $actor->user()->first());

        if (! $user) {
            return false;
        }

        if ((bool) ($user->is_disabled ?? false)) {
            return false;
        }

        if (in_array($user->role, ['hr', 'admin'], true)) {
            return true;
        }

        return LeaveApprovalAccess::review($user);
    }

    public function isCurrentStepHr(): bool
    {
        $snapshot = $this->workflow_snapshot ?? [];
        $currentLevel = (int) ($this->current_level ?? 1);

        foreach ($snapshot as $step) {
            if ((int) ($step['level'] ?? 0) === $currentLevel) {
                if (! empty($step['is_hr'])) {
                    return true;
                }

                if (blank($step['approver_employee_id'] ?? null) && str_contains(strtolower((string) ($step['label'] ?? '')), 'hr')) {
                    return true;
                }
            }
        }

        return blank($this->assigned_employee_id)
            && str_contains(strtolower((string) ($this->current_step_label ?? '')), 'hr');
    }

    public function isPendingHrApproval(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->isCurrentStepHr()
            && $this->arePreviousLevelsApproved();
    }

    public function getRequestTypeLabelAttribute(): string
    {
        return self::requestTypeOptions()[$this->request_type] ?? str($this->request_type)->replace('_', ' ')->title()->toString();
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === self::STATUS_PENDING) {
            if ($this->isCurrentStepHr()) {
                return 'Pending HR Final Approval';
            }

            $step = trim((string) ($this->current_step_label ?? ''));
            if ($step !== '' && str_ends_with(strtolower($step), 'approval')) {
                return "Pending – {$step}";
            }

            return $step !== '' ? "Pending – {$step} Approval" : 'Pending';
        }

        return (string) $this->status;
    }

    public function getCurrentApproverNameAttribute(): string
    {
        if ($this->status !== self::STATUS_PENDING) {
            return $this->reviewer_name;
        }

        if ($this->isCurrentStepHr()) {
            return 'HR';
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

    public function getOvertimeSummaryDescriptionAttribute(): ?string
    {
        if (! $this->is_overtime_type) {
            return null;
        }

        $orig = $this->original_values ?? [];
        $scope = $this->overtime_request_scope;

        $earlyMins = isset($orig['early_clock_in'])
            ? max(0, (int) $orig['early_clock_in'])
            : max(0, (int) ($this->targetDtrRecord()?->early_clock_in ?? 0));

        $otMins = isset($orig['overtime'])
            ? max(0, (int) $orig['overtime'])
            : max(0, (int) ($this->targetDtrRecord()?->overtime ?? 0));

        $formatMins = function (int $mins): string {
            $hrs = round($mins / 60, 1);
            return "{$mins}m ({$hrs}h)";
        };

        return match ($scope) {
            self::OT_SCOPE_OVERTIME_ONLY => 'Target: ' . ($otMins > 0 ? $formatMins($otMins) : '0m') . ' Overtime',
            self::OT_SCOPE_EARLY_ONLY => 'Target: ' . ($earlyMins > 0 ? $formatMins($earlyMins) : '0m') . ' Early Clock In',
            default => 'Targets: Early ' . ($earlyMins > 0 ? "{$earlyMins}m" : '0m') . ' + OT ' . ($otMins > 0 ? $formatMins($otMins) : '0m'),
        };
    }

    public function getRequestTypeColorAttribute(): string
    {
        return match ($this->request_type) {
            self::TYPE_OVERTIME_APPROVAL => 'warning',
            self::TYPE_DELETE_TIME_ENTRY => 'danger',
            self::TYPE_TIME_DISCREPANCY, self::TYPE_MISSING_DTR_ENTRIES => 'purple',
            self::TYPE_FORGOT_TO_PUNCH => 'info',
            default => 'info',
        };
    }

    public function getRequestTypeIconAttribute(): string
    {
        return match ($this->request_type) {
            self::TYPE_OVERTIME_APPROVAL => 'heroicon-m-clock',
            self::TYPE_DELETE_TIME_ENTRY => 'heroicon-m-trash',
            self::TYPE_MISSING_DTR_ENTRIES => 'heroicon-m-plus-circle',
            self::TYPE_FORGOT_TO_PUNCH => 'heroicon-m-finger-print',
            self::TYPE_TIME_DISCREPANCY => 'heroicon-m-arrows-right-left',
            default => 'heroicon-m-tag',
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
        if ($this->status !== self::STATUS_PENDING || ! $this->arePreviousLevelsApproved()) {
            return false;
        }

        if ($this->isCurrentStepHr()) {
            return true;
        }

        return filled($this->assigned_employee_id);
    }

    public function canBeEditedBy(User|Employee|null $actor): bool
    {
        if (! $this->isPendingAtCurrentLevel() || ! $this->is_editable_type) {
            return false;
        }

        if ($this->isCurrentStepHr()) {
            $employee = $actor instanceof Employee ? $actor : $actor?->employee;

            if ($employee && (int) $employee->id === (int) $this->employee_id) {
                return false;
            }

            return self::isHrReviewer($actor);
        }

        if (! $this->is_first_level) {
            return false;
        }

        if (! StationManagementAccess::isActiveApproverAccount($actor)) {
            return false;
        }

        $employee = StationManagementAccess::resolveEmployee($actor);
        if (! $employee) {
            return false;
        }

        if ((int) $this->employee_id === (int) $employee->id) {
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

        if ($this->isCurrentStepHr()) {
            $employee = $actor instanceof Employee ? $actor : $actor?->employee;

            if ($employee && (int) $employee->id === (int) $this->employee_id) {
                return false;
            }

            return self::isHrReviewer($actor);
        }

        if (! StationManagementAccess::isActiveApproverAccount($actor)) {
            return false;
        }

        $employee = StationManagementAccess::resolveEmployee($actor);
        if (! $employee) {
            return false;
        }

        if ((int) $this->employee_id === (int) $employee->id) {
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

    public function scopePendingForHr(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING)
            ->whereNull('assigned_employee_id')
            ->where(function (Builder $q): void {
                $q->where('current_step_label', 'HR Final Approval')
                    ->orWhereColumn('current_level', '>=', 'total_levels');
            });
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
        if ($this->reviewedByEmployee) {
            return (string) $this->reviewedByEmployee->full_name;
        }

        $history = collect($this->approvals_history ?? []);
        $lastDecision = $history->reverse()->first(fn ($entry) => in_array(strtolower((string) ($entry['action'] ?? '')), ['approved', 'rejected'], true));
        if ($lastDecision && filled($lastDecision['actor_name'] ?? null)) {
            return (string) $lastDecision['actor_name'];
        }

        return $this->reviewed_at ? 'HR' : 'Not reviewed yet';
    }
}
