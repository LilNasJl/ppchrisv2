<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\LeaveApprovalAccess;
use App\Models\LeaveApprovalWorkflow;
use App\Models\PayrollPeriod;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DtrChangeRequestService
{
    /**
     * Resolves the approval workflow hierarchy for an employee by checking
     * LeaveApprovalWorkflow.
     *
     * Rules:
     * - Regular employee requests use the configured workflow approvers only
     *   (the last workflow approver is the final approver).
     * - When an approver submits their own DTR Change Request, skip the requester
     *   (and any lower workflow levels):
     *   - If a higher workflow approver exists, send the request to that approver
     *     (who becomes the final approver if last in the workflow).
     *   - If no higher workflow approver exists, automatically send the request
     *     directly to HR for final approval.
     *
     * @return array<int, array{level: int, label: string, approver_employee_id: int|null, approver_name: string, is_hr?: bool}>
     */
    public function resolveWorkflow(Employee $employee): array
    {
        if (Schema::hasTable('leave_approval_workflows') && Schema::hasTable('leave_approval_levels')) {
            // 1. Check active workflows matching the employee's organizational scope
            $matches = LeaveApprovalWorkflow::with('levels')->where('is_active', true);

            foreach ([
                'employee' => $employee->id,
                'branch' => $employee->branch_id,
                'department' => $employee->department_id,
                'designation' => $employee->designation_id,
            ] as $key => $value) {
                $col = $key.'_id';
                if (Schema::hasColumn('leave_approval_workflows', $col)) {
                    $matches->where(fn ($q) => $q->whereNull($col)->orWhere($col, $value));
                }
            }

            $workflows = $matches->get()
                ->sortByDesc(fn (LeaveApprovalWorkflow $flow): array => [$flow->specificity(), (int) $flow->id])
                ->values();

            foreach ($workflows as $flow) {
                if ($flow->levels->isEmpty()) {
                    continue;
                }

                $steps = $this->buildStepsFromWorkflow($flow, $employee);
                if (count($steps) > 0) {
                    return $steps;
                }
            }

            // 2. Fallback for approvers whose home organizational scope (e.g., Main Office)
            // differs from the station/scope workflow where they are assigned as an approver:
            $approverWorkflows = LeaveApprovalWorkflow::with('levels')
                ->where('is_active', true)
                ->whereHas('levels', fn ($q) => $q->where('approver_employee_id', $employee->id))
                ->get()
                ->sortByDesc(fn (LeaveApprovalWorkflow $flow): array => [$flow->specificity(), (int) $flow->id])
                ->values();

            if ($approverWorkflows->isNotEmpty()) {
                // First, prefer any workflow where a higher active approver exists above the requester
                foreach ($approverWorkflows as $flow) {
                    $steps = $this->buildStepsFromWorkflow($flow, $employee, allowHrFallbackWhenLastApprover: false);
                    if (count($steps) > 0) {
                        return $steps;
                    }
                }

                // If no higher active approver exists in any workflow where the requester is an approver,
                // route directly to HR for final approval.
                foreach ($approverWorkflows as $flow) {
                    $steps = $this->buildStepsFromWorkflow($flow, $employee, allowHrFallbackWhenLastApprover: true);
                    if (count($steps) > 0) {
                        return $steps;
                    }
                }
            }
        }

        throw ValidationException::withMessages([
            'payroll_period_id' => 'No active approval workflow with an eligible approver is configured for your branch or scope. Contact HR before submitting this request.',
        ]);
    }

    /**
     * @return array<int, array{level: int, label: string, approver_employee_id: int|null, approver_name: string, is_hr?: bool}>
     */
    protected function buildStepsFromWorkflow(
        LeaveApprovalWorkflow $flow,
        Employee $employee,
        bool $allowHrFallbackWhenLastApprover = true,
    ): array {
        $orderedLevels = $flow->levels->values();
        $requesterHighestIndex = -1;

        foreach ($orderedLevels as $idx => $level) {
            if ((int) $level->approver_employee_id === (int) $employee->id) {
                $requesterHighestIndex = $idx;
            }
        }

        $candidateSteps = [];
        $hasAnyActiveApproverInFlow = false;

        foreach ($orderedLevels as $idx => $level) {
            $approver = Employee::with('user')->find($level->approver_employee_id);
            $isActiveApprover = $approver && StationManagementAccess::isActiveApproverAccount($approver);

            if ($isActiveApprover) {
                $hasAnyActiveApproverInFlow = true;
            }

            if ($idx <= $requesterHighestIndex || (int) $level->approver_employee_id === (int) $employee->id) {
                continue; // Skip self-approval and levels at or below requester's level
            }

            if ($isActiveApprover) {
                $candidateSteps[] = [
                    'level' => count($candidateSteps) + 1,
                    'label' => (string) $level->label,
                    'approver_employee_id' => (int) $approver->id,
                    'approver_name' => (string) $approver->full_name,
                    'is_hr' => false,
                ];
            }
        }

        if (count($candidateSteps) > 0) {
            return $candidateSteps;
        }

        if ($allowHrFallbackWhenLastApprover && $requesterHighestIndex >= 0 && $hasAnyActiveApproverInFlow) {
            return [
                [
                    'level' => 1,
                    'label' => 'HR Final Approval',
                    'approver_employee_id' => null,
                    'approver_name' => 'HR',
                    'is_hr' => true,
                ],
            ];
        }

        return [];
    }

    /**
     * @return array{employee: ?Employee, user: ?User, actor_id: ?int, actor_name: string}
     */
    protected function resolveReviewerIdentity(Employee|User $reviewer): array
    {
        if ($reviewer instanceof Employee) {
            $user = $reviewer->relationLoaded('user') ? $reviewer->user : $reviewer->user()->first();

            return [
                'employee' => $reviewer,
                'user' => $user,
                'actor_id' => (int) $reviewer->id,
                'actor_name' => (string) $reviewer->full_name,
            ];
        }

        $employee = $reviewer->relationLoaded('employee') ? $reviewer->employee : $reviewer->employee()->first();

        return [
            'employee' => $employee,
            'user' => $reviewer,
            'actor_id' => $employee ? (int) $employee->id : (int) $reviewer->id,
            'actor_name' => $employee ? (string) $employee->full_name : (string) ($reviewer->name ?: ($reviewer->username ?: 'HR')),
        ];
    }

    /**
     * Submit a general or date-range change request.
     */
    public function submit(Employee $employee, array $data): DtrChangeRequest
    {
        return DB::transaction(function () use ($employee, $data): DtrChangeRequest {
            $employee = Employee::query()
                ->with('branch')
                ->whereKey($employee->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (blank($employee->branch_id) || ! $employee->branch) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => 'Your employee profile is not assigned to a branch.',
                ]);
            }

            $period = PayrollPeriod::query()->find($data['payroll_period_id'] ?? null);

            if (! $period) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => 'Select a valid payroll period.',
                ]);
            }

            if ($period->is_locked) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => "Cannot submit a change request for locked payroll period ({$period->title}).",
                ]);
            }

            $dateFrom = $this->parseDate($data['date_from'] ?? null, 'date_from');
            $dateTo = $this->parseDate($data['date_to'] ?? null, 'date_to');

            if ($dateTo->lessThan($dateFrom)) {
                throw ValidationException::withMessages([
                    'date_to' => 'Date To cannot be earlier than Date From.',
                ]);
            }

            if ($dateFrom->lessThan($period->date_start) || $dateTo->greaterThan($period->date_end)) {
                throw ValidationException::withMessages([
                    'date_from' => 'The selected dates must be within '.$period->title.'.',
                    'date_to' => 'The selected dates must be within '.$period->title.'.',
                ]);
            }

            $requestType = (string) ($data['request_type'] ?? '');
            if (! array_key_exists($requestType, DtrChangeRequest::requestTypeOptions())) {
                throw ValidationException::withMessages([
                    'request_type' => 'Select a valid change request type.',
                ]);
            }

            $description = trim((string) ($data['description'] ?? $data['explanation'] ?? ''));
            if ($description === '') {
                throw ValidationException::withMessages([
                    'description' => 'Describe the D.T.R issue or requested correction.',
                ]);
            }

            if (mb_strlen($description) > 2000) {
                throw ValidationException::withMessages([
                    'description' => 'The description must not exceed 2,000 characters.',
                ]);
            }

            $duplicateExists = DtrChangeRequest::query()
                ->where('employee_id', $employee->id)
                ->where('payroll_period_id', $period->id)
                ->whereDate('date_from', $dateFrom)
                ->whereDate('date_to', $dateTo)
                ->where('request_type', $requestType)
                ->pending()
                ->exists();

            if ($duplicateExists) {
                $typeLabel = DtrChangeRequest::requestTypeOptions()[$requestType] ?? 'D.T.R change';
                throw ValidationException::withMessages([
                    'request_type' => "An active {$typeLabel} request already exists for this DTR entry. Please wait for the current request to be completed or cancel it before submitting another request.",
                ]);
            }

            $steps = $this->resolveWorkflow($employee);
            $firstStep = $steps[0];

            return DtrChangeRequest::query()->create([
                'employee_id' => $employee->id,
                'branch_id' => $employee->branch_id,
                'payroll_period_id' => $period->id,
                'assigned_employee_id' => $firstStep['approver_employee_id'],
                'employee_name_snapshot' => $employee->full_name,
                'employee_company_id_snapshot' => $employee->company_id,
                'branch_name_snapshot' => $employee->branch->branch_name,
                'payroll_period_title_snapshot' => $period->title,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'dtr_date' => $dateFrom,
                'request_type' => $requestType,
                'description' => $description,
                'attachment_path' => $data['attachment_path'] ?? null,
                'attachment_original_name' => $data['attachment_original_name'] ?? (filled($data['attachment_path'] ?? null) ? basename($data['attachment_path']) : null),
                'status' => DtrChangeRequest::STATUS_PENDING,
                'current_level' => 1,
                'total_levels' => count($steps),
                'current_step_label' => $firstStep['label'],
                'workflow_snapshot' => $steps,
                'approvals_history' => [],
                'proposed_values' => $requestType === DtrChangeRequest::TYPE_OVERTIME_APPROVAL ? [
                    'overtime_request_scope' => $data['overtime_request_scope'] ?? DtrChangeRequest::OT_SCOPE_BOTH,
                ] : null,
            ]);
        });
    }

    /**
     * Normalize schedule type identifier for consistent comparison and lookups.
     */
    public static function normalizeScheduleType(?string $scheduleType): string
    {
        $value = strtolower(str_replace([' ', '_', '-'], '', (string) $scheduleType));

        if (str_contains($value, 'shift1') || $value === '1') {
            return 'shift1';
        }

        if (str_contains($value, 'shift2') || $value === '2') {
            return 'shift2';
        }

        if (str_contains($value, 'shift3') || $value === '3') {
            return 'shift3';
        }

        if (str_contains($value, 'broken1') || str_contains($value, 'brkn1')) {
            return 'broken1';
        }

        if (str_contains($value, 'broken2') || str_contains($value, 'brkn2')) {
            return 'broken2';
        }

        if (str_contains($value, 'broken3') || str_contains($value, 'brkn3')) {
            return 'broken3';
        }

        if (str_contains($value, 'saturday')) {
            return 'saturday';
        }

        if (str_contains($value, 'sunday')) {
            return 'sunday';
        }

        if (str_contains($value, 'over')) {
            return 'overtime';
        }

        return 'regular';
    }

    /**
     * Human-readable label for a schedule type.
     */
    public static function scheduleTypeLabel(?string $scheduleType): string
    {
        return match (self::normalizeScheduleType($scheduleType)) {
            'shift1' => 'Shift 1',
            'shift2' => 'Shift 2',
            'shift3' => 'Shift 3',
            'broken1' => 'Broken Shift 1',
            'broken2' => 'Broken Shift 2',
            'broken3' => 'Broken Shift 3',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
            'overtime' => 'Overtime Only',
            default => 'Regular',
        };
    }

    /**
     * Resolve schedule start and end times for a branch and schedule type.
     *
     * @return array{0: ?string, 1: ?string}
     */
    public function resolveBranchScheduleTimes(?Branch $branch, ?string $scheduleType, bool $formatForInput = false): array
    {
        $normalized = self::normalizeScheduleType($scheduleType);

        $format = function (?string $time) use ($formatForInput): ?string {
            if (blank($time)) {
                return null;
            }

            try {
                return Carbon::parse($time)->format($formatForInput ? 'h:i A' : 'H:i:s');
            } catch (\Throwable) {
                return $time;
            }
        };

        [$rawStart, $rawEnd] = match ($normalized) {
            'shift1' => [
                $branch?->shift1_start,
                $branch?->shift1_end,
            ],
            'shift2' => [
                $branch?->shift2_start,
                $branch?->shift2_end,
            ],
            'shift3' => [
                $branch?->shift3_start,
                $branch?->shift3_end,
            ],
            'broken1' => [
                $branch?->broken_shift1_start,
                $branch?->broken_shift1_end,
            ],
            'broken2' => [
                $branch?->broken_shift2_start,
                $branch?->broken_shift2_end,
            ],
            'broken3' => [
                $branch?->broken_shift3_start,
                $branch?->broken_shift3_end,
            ],
            'saturday' => [
                '08:00:00',
                '11:00:00',
            ],
            'overtime' => [
                '00:00:00',
                '00:00:00',
            ],
            default => [
                $branch?->reg_sched_start,
                $branch?->reg_sched_end,
            ],
        };

        return [
            $format($rawStart),
            $format($rawEnd),
        ];
    }

    /**
     * Get available schedule type options configured specifically for a branch.
     *
     * @return array<string, string>
     */
    public function availableScheduleOptionsForBranch(?Branch $branch, bool $withTimes = true): array
    {
        if (! $branch) {
            return [];
        }

        $configs = [
            'Regular' => ['Regular', $branch->reg_sched_start, $branch->reg_sched_end],
            'Shift1' => ['Shift 1', $branch->shift1_start, $branch->shift1_end],
            'Shift2' => ['Shift 2', $branch->shift2_start, $branch->shift2_end],
            'Shift3' => ['Shift 3', $branch->shift3_start, $branch->shift3_end],
            'Broken1' => ['Broken Shift 1', $branch->broken_shift1_start, $branch->broken_shift1_end],
            'Broken2' => ['Broken Shift 2', $branch->broken_shift2_start, $branch->broken_shift2_end],
            'Broken3' => ['Broken Shift 3', $branch->broken_shift3_start, $branch->broken_shift3_end],
        ];

        $options = [];
        foreach ($configs as $key => [$label, $start, $end]) {
            if (blank($start) || blank($end)) {
                continue;
            }

            if ($withTimes) {
                try {
                    $formattedStart = Carbon::parse($start)->format('h:i A');
                    $formattedEnd = Carbon::parse($end)->format('h:i A');
                    $options[$key] = "{$label} ({$formattedStart} - {$formattedEnd})";
                } catch (\Throwable) {
                    $options[$key] = "{$label} ({$start} - {$end})";
                }
            } else {
                $options[$key] = $label;
            }
        }

        return $options;
    }

    /**
     * Resolve schedule_start_column identifier from a schedule type.
     */
    public static function resolveScheduleStartColumn(?string $scheduleType): ?string
    {
        return match (self::normalizeScheduleType($scheduleType)) {
            'shift1' => 'shift1_start',
            'shift2' => 'shift2_start',
            'shift3' => 'shift3_start',
            'broken1' => 'broken_shift1_start',
            'broken2' => 'broken_shift2_start',
            'broken3' => 'broken_shift3_start',
            'regular' => 'reg_sched_start',
            default => null,
        };
    }

    /**
     * Resolve default day_part from a schedule type.
     */
    public static function resolveDayPart(?string $scheduleType): string
    {
        return match (self::normalizeScheduleType($scheduleType)) {
            'broken1' => DtrDayPartService::MORNING,
            'broken2' => DtrDayPartService::AFTERNOON,
            default => DtrDayPartService::WHOLE_DAY,
        };
    }

    /**
     * Validate an attachment file for change requests.
     */
    public function validateAttachment(mixed $path, ?string $originalName = null): void
    {
        if (blank($path)) {
            throw ValidationException::withMessages([
                'attachment_path' => 'An attachment is required for Missing DTR Entries requests.',
            ]);
        }

        $disk = Storage::disk('local');
        if (! $disk->exists((string) $path)) {
            throw ValidationException::withMessages([
                'attachment_path' => 'The uploaded attachment file could not be found. Please upload again.',
            ]);
        }

        $extension = strtolower(pathinfo($originalName ?: (string) $path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['png', 'jpg', 'jpeg'], true)) {
            throw ValidationException::withMessages([
                'attachment_path' => 'The attachment must be a file of type: png, jpg, jpeg.',
            ]);
        }

        $size = $disk->size((string) $path);
        if ($size > 2560 * 1024) { // 2.5 MB
            throw ValidationException::withMessages([
                'attachment_path' => 'The attachment file size must not exceed 2.5 MB.',
            ]);
        }

        $mimeType = $disk->mimeType((string) $path);
        if (! in_array(strtolower((string) $mimeType), ['image/png', 'image/jpeg', 'image/jpg', 'image/pjpeg'], true)) {
            throw ValidationException::withMessages([
                'attachment_path' => 'The attachment must be a valid PNG or JPEG image.',
            ]);
        }
    }

    /**
     * Submit multiple missing DTR entries for an unlocked payroll period.
     */
    public function submitMissingDtrEntries(Employee $employee, array $data): DtrChangeRequest
    {
        return DB::transaction(function () use ($employee, $data): DtrChangeRequest {
            $employee = Employee::query()
                ->with('branch')
                ->whereKey($employee->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (blank($employee->branch_id) || ! $employee->branch) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => 'Your employee profile is not assigned to a branch.',
                ]);
            }

            $period = PayrollPeriod::query()->find($data['payroll_period_id'] ?? null);
            if (! $period) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => 'Select a valid payroll period.',
                ]);
            }

            if ($period->is_locked) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => "Cannot submit a change request for locked payroll period ({$period->title}).",
                ]);
            }

            $rawEntries = $data['entries'] ?? [];
            if (! is_array($rawEntries) || count($rawEntries) === 0) {
                throw ValidationException::withMessages([
                    'entries' => 'At least one missing DTR entry must be added.',
                ]);
            }

            $explanation = trim((string) ($data['explanation'] ?? $data['description'] ?? ''));
            if ($explanation === '') {
                throw ValidationException::withMessages([
                    'explanation' => 'An explanation or reason is required.',
                ]);
            }

            if (mb_strlen($explanation) > 2000) {
                throw ValidationException::withMessages([
                    'explanation' => 'The explanation must not exceed 2,000 characters.',
                ]);
            }

            // Validate required attachment
            $this->validateAttachment(
                $data['attachment_path'] ?? null,
                $data['attachment_original_name'] ?? null
            );

            $validatedEntries = [];
            $seenDateSchedules = [];
            $fingerprintId = (string) ($employee->fingerprint_id ?: $employee->uid ?: $employee->company_id);

            foreach ($rawEntries as $index => $entry) {
                $rowNum = $index + 1;
                $dateInStr = trim((string) ($entry['date_in'] ?? ''));
                $timeInStr = trim((string) ($entry['time_in'] ?? ''));
                $dateOutStr = trim((string) ($entry['date_out'] ?? ''));
                $timeOutStr = trim((string) ($entry['time_out'] ?? ''));
                $scheduleType = trim((string) ($entry['schedule_type'] ?? 'Regular'));
                $normScheduleType = self::normalizeScheduleType($scheduleType);

                if ($dateInStr === '' || $timeInStr === '' || $dateOutStr === '' || $timeOutStr === '') {
                    throw ValidationException::withMessages([
                        'entries' => "Entry #{$rowNum} is missing required date or time information.",
                    ]);
                }

                $dateIn = Carbon::parse($dateInStr);
                $dateOut = Carbon::parse($dateOutStr);

                if ($dateIn->lessThan($period->date_start) || $dateIn->greaterThan($period->date_end)) {
                    throw ValidationException::withMessages([
                        'entries' => "Entry #{$rowNum} Date In ({$dateInStr}) must be within payroll period {$period->title}.",
                    ]);
                }

                if ($dateOut->lessThan($period->date_start) || $dateOut->greaterThan(Carbon::parse($period->date_end)->addDay())) {
                    throw ValidationException::withMessages([
                        'entries' => "Entry #{$rowNum} Date Out ({$dateOutStr}) must be within the payroll period window.",
                    ]);
                }

                if ($dateOut->lessThan($dateIn)) {
                    throw ValidationException::withMessages([
                        'entries' => "Entry #{$rowNum}: Date Out cannot be earlier than Date In.",
                    ]);
                }

                $actualIn = Carbon::parse($dateIn->toDateString() . ' ' . $timeInStr);
                $actualOut = Carbon::parse($dateOut->toDateString() . ' ' . $timeOutStr);

                if ($dateIn->toDateString() === $dateOut->toDateString() && $actualOut->lessThanOrEqualTo($actualIn)) {
                    throw ValidationException::withMessages([
                        'entries' => "Entry #{$rowNum}: Time Out must be later than Time In for date {$dateIn->format('M d, Y')}.",
                    ]);
                }

                $dateInKey = $dateIn->toDateString();
                $entryDateSchedKey = $dateInKey . '::' . $normScheduleType;
                if (in_array($entryDateSchedKey, $seenDateSchedules, true)) {
                    $schedLabel = self::scheduleTypeLabel($scheduleType);
                    throw ValidationException::withMessages([
                        'entries' => "Duplicate entry for date {$dateIn->format('M d, Y')} with schedule type {$schedLabel} found in this request. Each entry on the same date must have a different schedule type.",
                    ]);
                }
                $seenDateSchedules[] = $entryDateSchedKey;

                // Check existing official / visible DTR records for the SAME schedule type on this date
                $existingOfficialTypes = Dtr::query()
                    ->where('payroll_period_id', $period->id)
                    ->whereDate('date_in', $dateInKey)
                    ->where('fingerprint_id', $fingerprintId)
                    ->pluck('schedule_type')
                    ->map(fn ($t) => self::normalizeScheduleType($t))
                    ->all();

                $existingVisibleTypes = EmployeeVisibleDtr::query()
                    ->where('payroll_period_id', $period->id)
                    ->whereDate('date_in', $dateInKey)
                    ->where('employee_id', $employee->id)
                    ->pluck('schedule_type')
                    ->map(fn ($t) => self::normalizeScheduleType($t))
                    ->all();

                if (in_array($normScheduleType, $existingOfficialTypes, true) || in_array($normScheduleType, $existingVisibleTypes, true)) {
                    $schedLabel = self::scheduleTypeLabel($scheduleType);
                    throw ValidationException::withMessages([
                        'entries' => "An attendance record already exists for {$dateIn->format('M d, Y')} with schedule type {$schedLabel}. If this record is incorrect, please submit a Change Request (such as Time Entries Discrepancy or Forgot To Punch) for that date instead.",
                    ]);
                }

                // Check pending change request with the same schedule type on this date
                $pendingCrs = DtrChangeRequest::query()
                    ->where('employee_id', $employee->id)
                    ->where('payroll_period_id', $period->id)
                    ->pending()
                    ->get();

                foreach ($pendingCrs as $pendingCr) {
                    if ($pendingCr->request_type === DtrChangeRequest::TYPE_MISSING_DTR_ENTRIES) {
                        $pEntries = $pendingCr->proposed_values['entries'] ?? [];
                        foreach ($pEntries as $pEntry) {
                            if (($pEntry['date_in'] ?? null) === $dateInKey && self::normalizeScheduleType($pEntry['schedule_type'] ?? 'Regular') === $normScheduleType) {
                                $schedLabel = self::scheduleTypeLabel($scheduleType);
                                throw ValidationException::withMessages([
                                    'entries' => "An active Missing DTR Entries request already exists for {$dateIn->format('M d, Y')} ({$schedLabel}). Please wait for it to be reviewed or cancel it before submitting another request.",
                                ]);
                            }
                        }
                    } else {
                        $crDate = Carbon::parse($pendingCr->dtr_date ?: $pendingCr->date_from)->toDateString();
                        if ($crDate === $dateInKey) {
                            $crSched = self::normalizeScheduleType($pendingCr->original_values['schedule_type'] ?? $pendingCr->proposed_values['schedule_type'] ?? null);
                            if ($crSched === $normScheduleType) {
                                $schedLabel = self::scheduleTypeLabel($scheduleType);
                                throw ValidationException::withMessages([
                                    'entries' => "An active Change Request already exists for {$dateIn->format('M d, Y')} ({$schedLabel}). Please wait for the current request to be completed or cancel it before submitting another request.",
                                ]);
                            }
                        }
                    }
                }

                $validatedEntries[] = [
                    'date_in' => $dateIn->toDateString(),
                    'time_in' => Carbon::parse($timeInStr)->format('H:i:s'),
                    'date_out' => $dateOut->toDateString(),
                    'time_out' => Carbon::parse($timeOutStr)->format('H:i:s'),
                    'schedule_type' => $entry['schedule_type'] ?? 'Regular',
                    'schedule_start' => filled($entry['schedule_start'] ?? null) ? Carbon::parse($entry['schedule_start'])->format('H:i:s') : null,
                    'schedule_end' => filled($entry['schedule_end'] ?? null) ? Carbon::parse($entry['schedule_end'])->format('H:i:s') : null,
                    'comment' => trim((string) ($entry['comment'] ?? '')) ?: null,
                ];
            }

            usort($validatedEntries, fn ($a, $b) => strcmp($a['date_in'], $b['date_in']));

            $earliestDate = $validatedEntries[0]['date_in'];
            $latestDate = $validatedEntries[count($validatedEntries) - 1]['date_out'];

            $steps = $this->resolveWorkflow($employee);
            $firstStep = $steps[0];

            return DtrChangeRequest::query()->create([
                'employee_id' => $employee->id,
                'branch_id' => $employee->branch_id,
                'payroll_period_id' => $period->id,
                'dtr_date' => $earliestDate,
                'date_from' => $earliestDate,
                'date_to' => $latestDate,
                'request_type' => DtrChangeRequest::TYPE_MISSING_DTR_ENTRIES,
                'assigned_employee_id' => $firstStep['approver_employee_id'],
                'employee_name_snapshot' => $employee->full_name,
                'employee_company_id_snapshot' => $employee->company_id,
                'branch_name_snapshot' => $employee->branch->branch_name,
                'payroll_period_title_snapshot' => $period->title,
                'description' => $explanation,
                'attachment_path' => $data['attachment_path'],
                'attachment_original_name' => $data['attachment_original_name'] ?? basename($data['attachment_path']),
                'status' => DtrChangeRequest::STATUS_PENDING,
                'current_level' => 1,
                'total_levels' => count($steps),
                'current_step_label' => $firstStep['label'],
                'workflow_snapshot' => $steps,
                'approvals_history' => [],
                'proposed_values' => ['entries' => $validatedEntries],
            ]);
        });
    }

    /**
     * Submit a change request specifically for a single DTR entry.
     */
    public function submitFromDtr(Employee $employee, Dtr|EmployeeVisibleDtr $record, array $data): DtrChangeRequest
    {
        return DB::transaction(function () use ($employee, $record, $data): DtrChangeRequest {
            $employee = Employee::query()
                ->with('branch')
                ->whereKey($employee->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (blank($employee->branch_id) || ! $employee->branch) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => 'Your employee profile is not assigned to a branch.',
                ]);
            }

            $period = $record->payrollPeriod ?: PayrollPeriod::query()->find($record->payroll_period_id);
            if (! $period) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => 'No valid payroll period found for this D.T.R entry.',
                ]);
            }

            if ($period->is_locked || (bool) $record->is_locked || $record->isPayrollPeriodLocked()) {
                throw ValidationException::withMessages([
                    'request_type' => "Cannot submit a change request for locked payroll period ({$period->title}).",
                ]);
            }

            $requestType = (string) ($data['request_type'] ?? '');
            if (! array_key_exists($requestType, DtrChangeRequest::requestTypeOptions())) {
                throw ValidationException::withMessages([
                    'request_type' => 'Select a valid change request type.',
                ]);
            }

            // Forgot To Punch Validation: Ensure selected DTR entry is actually a Forgot To Punch / incomplete punch entry
            if ($requestType === DtrChangeRequest::TYPE_FORGOT_TO_PUNCH) {
                $schedTypeLower = strtolower((string) ($record->schedule_type ?? ''));
                $isForgotSched = str_contains($schedTypeLower, 'forgot');
                $hasMissingPunch = blank($record->time_in) || blank($record->time_out);

                if (! $isForgotSched && ! $hasMissingPunch) {
                    throw ValidationException::withMessages([
                        'request_type' => 'The selected DTR entry is not a Forgot To Punch record. Please select Time Entries Discrepancy or another appropriate request type.',
                    ]);
                }
            }

            // Minimum Overtime Validation (Section 3)
            $proposedValues = null;
            if ($requestType === DtrChangeRequest::TYPE_OVERTIME_APPROVAL) {
                $earlyOt = max(0, (int) ($record->early_clock_in ?? 0));
                $regularOt = max(0, (int) ($record->overtime ?? 0));

                if ($earlyOt < 30 && $regularOt < 30) {
                    throw ValidationException::withMessages([
                        'request_type' => 'Overtime approval cannot be requested when both Early OT ('.$earlyOt.' mins) and Regular OT ('.$regularOt.' mins) are below 30 minutes.',
                    ]);
                }

                $scope = $data['overtime_request_scope'] ?? null;
                if (! in_array($scope, [DtrChangeRequest::OT_SCOPE_BOTH, DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY, DtrChangeRequest::OT_SCOPE_EARLY_ONLY], true)) {
                    if ($earlyOt >= 30 && $regularOt >= 30) {
                        $scope = DtrChangeRequest::OT_SCOPE_BOTH;
                    } elseif ($regularOt >= 30) {
                        $scope = DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY;
                    } else {
                        $scope = DtrChangeRequest::OT_SCOPE_EARLY_ONLY;
                    }
                }

                if ($scope === DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY && $regularOt < 30) {
                    throw ValidationException::withMessages([
                        'overtime_request_scope' => 'Regular Overtime must be at least 30 minutes to request Overtime Only.',
                    ]);
                }

                if ($scope === DtrChangeRequest::OT_SCOPE_EARLY_ONLY && $earlyOt < 30) {
                    throw ValidationException::withMessages([
                        'overtime_request_scope' => 'Early Clock In must be at least 30 minutes to request Early Clock In Overtime Only.',
                    ]);
                }

                if ($scope === DtrChangeRequest::OT_SCOPE_BOTH) {
                    if ($earlyOt < 30) {
                        throw ValidationException::withMessages([
                            'overtime_request_scope' => 'Early Clock In is only '.$earlyOt.' minutes (minimum 30 mins). Please select Overtime Only.',
                        ]);
                    }
                    if ($regularOt < 30) {
                        throw ValidationException::withMessages([
                            'overtime_request_scope' => 'Regular Overtime is only '.$regularOt.' minutes (minimum 30 mins). Please select Early Clock In Only.',
                        ]);
                    }
                }

                $proposedValues = [
                    'overtime_request_scope' => $scope,
                ];
            }

            $explanation = trim((string) ($data['explanation'] ?? $data['description'] ?? ''));
            if ($explanation === '') {
                throw ValidationException::withMessages([
                    'explanation' => 'An explanation or reason is required.',
                ]);
            }

            if (mb_strlen($explanation) > 2000) {
                throw ValidationException::withMessages([
                    'explanation' => 'The explanation must not exceed 2,000 characters.',
                ]);
            }

            $dtrDate = Carbon::parse($record->date_in)->toDateString();

            // Duplicate Request Prevention
            if ($requestType === DtrChangeRequest::TYPE_DELETE_TIME_ENTRY) {
                $duplicateQuery = DtrChangeRequest::query()
                    ->where('employee_id', $employee->id)
                    ->where('request_type', $requestType)
                    ->where(function ($q) use ($record) {
                        if ($record instanceof EmployeeVisibleDtr) {
                            $q->where('employee_visible_dtr_id', $record->id);
                        } else {
                            $q->where('dtr_id', $record->id);
                        }
                    })
                    ->pending();
            } else {
                $duplicateQuery = DtrChangeRequest::query()
                    ->where('employee_id', $employee->id)
                    ->where(function ($q) use ($requestType, $dtrDate, $record) {
                        $q->where('request_type', $requestType)
                            ->where(function ($sub) use ($dtrDate, $record) {
                                $sub->whereDate('dtr_date', $dtrDate);
                                if ($record->id) {
                                    $sub->orWhere('dtr_id', $record->id)
                                        ->orWhere('employee_visible_dtr_id', $record->id);
                                }
                            });
                    })
                    ->pending();
            }

            if ($duplicateQuery->exists()) {
                $typeLabel = DtrChangeRequest::requestTypeOptions()[$requestType] ?? 'Change Request';
                throw ValidationException::withMessages([
                    'request_type' => "An active {$typeLabel} request already exists for this DTR entry. Please wait for the current request to be completed or cancel it before submitting another request.",
                ]);
            }

            $originalValues = [
                'dtr_uid' => (int) $record->id,
                'target_model' => get_class($record),
                'target_table' => $record->getTable(),
                'date_in' => (string) $record->date_in,
                'time_in' => (string) ($record->time_in ?? ''),
                'date_out' => (string) ($record->date_out ?? $record->date_in),
                'time_out' => (string) ($record->time_out ?? ''),
                'schedule_type' => (string) ($record->schedule_type ?? ''),
                'schedule_start' => (string) ($record->schedule_start ?? ''),
                'schedule_end' => (string) ($record->schedule_end ?? ''),
                'day_part' => (string) ($record->day_part ?? ''),
                'late' => (int) ($record->late ?? 0),
                'undertime' => (int) ($record->undertime ?? 0),
                'overtime' => (int) ($record->overtime ?? 0),
                'early_clock_in' => (int) ($record->early_clock_in ?? 0),
                'credited_overtime' => (int) ($record->credited_overtime ?? 0),
                'credited_early_clock_in' => (int) ($record->credited_early_clock_in ?? 0),
                'work_hrs' => (int) ($record->work_hrs ?? 0),
                'credited_work_hrs' => (int) ($record->credited_work_hrs ?? 0),
                'is_absent' => (bool) ($record->is_absent ?? false),
                'absence_minutes' => (int) ($record->absence_minutes ?? 0),
                'overtime_status' => (string) ($record->overtime_status ?? ''),
                'comment' => (string) ($record->comment ?? ''),
            ];

            if ($requestType === DtrChangeRequest::TYPE_DELETE_TIME_ENTRY) {
                $proposedValues = [
                    'action' => 'delete',
                    'dtr_uid' => (int) $record->id,
                    'target_id' => (int) $record->id,
                    'target_type' => $record instanceof EmployeeVisibleDtr ? 'employee_visible_dtr' : 'dtr',
                    'target_table' => $record->getTable(),
                    'employee_id' => (int) $employee->id,
                    'dtr_date' => (string) $dtrDate,
                    'payroll_period_id' => (int) $period->id,
                    'branch_id' => (int) $employee->branch_id,
                    'record_snapshot' => $originalValues,
                ];
            }

            $steps = $this->resolveWorkflow($employee);
            $firstStep = $steps[0];

            return DtrChangeRequest::query()->create([
                'employee_id' => $employee->id,
                'branch_id' => $employee->branch_id,
                'payroll_period_id' => $period->id,
                'dtr_id' => $record instanceof EmployeeVisibleDtr ? null : $record->id,
                'employee_visible_dtr_id' => $record instanceof EmployeeVisibleDtr ? $record->id : null,
                'dtr_date' => $dtrDate,
                'assigned_employee_id' => $firstStep['approver_employee_id'],
                'employee_name_snapshot' => $employee->full_name,
                'employee_company_id_snapshot' => $employee->company_id,
                'branch_name_snapshot' => $employee->branch->branch_name,
                'payroll_period_title_snapshot' => $period->title,
                'date_from' => $dtrDate,
                'date_to' => Carbon::parse($record->date_out ?? $record->date_in)->toDateString(),
                'request_type' => $requestType,
                'description' => $explanation,
                'attachment_path' => $data['attachment_path'] ?? null,
                'attachment_original_name' => $data['attachment_original_name'] ?? (filled($data['attachment_path'] ?? null) ? basename($data['attachment_path']) : null),
                'status' => DtrChangeRequest::STATUS_PENDING,
                'current_level' => 1,
                'total_levels' => count($steps),
                'current_step_label' => $firstStep['label'],
                'workflow_snapshot' => $steps,
                'approvals_history' => [],
                'original_values' => $originalValues,
                'proposed_values' => $proposedValues,
            ]);
        });
    }

    /**
     * Save proposed corrections performed by the first-level approver or HR final reviewer.
     */
    public function saveCorrections(DtrChangeRequest $request, Employee|User $editor, array $data): DtrChangeRequest
    {
        return DB::transaction(function () use ($request, $editor, $data): DtrChangeRequest {
            $request = DtrChangeRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status !== DtrChangeRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => 'Corrections can only be saved while the request is pending.',
                ]);
            }

            if ($request->payrollPeriod?->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Corrections cannot be saved because the payroll period is locked.',
                ]);
            }

            if (! $request->is_first_level && ! $request->isPendingHrApproval()) {
                throw new AuthorizationException('Only the first-level approver or HR can edit DTR information.');
            }

            if (! $request->is_editable_type) {
                throw ValidationException::withMessages([
                    'request_type' => 'D.T.R editing is not permitted for this request type.',
                ]);
            }

            if (! $request->canBeEditedBy($editor)) {
                throw new AuthorizationException('You are not authorized to edit DTR information for this request.');
            }

            $editorIdentity = $this->resolveReviewerIdentity($editor);

            // 1. Verify employee and assigned station/branch ownership
            $employee = $request->employee ?: Employee::query()->find($request->employee_id);
            $branchId = $request->branch_id ?: $employee?->branch_id;
            $branch = $request->branch ?: ($branchId ? Branch::query()->find($branchId) : null);

            if (! $branch) {
                throw ValidationException::withMessages([
                    'branch_id' => "No schedule configuration is available for this employee's assigned station or branch.",
                ]);
            }

            if (filled($employee?->branch_id) && (int) $employee->branch_id !== (int) $branch->id) {
                throw ValidationException::withMessages([
                    'branch_id' => 'The employee is not assigned to the expected branch.',
                ]);
            }

            // Verify target DTR entry belongs to the requesting employee
            $targetRecord = $request->targetDtrRecord();
            if ($targetRecord) {
                $targetEmpId = $targetRecord->employee_id ?? null;
                if (filled($targetEmpId) && (int) $targetEmpId !== (int) $request->employee_id) {
                    throw ValidationException::withMessages([
                        'employee_id' => 'The selected DTR record does not belong to the requesting employee.',
                    ]);
                }
            }

            // 2. Validate schedule type against branch configuration
            $availableOptions = $this->availableScheduleOptionsForBranch($branch, withTimes: false);
            if (empty($availableOptions)) {
                throw ValidationException::withMessages([
                    'schedule_type' => "No schedule configuration is available for this employee's assigned station or branch.",
                ]);
            }

            $rawScheduleType = $data['schedule_type']
                ?? $request->proposed_values['schedule_type']
                ?? $request->original_values['schedule_type']
                ?? $request->targetDtrRecord()?->schedule_type
                ?? 'Regular';

            $normScheduleType = self::normalizeScheduleType($rawScheduleType);
            $canonicalScheduleType = null;
            foreach ($availableOptions as $key => $label) {
                if (self::normalizeScheduleType($key) === $normScheduleType) {
                    $canonicalScheduleType = $key;
                    break;
                }
            }

            if (! $canonicalScheduleType) {
                throw ValidationException::withMessages([
                    'schedule_type' => "The selected schedule type is not configured for {$branch->branch_name}.",
                ]);
            }

            // 3. Re-derive Schedule Start and Schedule End from branch configuration (do not trust browser)
            [$resolvedStart, $resolvedEnd] = $this->resolveBranchScheduleTimes($branch, $canonicalScheduleType, formatForInput: false);
            if (blank($resolvedStart) || blank($resolvedEnd)) {
                throw ValidationException::withMessages([
                    'schedule_type' => "The selected schedule type has incomplete times configured for {$branch->branch_name}.",
                ]);
            }
            $scheduleStart = $resolvedStart;
            $scheduleEnd = $resolvedEnd;

            // 4. Validate Time In and Time Out
            $timeInRaw = trim((string) ($data['time_in'] ?? ''));
            $timeOutRaw = trim((string) ($data['time_out'] ?? ''));

            if ($timeInRaw === '') {
                throw ValidationException::withMessages([
                    'time_in' => 'Time In is required.',
                ]);
            }

            if ($timeOutRaw === '') {
                throw ValidationException::withMessages([
                    'time_out' => 'Time Out is required.',
                ]);
            }

            try {
                $parsedTimeIn = Carbon::parse($timeInRaw);
            } catch (\Throwable) {
                throw ValidationException::withMessages([
                    'time_in' => 'Please provide a valid time format for Time In.',
                ]);
            }

            try {
                $parsedTimeOut = Carbon::parse($timeOutRaw);
            } catch (\Throwable) {
                throw ValidationException::withMessages([
                    'time_out' => 'Please provide a valid time format for Time Out.',
                ]);
            }

            // 5. Date and overnight-shift validation
            $dateIn = Carbon::parse($data['date_in'] ?? $request->dtr_date)->toDateString();
            $dateOut = Carbon::parse($data['date_out'] ?? $dateIn)->toDateString();

            if (Carbon::parse($dateOut)->lessThan(Carbon::parse($dateIn))) {
                throw ValidationException::withMessages([
                    'date_out' => 'Date Out cannot be earlier than Date In.',
                ]);
            }

            $isOvernightSchedule = Carbon::parse($scheduleEnd)->lessThanOrEqualTo(Carbon::parse($scheduleStart));
            $actualIn = Carbon::parse("{$dateIn} {$parsedTimeIn->format('H:i:s')}");
            $actualOut = Carbon::parse("{$dateOut} {$parsedTimeOut->format('H:i:s')}");

            if ($actualOut->lessThan($actualIn)) {
                if ($dateOut === $dateIn && $isOvernightSchedule) {
                    $dateOut = Carbon::parse($dateIn)->addDay()->toDateString();
                } else {
                    throw ValidationException::withMessages([
                        'date_out' => 'Time Out cannot be earlier than Time In on the same date.',
                    ]);
                }
            }

            $scheduleStartColumn = self::resolveScheduleStartColumn($canonicalScheduleType);
            $dayPart = $data['day_part'] ?? self::resolveDayPart($canonicalScheduleType);
            $comment = trim((string) ($data['comment'] ?? ''));

            $proposedValues = [
                'date_in' => $dateIn,
                'time_in' => $timeInRaw,
                'date_out' => $dateOut,
                'time_out' => $timeOutRaw,
                'schedule_start' => $scheduleStart,
                'schedule_end' => $scheduleEnd,
                'schedule_type' => $canonicalScheduleType,
                'schedule_start_column' => $scheduleStartColumn,
                'day_part' => $dayPart,
                'comment' => $comment,
            ];

            $request->update([
                'proposed_values' => $proposedValues,
                'first_level_editor_id' => $editorIdentity['employee']?->id ?? $request->first_level_editor_id,
                'first_level_edited_at' => now(),
            ]);

            return $request->refresh();
        });
    }

    /**
     * Approve the change request at the current level.
     */
    public function approve(
        DtrChangeRequest $request,
        Employee|User $reviewer,
        ?string $remarks = null,
        array $approvalData = [],
    ): DtrChangeRequest {
        return DB::transaction(function () use ($request, $reviewer, $remarks, $approvalData): DtrChangeRequest {
            $request = DtrChangeRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status !== DtrChangeRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending change requests can be approved.',
                ]);
            }

            if (! $request->canBeReviewedBy($reviewer)) {
                throw new AuthorizationException('You are not authorized to approve this change request.');
            }

            $period = $request->payrollPeriod;
            if ($period && $period->is_locked) {
                throw ValidationException::withMessages([
                    'status' => "Cannot approve change request because payroll period ({$period->title}) is locked.",
                ]);
            }

            $remarks = trim((string) $remarks);
            if (mb_strlen($remarks) > 2000) {
                throw ValidationException::withMessages([
                    'reviewer_remarks' => 'Remarks must not exceed 2,000 characters.',
                ]);
            }

            $identity = $this->resolveReviewerIdentity($reviewer);
            $proposed = $request->proposed_values ?? [];

            if ($request->is_overtime_type) {
                $targetRecord = $request->targetDtrRecord();
                $earlyCalculated = max(0, (int) ($request->original_values['early_clock_in'] ?? $targetRecord?->early_clock_in ?? 0));
                $otCalculated = max(0, (int) ($request->original_values['overtime'] ?? $targetRecord?->overtime ?? 0));
                $reqScope = $request->overtime_request_scope;

                if ($reqScope === DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY) {
                    $approvedScope = DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY;
                    $creditedOt = isset($approvalData['credited_overtime_minutes'])
                        ? (int) $approvalData['credited_overtime_minutes']
                        : ($proposed['credited_overtime'] ?? $otCalculated);
                    $creditedEarly = 0;
                } elseif ($reqScope === DtrChangeRequest::OT_SCOPE_EARLY_ONLY) {
                    $approvedScope = DtrChangeRequest::OT_SCOPE_EARLY_ONLY;
                    $creditedEarly = isset($approvalData['credited_early_minutes'])
                        ? (int) $approvalData['credited_early_minutes']
                        : ($proposed['credited_early_clock_in'] ?? $earlyCalculated);
                    $creditedOt = 0;
                } else {
                    $approvedScope = $approvalData['approved_overtime_scope']
                        ?? ($proposed['approved_overtime_scope'] ?? DtrChangeRequest::OT_SCOPE_BOTH);

                    if ($approvedScope === DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY) {
                        $creditedOt = isset($approvalData['credited_overtime_minutes'])
                            ? (int) $approvalData['credited_overtime_minutes']
                            : ($proposed['credited_overtime'] ?? $otCalculated);
                        $creditedEarly = 0;
                    } elseif ($approvedScope === DtrChangeRequest::OT_SCOPE_EARLY_ONLY) {
                        $creditedEarly = isset($approvalData['credited_early_minutes'])
                            ? (int) $approvalData['credited_early_minutes']
                            : ($proposed['credited_early_clock_in'] ?? $earlyCalculated);
                        $creditedOt = 0;
                    } else {
                        $approvedScope = DtrChangeRequest::OT_SCOPE_BOTH;
                        $creditedOt = isset($approvalData['credited_overtime_minutes'])
                            ? (int) $approvalData['credited_overtime_minutes']
                            : ($proposed['credited_overtime'] ?? $otCalculated);
                        $creditedEarly = isset($approvalData['credited_early_minutes'])
                            ? (int) $approvalData['credited_early_minutes']
                            : ($proposed['credited_early_clock_in'] ?? $earlyCalculated);
                    }
                }

                $proposed['approved_overtime_scope'] = $approvedScope;
                $proposed['credited_overtime'] = $creditedOt;
                $proposed['credited_early_clock_in'] = $creditedEarly;
            }

            $isHrStage = $request->isCurrentStepHr();

            $historyEntry = [
                'level' => (int) $request->current_level,
                'label' => $isHrStage
                    ? 'HR Final Approval'
                    : (string) ($request->current_step_label ?? "Level {$request->current_level}"),
                'action' => 'Approved',
                'actor_id' => $identity['actor_id'],
                'actor_name' => $identity['actor_name'],
                'acted_at' => now()->toDateTimeString(),
                'remarks' => $remarks !== '' ? $remarks : null,
            ];

            if ($request->is_overtime_type) {
                $historyEntry['overtime_decision'] = [
                    'scope' => $proposed['approved_overtime_scope'] ?? null,
                    'credited_overtime' => $proposed['credited_overtime'] ?? 0,
                    'credited_early_clock_in' => $proposed['credited_early_clock_in'] ?? 0,
                ];
            }

            $history = $request->approvals_history ?? [];
            $history[] = $historyEntry;

            $isFinal = (int) $request->current_level >= (int) $request->total_levels;

            if (! $isFinal) {
                // Multi-level workflow: Advance to next configured level (which may be another employee or HR Final Approval)
                $nextLevel = (int) $request->current_level + 1;
                $snapshot = $request->workflow_snapshot ?? [];
                $nextStep = collect($snapshot)->firstWhere('level', $nextLevel);

                $request->update([
                    'current_level' => $nextLevel,
                    'current_step_label' => $nextStep['label'] ?? "Level {$nextLevel}",
                    'assigned_employee_id' => $nextStep['approver_employee_id'] ?? null,
                    'approvals_history' => $history,
                    'proposed_values' => $proposed,
                ]);

                return $request->refresh();
            }

            // Final Approval (last workflow approver or HR Final Approval): Officially finalize DTR
            $request->proposed_values = $proposed;
            $this->finalizeApprovedRequest($request, $identity['employee']);

            $request->update([
                'status' => DtrChangeRequest::STATUS_APPROVED,
                'reviewed_by_employee_id' => $identity['employee']?->id,
                'reviewed_at' => now(),
                'reviewer_remarks' => $remarks !== '' ? $remarks : null,
                'approvals_history' => $history,
                'proposed_values' => $proposed,
            ]);

            return $request->refresh();
        });
    }

    /**
     * Reject the change request.
     */
    public function reject(DtrChangeRequest $request, Employee|User $reviewer, string $remarks): DtrChangeRequest
    {
        return DB::transaction(function () use ($request, $reviewer, $remarks): DtrChangeRequest {
            $request = DtrChangeRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status !== DtrChangeRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending change requests can be rejected.',
                ]);
            }

            if (! $request->canBeReviewedBy($reviewer)) {
                throw new AuthorizationException('You are not authorized to reject this change request.');
            }

            $remarks = trim((string) $remarks);
            if ($remarks === '') {
                throw ValidationException::withMessages([
                    'reviewer_remarks' => 'Explain why the D.T.R change request is being rejected.',
                ]);
            }

            if (mb_strlen($remarks) > 2000) {
                throw ValidationException::withMessages([
                    'reviewer_remarks' => 'Remarks must not exceed 2,000 characters.',
                ]);
            }

            $identity = $this->resolveReviewerIdentity($reviewer);
            $isHrStage = $request->isCurrentStepHr();

            $history = $request->approvals_history ?? [];
            $history[] = [
                'level' => (int) $request->current_level,
                'label' => $isHrStage
                    ? 'HR Final Approval'
                    : (string) ($request->current_step_label ?? "Level {$request->current_level}"),
                'action' => 'Rejected',
                'actor_id' => $identity['actor_id'],
                'actor_name' => $identity['actor_name'],
                'acted_at' => now()->toDateTimeString(),
                'remarks' => $remarks,
            ];

            // Rejection stops the workflow immediately without modifying DTR.
            if ($request->is_editable_type && filled($request->original_values) && ! $request->payrollPeriod?->is_locked) {
                $targetRecord = $request->targetDtrRecord();
                if ($targetRecord instanceof Dtr && ! $targetRecord->is_locked) {
                    $this->revertDtrToOriginal($targetRecord, $request->original_values);
                }
            }

            $request->update([
                'status' => DtrChangeRequest::STATUS_REJECTED,
                'rejection_reason' => $remarks,
                'reviewer_remarks' => $remarks,
                'reviewed_by_employee_id' => $identity['employee']?->id,
                'reviewed_at' => now(),
                'approvals_history' => $history,
            ]);

            return $request->refresh();
        });
    }

    /**
     * Cancel a change request by the requesting employee.
     */
    public function cancel(DtrChangeRequest $request, Employee $employee): DtrChangeRequest
    {
        return DB::transaction(function () use ($request, $employee): DtrChangeRequest {
            $request = DtrChangeRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status !== DtrChangeRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending change requests can be cancelled.',
                ]);
            }

            if ($request->payrollPeriod?->is_locked) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot cancel a change request for a locked payroll period.',
                ]);
            }

            if (! $request->canBeCancelledBy($employee)) {
                throw new AuthorizationException('You are not authorized to cancel this change request.');
            }

            $history = $request->approvals_history ?? [];
            $history[] = [
                'level' => (int) $request->current_level,
                'label' => 'Employee',
                'action' => 'Cancelled',
                'actor_id' => $employee->id,
                'actor_name' => $employee->full_name,
                'acted_at' => now()->toDateTimeString(),
                'remarks' => 'Cancelled by employee.',
            ];

            if ($request->is_editable_type && filled($request->original_values) && ! $request->payrollPeriod?->is_locked) {
                $targetRecord = $request->targetDtrRecord();
                if ($targetRecord instanceof Dtr && ! $targetRecord->is_locked) {
                    $this->revertDtrToOriginal($targetRecord, $request->original_values);
                }
            }

            $request->update([
                'status' => DtrChangeRequest::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'approvals_history' => $history,
            ]);

            return $request->refresh();
        });
    }

    /**
     * Apply final approval mutations and recalculate DTR records.
     */
    protected function finalizeApprovedRequest(DtrChangeRequest $request, ?Employee $reviewer = null): void
    {
        $period = $request->payrollPeriod ?: PayrollPeriod::query()->find($request->payroll_period_id);
        if (! $period || $period->is_locked) {
            throw new \DomainException("Cannot finalize approval: The payroll period ({$request->payroll_period_title_snapshot}) is locked.");
        }

        // 1. Overtime Approval Request
        if ($request->is_overtime_type) {
            $targetRecord = $request->targetDtrRecord();
            $scope = $request->proposed_values['approved_overtime_scope']
                ?? $request->overtime_request_scope;

            $earlyCalculated = max(0, (int) ($request->original_values['early_clock_in'] ?? $targetRecord?->early_clock_in ?? 0));
            $otCalculated = max(0, (int) ($request->original_values['overtime'] ?? $targetRecord?->overtime ?? 0));

            $creditedOt = match ($scope) {
                DtrChangeRequest::OT_SCOPE_EARLY_ONLY => 0,
                default => (int) ($request->proposed_values['credited_overtime'] ?? $otCalculated),
            };

            $creditedEarly = match ($scope) {
                DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY => 0,
                default => (int) ($request->proposed_values['credited_early_clock_in'] ?? $earlyCalculated),
            };

            if ($targetRecord instanceof EmployeeVisibleDtr) {
                if (app(OvertimeApprovalService::class)->hasApprovableOvertime($targetRecord)) {
                    app(OvertimeApprovalService::class)->approve(
                        $targetRecord,
                        $creditedOt,
                        $creditedEarly,
                    );
                } else {
                    $baseCreditedMinutes = max(0, (int) $targetRecord->credited_work_hrs - (int) $targetRecord->credited_overtime);
                    $totalCredited = $creditedOt + $creditedEarly;
                    $targetRecord->forceFill([
                        'early_clock_in_approved' => $creditedEarly >= 30,
                        'overtime_approved' => $creditedOt >= 30,
                        'overtime_status' => 'Approved',
                        'credited_early_clock_in' => $creditedEarly,
                        'credited_overtime' => $totalCredited,
                        'credited_work_hrs' => $baseCreditedMinutes + $totalCredited,
                    ])->save();
                }

                $targetRecord->forceFill([
                    'is_manually_edited' => true,
                    'manual_edited_at' => now(),
                    'manual_edited_by_employee_id' => $reviewer?->id,
                ])->save();

                app(DtrSynchronizationService::class)->syncVisibleToOfficial(
                    $targetRecord->refresh(),
                    'change_request_overtime_approval',
                    $reviewer,
                );
            } elseif ($targetRecord instanceof Dtr) {
                if (app(OvertimeApprovalService::class)->hasApprovableOvertime($targetRecord)) {
                    app(OvertimeApprovalService::class)->approve(
                        $targetRecord,
                        $creditedOt,
                        $creditedEarly,
                    );
                } else {
                    $baseCreditedMinutes = max(0, (int) $targetRecord->credited_work_hrs - (int) $targetRecord->credited_overtime);
                    $totalCredited = $creditedOt + $creditedEarly;
                    $targetRecord->forceFill([
                        'early_clock_in_approved' => $creditedEarly >= 30,
                        'overtime_approved' => $creditedOt >= 30,
                        'overtime_status' => 'Approved',
                        'credited_early_clock_in' => $creditedEarly,
                        'credited_overtime' => $totalCredited,
                        'credited_work_hrs' => $baseCreditedMinutes + $totalCredited,
                    ])->save();
                }

                app(DtrSynchronizationService::class)->syncOfficialToVisible(
                    $targetRecord->refresh(),
                    'change_request_overtime_approval',
                    $reviewer,
                );
            }

            return;
        }

        // 2. Editable Request Types (Forgot to Punch / Time Discrepancy)
        if ($request->is_editable_type && filled($request->proposed_values)) {
            $targetRecord = $request->targetDtrRecord();

            if ($targetRecord instanceof EmployeeVisibleDtr) {
                $originalAnchor = [
                    'date_in' => $request->original_values['date_in'] ?? $targetRecord->date_in,
                    'schedule_type' => $request->original_values['schedule_type'] ?? $targetRecord->schedule_type,
                    'day_part' => $request->original_values['day_part'] ?? $targetRecord->day_part,
                    'time_in' => $request->original_values['time_in'] ?? $targetRecord->time_in,
                ];
                $counterpartOfficial = app(DtrSynchronizationService::class)->findCounterpartOfficial($targetRecord);

                $this->applyAndRecalculateDtr($targetRecord, $request->proposed_values, $reviewer);

                if ($counterpartOfficial) {
                    $this->applyAndRecalculateDtr($counterpartOfficial, $request->proposed_values, $reviewer);
                } else {
                    app(DtrSynchronizationService::class)->syncVisibleToOfficial(
                        $targetRecord->refresh(),
                        'change_request_correction',
                        $reviewer,
                        $originalAnchor,
                    );
                }

                app(DtrSynchronizationService::class)->recordAudit(
                    'change_request_finalized',
                    $targetRecord,
                    [
                        'request_id' => $request->id,
                        'request_type' => $request->request_type,
                        'proposed_values' => $request->proposed_values,
                    ],
                    $reviewer,
                );
            } elseif ($targetRecord instanceof Dtr) {
                $originalAnchor = [
                    'date_in' => $request->original_values['date_in'] ?? $targetRecord->date_in,
                    'schedule_type' => $request->original_values['schedule_type'] ?? $targetRecord->schedule_type,
                    'day_part' => $request->original_values['day_part'] ?? $targetRecord->day_part,
                    'time_in' => $request->original_values['time_in'] ?? $targetRecord->time_in,
                ];
                $counterpartVisible = app(DtrSynchronizationService::class)->findCounterpartVisible($targetRecord, (int) $request->employee_id);

                $this->applyAndRecalculateDtr($targetRecord, $request->proposed_values, $reviewer);

                if ($counterpartVisible) {
                    $this->applyAndRecalculateDtr($counterpartVisible, $request->proposed_values, $reviewer);
                } else {
                    app(DtrSynchronizationService::class)->syncOfficialToVisible(
                        $targetRecord->refresh(),
                        'change_request_correction',
                        $reviewer,
                        $originalAnchor,
                    );
                }

                app(DtrSynchronizationService::class)->recordAudit(
                    'change_request_finalized',
                    $targetRecord,
                    [
                        'request_id' => $request->id,
                        'request_type' => $request->request_type,
                        'proposed_values' => $request->proposed_values,
                    ],
                    $reviewer,
                );
            }

            return;
        }

        // 3. Missing DTR Entries Request
        if ($request->request_type === DtrChangeRequest::TYPE_MISSING_DTR_ENTRIES) {
            $period = PayrollPeriod::query()->find($request->payroll_period_id);
            if (! $period || $period->is_locked) {
                throw new \DomainException("Cannot finalize approval: The payroll period ({$request->payroll_period_title_snapshot}) is locked.");
            }

            $entries = $request->proposed_values['entries'] ?? [];
            if (empty($entries)) {
                throw new \DomainException('No proposed DTR entries found in this request.');
            }

            $employee = Employee::query()->find($request->employee_id);
            $fingerprintId = (string) ($employee?->fingerprint_id ?: $employee?->uid ?: $employee?->company_id);

            // All-or-nothing check: Verify NO conflicts exist for any proposed entry
            foreach ($entries as $entry) {
                $entryDate = Carbon::parse($entry['date_in'])->toDateString();
                $normEntryType = self::normalizeScheduleType($entry['schedule_type'] ?? 'Regular');

                $existingOfficialTypes = Dtr::query()
                    ->where('payroll_period_id', $period->id)
                    ->whereDate('date_in', $entryDate)
                    ->where('fingerprint_id', $fingerprintId)
                    ->pluck('schedule_type')
                    ->map(fn ($t) => self::normalizeScheduleType($t))
                    ->all();

                $existingVisibleTypes = EmployeeVisibleDtr::query()
                    ->where('payroll_period_id', $period->id)
                    ->whereDate('date_in', $entryDate)
                    ->where('employee_id', $request->employee_id)
                    ->pluck('schedule_type')
                    ->map(fn ($t) => self::normalizeScheduleType($t))
                    ->all();

                if (in_array($normEntryType, $existingOfficialTypes, true) || in_array($normEntryType, $existingVisibleTypes, true)) {
                    $schedLabel = self::scheduleTypeLabel($entry['schedule_type'] ?? 'Regular');
                    throw new \DomainException("Cannot finalize approval: An attendance record already exists for {$entryDate} with schedule type {$schedLabel}. No records were created.");
                }
            }

            // Create each approved missing entry
            foreach ($entries as $entry) {
                $this->createMissingDtrEntry($request, $entry, $reviewer);
            }

            return;
        }

        // 4. Delete Time Entry Request
        if ($request->request_type === DtrChangeRequest::TYPE_DELETE_TIME_ENTRY) {
            $period = PayrollPeriod::query()->find($request->payroll_period_id);
            if (! $period || $period->is_locked) {
                throw new \DomainException("Cannot finalize request: The payroll period ({$request->payroll_period_title_snapshot}) is locked.");
            }

            // Step 1 & 2: Retrieve the stored DTR UID and target type
            $isTargetVisible = filled($request->employee_visible_dtr_id)
                || ($request->proposed_values['target_type'] ?? '') === 'employee_visible_dtr';

            $targetUid = $request->employee_visible_dtr_id
                ?: $request->dtr_id
                ?: ($request->proposed_values['dtr_uid'] ?? $request->proposed_values['target_id'] ?? null);

            if (blank($targetUid)) {
                throw new \DomainException('The selected DTR entry no longer exists or has already been deleted.');
            }

            // Step 3: Find the DTR record using that EXACT UID only (no date fallback!)
            $targetRecord = null;
            if ($isTargetVisible) {
                $targetRecord = EmployeeVisibleDtr::query()->find($targetUid);
            } else {
                $targetRecord = Dtr::query()->find($targetUid);
            }

            // If not found in primary target, check if it exists in the other model using that exact UID
            if (! $targetRecord) {
                if ($isTargetVisible) {
                    $targetRecord = Dtr::query()->find($targetUid);
                } else {
                    $targetRecord = EmployeeVisibleDtr::query()->find($targetUid);
                }
            }

            if (! $targetRecord) {
                throw new \DomainException('The selected DTR entry no longer exists or has already been deleted.');
            }

            // Step 4: Verify that the record still belongs to the requesting employee
            $employee = Employee::query()->find($request->employee_id);
            $fingerprintId = (string) ($employee?->fingerprint_id ?: $employee?->uid ?: $employee?->company_id);

            $belongsToEmployee = false;
            if (isset($targetRecord->employee_id) && (int) $targetRecord->employee_id === (int) $request->employee_id) {
                $belongsToEmployee = true;
            } elseif ($fingerprintId !== '' && isset($targetRecord->fingerprint_id) && (string) $targetRecord->fingerprint_id === $fingerprintId) {
                $belongsToEmployee = true;
            }

            if (! $belongsToEmployee) {
                throw new \DomainException('The selected DTR entry does not belong to the requesting employee.');
            }

            // Step 5: Verify that the record still belongs to the expected payroll period and branch
            if ((int) $targetRecord->payroll_period_id !== (int) $request->payroll_period_id) {
                throw new \DomainException('The selected DTR entry does not match the expected payroll period.');
            }

            if (filled($targetRecord->branch_id) && (int) $targetRecord->branch_id !== (int) $request->branch_id) {
                throw new \DomainException('The selected DTR entry does not match the expected branch.');
            }

            // Step 6: Verify that the Change Request is approved by the final approver (enforced by approve())

            // Step 7: Delete ONLY that exact DTR record matching that exact UID
            $exactId = (int) $targetRecord->id;
            $deletedCount = get_class($targetRecord)::where('id', $exactId)->forceDelete();

            // Step 8: Confirm deletion
            if ($deletedCount === 0) {
                throw new \DomainException('The selected DTR entry no longer exists or has already been deleted.');
            }

            // Step 9: Permanently delete the counterpart record in the other table to avoid orphaned records/discrepancies
            app(DtrSynchronizationService::class)->deleteCounterpart($targetRecord);

            app(DtrSynchronizationService::class)->recordAudit(
                'change_request_delete_dtr',
                $targetRecord,
                [
                    'request_id' => $request->id,
                    'deleted_record_id' => $exactId,
                ],
                $reviewer,
            );

            return;
        }
    }

    /**
     * Create official and visible DTR records for an approved missing DTR entry.
     */
    protected function createMissingDtrEntry(DtrChangeRequest $request, array $entry, ?Employee $reviewer = null): void
    {
        $employee = Employee::query()->find($request->employee_id);
        $branch = Branch::query()->find($request->branch_id);
        $period = PayrollPeriod::query()->find($request->payroll_period_id);

        $dateIn = Carbon::parse($entry['date_in'])->toDateString();
        $dateOut = Carbon::parse($entry['date_out'] ?? $entry['date_in'])->toDateString();
        $timeIn = Carbon::parse($entry['time_in'])->format('H:i:s');
        $timeOut = Carbon::parse($entry['time_out'])->format('H:i:s');
        $scheduleType = $entry['schedule_type'] ?? 'Regular';

        $scheduleStart = filled($entry['schedule_start'] ?? null)
            ? Carbon::parse($entry['schedule_start'])->format('H:i:s')
            : null;
        $scheduleEnd = filled($entry['schedule_end'] ?? null)
            ? Carbon::parse($entry['schedule_end'])->format('H:i:s')
            : null;

        $normType = self::normalizeScheduleType($scheduleType);

        // Auto-resolve schedule from branch if not provided
        if (blank($scheduleStart) || blank($scheduleEnd)) {
            [$resolvedStart, $resolvedEnd] = $this->resolveBranchScheduleTimes($branch, $scheduleType, formatForInput: false);
            if (blank($scheduleStart)) {
                $scheduleStart = $resolvedStart;
            }
            if (blank($scheduleEnd)) {
                $scheduleEnd = $resolvedEnd;
            }
        }

        $dayPart = DtrDayPartService::WHOLE_DAY;
        if ($normType === 'regular') {
            $dayPart = app(DtrDayPartService::class)->classifyRegularPunch(
                dateIn: $dateIn,
                timeIn: $timeIn,
                dateOut: $dateOut,
                timeOut: $timeOut,
                scheduleStart: $scheduleStart,
                scheduleEnd: $scheduleEnd,
                scheduleType: $scheduleType,
            );
        } elseif ($normType === 'broken1') {
            $dayPart = DtrDayPartService::MORNING;
        } elseif ($normType === 'broken2') {
            $dayPart = DtrDayPartService::AFTERNOON;
        }

        $calc = app(DtrCalculator::class)->calculate(
            dateIn: $dateIn,
            timeIn: $timeIn,
            dateOut: $dateOut,
            timeOut: $timeOut,
            scheduleStart: $scheduleStart,
            scheduleEnd: $scheduleEnd,
            scheduleStartColumn: null,
            scheduleType: $scheduleType,
            overtimeOnly: $scheduleType === 'Overtime',
            dayPart: $dayPart,
        );

        $fingerprintId = (string) ($employee->fingerprint_id ?: $employee->uid ?: $employee->company_id);
        $holidayData = $branch ? app(HolidayEntitlementService::class)->dtrHolidayData($employee, $dateIn, (int) $branch->id) : [];

        $dtrPayload = [
            'payroll_period_id' => $period->id,
            'branch_id' => $branch?->id ?? $employee->branch_id,
            'fingerprint_id' => $fingerprintId,
            'date_in' => $dateIn,
            'time_in' => $timeIn,
            'date_out' => $dateOut,
            'time_out' => $timeOut,
            'schedule_type' => $scheduleType,
            'day_part' => $dayPart,
            'entry_source' => DtrDayPartService::SOURCE_MANUAL,
            'schedule_start' => $scheduleStart,
            'schedule_end' => $scheduleEnd,
            'daily_rate' => $employee->daily_rate,
            'comment' => $entry['comment'] ?? null,
            'is_imported' => false,
            'is_locked' => false,
            'is_absent' => false,
            'absence_minutes' => 0,
            ...$holidayData,
            ...$calc,
        ];

        $official = Dtr::query()->create($dtrPayload);
        $visible = EmployeeVisibleDtr::query()->create([
            ...$dtrPayload,
            'employee_id' => $employee->id,
            'is_manually_edited' => true,
            'manual_edited_at' => now(),
            'manual_edited_by_employee_id' => $reviewer?->id,
            'needs_review' => false,
        ]);
    }

    /**
     * Reverts a DTR record to its recorded original values.
     */
    public function revertDtrToOriginal(Dtr|EmployeeVisibleDtr $record, array $originalValues): void
    {
        $timeIn = trim((string) ($originalValues['time_in'] ?? ''));
        $timeOut = trim((string) ($originalValues['time_out'] ?? ''));

        $record->forceFill([
            'date_in' => $originalValues['date_in'] ?? $record->date_in,
            'time_in' => $timeIn !== '' ? $timeIn : null,
            'date_out' => $originalValues['date_out'] ?? $record->date_out,
            'time_out' => $timeOut !== '' ? $timeOut : null,
            'schedule_type' => $originalValues['schedule_type'] ?? $record->schedule_type,
            'schedule_start' => $originalValues['schedule_start'] ?? $record->schedule_start,
            'schedule_end' => $originalValues['schedule_end'] ?? $record->schedule_end,
            'day_part' => $originalValues['day_part'] ?? $record->day_part,
            'late' => (int) ($originalValues['late'] ?? 0),
            'undertime' => (int) ($originalValues['undertime'] ?? 0),
            'overtime' => (int) ($originalValues['overtime'] ?? 0),
            'early_clock_in' => (int) ($originalValues['early_clock_in'] ?? 0),
            'credited_overtime' => (int) ($originalValues['credited_overtime'] ?? 0),
            'credited_early_clock_in' => (int) ($originalValues['credited_early_clock_in'] ?? 0),
            'work_hrs' => (int) ($originalValues['work_hrs'] ?? 0),
            'credited_work_hrs' => (int) ($originalValues['credited_work_hrs'] ?? 0),
            'is_absent' => (bool) ($originalValues['is_absent'] ?? false),
            'absence_minutes' => (int) ($originalValues['absence_minutes'] ?? 0),
            'overtime_status' => $originalValues['overtime_status'] ?? $record->overtime_status,
            'comment' => $originalValues['comment'] ?? $record->comment,
        ]);

        if ($record instanceof EmployeeVisibleDtr) {
            $record->forceFill([
                'is_manually_edited' => false,
                'manual_edited_at' => null,
                'manual_edited_by_employee_id' => null,
            ]);
        }

        $record->save();
    }

    /**
     * Applies proposed corrected values to a DTR record and runs recalculation.
     */
    public function applyAndRecalculateDtr(
        Dtr|EmployeeVisibleDtr $record,
        array $proposedValues,
        ?Employee $actor = null,
    ): void {
        $dateIn = $proposedValues['date_in'] ?? $record->date_in;
        $dateOut = $proposedValues['date_out'] ?? $record->date_out ?? $dateIn;
        $timeIn = trim((string) ($proposedValues['time_in'] ?? $record->time_in ?? ''));
        $timeOut = trim((string) ($proposedValues['time_out'] ?? $record->time_out ?? ''));
        $scheduleStart = $proposedValues['schedule_start'] ?? $record->schedule_start;
        $scheduleEnd = $proposedValues['schedule_end'] ?? $record->schedule_end;
        $scheduleType = $proposedValues['schedule_type'] ?? $record->schedule_type ?? 'Regular';
        $comment = $proposedValues['comment'] ?? $record->comment;

        $calc = [];
        if ($timeIn !== '' && $timeOut !== '') {
            $calc = app(DtrCalculator::class)->calculate(
                dateIn: (string) $dateIn,
                timeIn: (string) $timeIn,
                dateOut: (string) $dateOut,
                timeOut: (string) $timeOut,
                scheduleStart: $scheduleStart,
                scheduleEnd: $scheduleEnd,
                scheduleStartColumn: $proposedValues['schedule_start_column'] ?? null,
                scheduleType: (string) $scheduleType,
                overtimeOnly: $scheduleType === 'Overtime',
                dayPart: $proposedValues['day_part'] ?? $record->day_part ?? 'whole_day',
            );
        }

        $hasPendingOvertime = (($calc['overtime'] ?? 0) >= 30) || (($calc['early_clock_in'] ?? 0) >= 30);
        $overtimeStatus = $record->overtime_status === 'Approved'
            ? ($hasPendingOvertime ? 'Approved' : 'n/a')
            : ($calc['overtime_status'] ?? ($hasPendingOvertime ? 'Pending' : 'n/a'));

        $record->forceFill([
            'date_in' => $dateIn,
            'time_in' => $timeIn !== '' ? $timeIn : null,
            'date_out' => $dateOut,
            'time_out' => $timeOut !== '' ? $timeOut : null,
            'schedule_start' => $scheduleStart,
            'schedule_end' => $scheduleEnd,
            'schedule_type' => $scheduleType,
            'comment' => $comment,
            'late' => $calc['late'] ?? 0,
            'undertime' => $calc['undertime'] ?? 0,
            'overtime' => $calc['overtime'] ?? 0,
            'early_clock_in' => $calc['early_clock_in'] ?? 0,
            'credited_overtime' => $calc['credited_overtime'] ?? 0,
            'credited_early_clock_in' => $calc['credited_early_clock_in'] ?? 0,
            'work_hrs' => $calc['work_hrs'] ?? 0,
            'credited_work_hrs' => $calc['credited_work_hrs'] ?? 0,
            'overtime_status' => $overtimeStatus,
            'early_clock_in_approved' => $calc['early_clock_in_approved'] ?? ($record->early_clock_in_approved ?? false),
            'overtime_approved' => $calc['overtime_approved'] ?? ($record->overtime_approved ?? false),
            'is_absent' => false,
            'absence_minutes' => 0,
        ]);

        if ($record instanceof EmployeeVisibleDtr) {
            $record->forceFill([
                'is_manually_edited' => true,
                'manual_edited_at' => now(),
                'manual_edited_by_employee_id' => $actor?->id,
                'needs_review' => false,
                'review_reason' => null,
            ]);
        }

        $record->save();
    }

    protected function parseDate(mixed $value, string $field): CarbonImmutable
    {
        if (blank($value)) {
            throw ValidationException::withMessages([
                $field => 'Enter a valid date.',
            ]);
        }

        try {
            return CarbonImmutable::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $field => 'Enter a valid date.',
            ]);
        }
    }
}
