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
        'submitted_by_employee_id',
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
        'schedule_type',
        'schedule_start',
        'schedule_end',
        'entries',
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
        'entries' => 'array',
        'reviewed_at' => 'datetime',
        'generated_dtr_deleted_at' => 'datetime',
    ];

    /**
     * Return normalized array of entries for both multi-entry and single-entry submissions.
     *
     * @return array<int, array{date_in: string, time_in: string, date_out: string, time_out: string, schedule_type: ?string, schedule_start: ?string, schedule_end: ?string}>
     */
    public function normalizedEntries(): array
    {
        if (is_array($this->entries) && count($this->entries) > 0) {
            return array_values(array_map(function (array $entry): array {
                return [
                    'date_in' => (string) ($entry['date_in'] ?? optional($this->date_in)->toDateString() ?? ''),
                    'time_in' => (string) ($entry['time_in'] ?? $this->time_in ?? ''),
                    'date_out' => (string) ($entry['date_out'] ?? optional($this->date_out)->toDateString() ?? ''),
                    'time_out' => (string) ($entry['time_out'] ?? $this->time_out ?? ''),
                    'schedule_type' => filled($entry['schedule_type'] ?? null) ? (string) $entry['schedule_type'] : $this->schedule_type,
                    'schedule_start' => filled($entry['schedule_start'] ?? null) ? (string) $entry['schedule_start'] : $this->schedule_start,
                    'schedule_end' => filled($entry['schedule_end'] ?? null) ? (string) $entry['schedule_end'] : $this->schedule_end,
                ];
            }, $this->entries));
        }

        return [[
            'date_in' => optional($this->date_in)->toDateString() ?? (string) $this->getRawOriginal('date_in'),
            'time_in' => (string) ($this->time_in ?? ''),
            'date_out' => optional($this->date_out)->toDateString() ?? (string) $this->getRawOriginal('date_out'),
            'time_out' => (string) ($this->time_out ?? ''),
            'schedule_type' => $this->schedule_type,
            'schedule_start' => $this->schedule_start,
            'schedule_end' => $this->schedule_end,
        ]];
    }

    public function submittedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'submitted_by_employee_id')->withTrashed();
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

        return $this->submittedByEmployee?->full_name ?? 'Unknown';
    }

    public function submittedBranchName(): string
    {
        return $this->branch_name_snapshot
            ?: $this->branch?->branch_name
            ?: 'Unknown';
    }
}
