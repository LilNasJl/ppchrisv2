<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\DtrSubmission;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
use App\Models\SicRcAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OnFieldDtrService
{
    private const ALLOWED_PROOF_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    public function submit(SicRcAccount $account, array $data): DtrSubmission
    {
        return DB::transaction(function () use ($account, $data): DtrSubmission {
            $account = SicRcAccount::query()
                ->with(['employee.branch'])
                ->lockForUpdate()
                ->find($account->getKey());

            if (! $account || ! $account->is_active || $account->trashed()) {
                throw ValidationException::withMessages([
                    'employee_id' => 'This SIC/RC account is not active.',
                ]);
            }

            $employee = $account->employee;
            $branch = $employee?->branch;

            if (! $employee || $employee->trashed() || ! $branch || $branch->trashed()) {
                throw ValidationException::withMessages([
                    'employee_id' => 'A valid employee with an assigned branch must be bound to this SIC/RC account.',
                ]);
            }

            if (! in_array((int) $branch->getKey(), $account->assignedBranchIds(), true)) {
                throw ValidationException::withMessages([
                    'employee_id' => 'The bound employee\'s branch is not assigned to this SIC/RC account.',
                ]);
            }

            $period = PayrollPeriod::query()
                ->lockForUpdate()
                ->find((int) ($data['payroll_period_id'] ?? 0));

            if (! $period || $period->is_locked) {
                throw ValidationException::withMessages([
                    'payroll_period_id' => 'Select an open payroll period.',
                ]);
            }

            [$dateIn, $timeIn, $dateOut, $timeOut] = $this->validateAttendanceValues($period, $data);
            $proofPath = (string) ($data['proof_file'] ?? '');

            if (
                $proofPath === ''
                || ! str($proofPath)->startsWith('dtr-proof-submissions/')
                || ! Storage::disk('local')->exists($proofPath)
            ) {
                throw ValidationException::withMessages([
                    'proof_file' => 'Upload a valid proof file.',
                ]);
            }

            $mimeType = Storage::disk('local')->mimeType($proofPath) ?: null;

            if (! in_array($mimeType, self::ALLOWED_PROOF_MIME_TYPES, true)) {
                throw ValidationException::withMessages([
                    'proof_file' => 'The proof must be a PDF, PNG, or JPG file.',
                ]);
            }

            if (Storage::disk('local')->size($proofPath) > 20 * 1024 * 1024) {
                throw ValidationException::withMessages([
                    'proof_file' => 'The proof file must not exceed 20 MB.',
                ]);
            }

            $duplicateExists = DtrSubmission::query()
                ->where('submission_type', DtrSubmission::TYPE_PROOF)
                ->whereIn('status', [DtrSubmission::STATUS_PENDING, DtrSubmission::STATUS_APPROVED])
                ->where('employee_id', $employee->getKey())
                ->where('payroll_period_id', $period->getKey())
                ->whereDate('date_in', $dateIn)
                ->where('time_in', $timeIn)
                ->whereDate('date_out', $dateOut)
                ->where('time_out', $timeOut)
                ->lockForUpdate()
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'date_in' => 'An identical pending or approved On Field DTR request already exists.',
                ]);
            }

            $description = trim((string) ($data['description'] ?? ''));

            return DtrSubmission::query()->create([
                'sic_rc_account_id' => $account->getKey(),
                'employee_id' => $employee->getKey(),
                'employee_name_snapshot' => trim($employee->lastname.', '.$employee->firstname),
                'employee_company_id_snapshot' => $employee->company_id,
                'payroll_period_id' => $period->getKey(),
                'branch_id' => $branch->getKey(),
                'branch_name_snapshot' => $branch->branch_name,
                'date_in' => $dateIn,
                'time_in' => $timeIn,
                'date_out' => $dateOut,
                'time_out' => $timeOut,
                'file_path' => $proofPath,
                'file_name' => basename($proofPath),
                'file_size' => Storage::disk('local')->size($proofPath),
                'mime_type' => $mimeType,
                'file_hash' => hash_file('sha256', Storage::disk('local')->path($proofPath)),
                'description' => $description === '' ? null : $description,
                'comments' => $description === '' ? null : $description,
                'is_new' => true,
                'submission_type' => DtrSubmission::TYPE_PROOF,
                'status' => DtrSubmission::STATUS_PENDING,
            ]);
        }, 3);
    }

    public function approve(DtrSubmission $submission, User $reviewer, ?string $remarks = null): DtrSubmission
    {
        $this->assertHrReviewer($reviewer);

        return DB::transaction(function () use ($submission, $reviewer, $remarks): DtrSubmission {
            $submission = DtrSubmission::query()
                ->with(['sicRcAccount.employee.branch', 'payrollPeriod'])
                ->lockForUpdate()
                ->findOrFail($submission->getKey());

            $this->assertPendingProof($submission);

            $account = $submission->sicRcAccount;
            $employee = $account?->employee;
            $branch = $employee?->branch;
            $period = $submission->payrollPeriod;

            if (! $account || ! $account->is_active || $account->trashed()) {
                throw new \DomainException('The submitting SIC/RC account is no longer active.');
            }

            if (! $employee || $employee->trashed() || (int) $employee->getKey() !== (int) $submission->employee_id) {
                throw new \DomainException('The SIC/RC account employee binding changed. Review or reject this request instead.');
            }

            if (! $branch || (int) $branch->getKey() !== (int) $submission->branch_id) {
                throw new \DomainException('The bound employee branch changed after submission. Review or reject this request instead.');
            }

            if (! in_array((int) $branch->getKey(), $account->assignedBranchIds(), true)) {
                throw new \DomainException('The employee branch is no longer assigned to the submitting SIC/RC account.');
            }

            if (! $period || $period->is_locked) {
                throw new \DomainException('The selected payroll period is locked and can no longer receive D.T.R entries.');
            }

            $data = $this->buildDtrData($submission, $employee, $branch);
            $this->assertNoAttendanceConflict($submission, $employee, $data);

            $official = Dtr::query()->create($data);
            $visible = EmployeeVisibleDtr::query()->create([
                ...$data,
                'employee_id' => $employee->getKey(),
                'latest_source_row_hash' => $data['source_row_hash'],
                'latest_source_payload' => $this->sourcePayload($submission),
                'is_manually_edited' => false,
                'needs_review' => false,
            ]);

            $submission->forceFill([
                'status' => DtrSubmission::STATUS_APPROVED,
                'reviewed_by_user_id' => $reviewer->getKey(),
                'reviewer_remarks' => $this->normalizeRemarks($remarks),
                'reviewed_at' => now(),
                'generated_dtr_id' => $official->getKey(),
                'generated_visible_dtr_id' => $visible->getKey(),
                'is_new' => false,
                'viewed_at' => $submission->viewed_at ?? now(),
            ])->save();

            return $submission->refresh();
        }, 3);
    }

    public function reject(DtrSubmission $submission, User $reviewer, ?string $remarks = null): DtrSubmission
    {
        $this->assertHrReviewer($reviewer);

        return DB::transaction(function () use ($submission, $reviewer, $remarks): DtrSubmission {
            $submission = DtrSubmission::query()->lockForUpdate()->findOrFail($submission->getKey());
            $this->assertPendingProof($submission);

            $submission->forceFill([
                'status' => DtrSubmission::STATUS_REJECTED,
                'reviewed_by_user_id' => $reviewer->getKey(),
                'reviewer_remarks' => $this->normalizeRemarks($remarks),
                'reviewed_at' => now(),
                'is_new' => false,
                'viewed_at' => $submission->viewed_at ?? now(),
            ])->save();

            return $submission->refresh();
        }, 3);
    }

    public function deleteGeneratedDtr(Dtr $record, User $reviewer): void
    {
        $this->assertHrReviewer($reviewer);

        DB::transaction(function () use ($record, $reviewer): void {
            $submissionId = $record->on_field_dtr_submission_id;

            if (! $submissionId) {
                $record->forceDelete();

                return;
            }

            $submission = DtrSubmission::query()->lockForUpdate()->findOrFail($submissionId);
            $period = PayrollPeriod::query()->find($submission->payroll_period_id);

            if ($period?->is_locked || $record->is_locked) {
                throw new \DomainException('This D.T.R belongs to a locked payroll period.');
            }

            Dtr::withTrashed()
                ->where('on_field_dtr_submission_id', $submission->getKey())
                ->get()
                ->each->forceDelete();
            EmployeeVisibleDtr::withTrashed()
                ->where('on_field_dtr_submission_id', $submission->getKey())
                ->get()
                ->each->forceDelete();

            $submission->forceFill([
                'generated_dtr_deleted_at' => now(),
                'generated_dtr_deleted_by_user_id' => $reviewer->getKey(),
            ])->save();
        }, 3);
    }

    private function validateAttendanceValues(PayrollPeriod $period, array $data): array
    {
        foreach (['date_in', 'time_in', 'date_out', 'time_out'] as $field) {
            if (blank($data[$field] ?? null)) {
                throw ValidationException::withMessages([$field => str($field)->replace('_', ' ')->title().' is required.']);
            }
        }

        try {
            $dateIn = Carbon::parse($data['date_in'])->toDateString();
            $timeIn = Carbon::parse($data['time_in'])->format('H:i:s');
            $dateOut = Carbon::parse($data['date_out'])->toDateString();
            $timeOut = Carbon::parse($data['time_out'])->format('H:i:s');
            $actualIn = Carbon::parse("{$dateIn} {$timeIn}");
            $actualOut = Carbon::parse("{$dateOut} {$timeOut}");
        } catch (\Throwable) {
            throw ValidationException::withMessages(['date_in' => 'Enter valid D.T.R dates and times.']);
        }

        $periodStart = Carbon::parse($period->date_start)->startOfDay();
        $periodEnd = Carbon::parse($period->date_end)->endOfDay();

        if (! $actualIn->betweenIncluded($periodStart, $periodEnd)) {
            throw ValidationException::withMessages([
                'date_in' => 'Date In must be within the selected payroll period.',
            ]);
        }

        if ($actualOut->lessThanOrEqualTo($actualIn)) {
            throw ValidationException::withMessages([
                'date_out' => 'Date and Time Out must be later than Date and Time In.',
            ]);
        }

        if ($actualIn->diffInMinutes($actualOut) > 36 * 60) {
            throw ValidationException::withMessages([
                'date_out' => 'An On Field D.T.R entry cannot exceed 36 hours.',
            ]);
        }

        return [$dateIn, $timeIn, $dateOut, $timeOut];
    }

    private function buildDtrData(DtrSubmission $submission, Employee $employee, Branch $branch): array
    {
        $dateIn = Carbon::parse($submission->date_in)->toDateString();
        $dateOut = Carbon::parse($submission->date_out)->toDateString();
        $timeIn = Carbon::parse($submission->time_in)->format('H:i:s');
        $timeOut = Carbon::parse($submission->time_out)->format('H:i:s');
        $schedule = $this->resolveSchedule($employee, $branch, $dateIn, $timeIn);
        $dayPart = $schedule['day_part'];
        $scheduleStart = $schedule['start'];
        $scheduleEnd = $schedule['end'];

        if ($schedule['type'] === 'Regular') {
            $dayPart = app(DtrDayPartService::class)->classifyRegularPunch(
                dateIn: $dateIn,
                timeIn: $timeIn,
                dateOut: $dateOut,
                timeOut: $timeOut,
                scheduleStart: $scheduleStart,
                scheduleEnd: $scheduleEnd,
                scheduleType: $schedule['type'],
            );

            if ($dayPart !== DtrDayPartService::WHOLE_DAY) {
                [$scheduleStart, $scheduleEnd] = app(DtrDayPartService::class)
                    ->scheduleWindow($dateIn, $scheduleStart, $scheduleEnd, $dayPart);
            }
        }

        $calculation = $dayPart === DtrDayPartService::UNCLASSIFIED
            ? app(DtrCalculator::class)->emptyCalculationData()
            : app(DtrCalculator::class)->calculate(
                dateIn: $dateIn,
                timeIn: $timeIn,
                dateOut: $dateOut,
                timeOut: $timeOut,
                scheduleStart: $scheduleStart,
                scheduleEnd: $scheduleEnd,
                scheduleStartColumn: $schedule['column'],
                scheduleType: $schedule['type'],
                dayPart: $dayPart,
            );

        $sourcePayload = $this->sourcePayload($submission);
        $sourceHash = hash('sha256', json_encode($sourcePayload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        return [
            'on_field_dtr_submission_id' => $submission->getKey(),
            'payroll_period_id' => $submission->payroll_period_id,
            'branch_id' => $employee->branch_id,
            'fingerprint_id' => (string) ($employee->fingerprint_id ?: $employee->uid),
            'batch_id' => 'ONFIELD-'.$submission->publicKey(),
            'import_name' => 'On Field DTR',
            'source_session_id' => 'on-field:'.$submission->publicKey(),
            'source_filename' => $submission->file_name,
            'source_file_hash' => $submission->file_hash,
            'source_row_hash' => $sourceHash,
            'date_in' => $dateIn,
            'time_in' => $timeIn,
            'date_out' => $dateOut,
            'time_out' => $timeOut,
            'schedule_type' => $schedule['type'],
            'day_part' => $dayPart,
            'entry_source' => DtrDayPartService::SOURCE_ON_FIELD_DTR,
            'schedule_start' => $scheduleStart,
            'schedule_end' => $scheduleEnd,
            'absence_minutes' => 0,
            'daily_rate' => $employee->daily_rate,
            'comment' => $submission->description,
            'is_absent' => false,
            'is_imported' => false,
            'is_locked' => false,
            ...app(HolidayEntitlementService::class)->dtrHolidayData($employee, $dateIn, (int) $branch->getKey()),
            ...$calculation,
        ];
    }

    private function resolveSchedule(Employee $employee, Branch $branch, string $dateIn, string $timeIn): array
    {
        $monthlyRate = str($employee->rate_type ?? '')->lower()->contains('month');

        if ($monthlyRate && Carbon::parse($dateIn)->isSaturday()) {
            return [
                'type' => 'Saturday',
                'column' => 'reg_sched_start',
                'start' => '08:00:00',
                'end' => '11:00:00',
                'day_part' => DtrDayPartService::WHOLE_DAY,
            ];
        }

        $candidateColumns = [
            'reg_sched_start' => ['reg_sched_end', 'Regular', DtrDayPartService::WHOLE_DAY],
            'shift1_start' => ['shift1_end', 'Shift1', DtrDayPartService::WHOLE_DAY],
            'shift2_start' => ['shift2_end', 'Shift2', DtrDayPartService::WHOLE_DAY],
            'shift3_start' => ['shift3_end', 'Shift3', DtrDayPartService::WHOLE_DAY],
            'broken_shift1_start' => ['broken_shift1_end', 'Brkn1', DtrDayPartService::MORNING],
            'broken_shift2_start' => ['broken_shift2_end', 'Brkn2', DtrDayPartService::AFTERNOON],
        ];

        if ($monthlyRate && filled($branch->reg_sched_start) && filled($branch->reg_sched_end)) {
            $candidateColumns = ['reg_sched_start' => $candidateColumns['reg_sched_start']];
        }

        $actualIn = Carbon::parse("{$dateIn} {$timeIn}");
        $candidates = collect($candidateColumns)
            ->map(function (array $details, string $startColumn) use ($actualIn, $branch, $dateIn): ?array {
                [$endColumn, $type, $dayPart] = $details;

                if (blank($branch->{$startColumn}) || blank($branch->{$endColumn})) {
                    return null;
                }

                $start = Carbon::parse("{$dateIn} {$branch->{$startColumn}}");
                $distances = [
                    abs($actualIn->diffInMinutes($start, false)),
                    abs($actualIn->diffInMinutes($start->copy()->addDay(), false)),
                    abs($actualIn->diffInMinutes($start->copy()->subDay(), false)),
                ];

                return [
                    'type' => $type,
                    'column' => $startColumn,
                    'start' => Carbon::parse($branch->{$startColumn})->format('H:i:s'),
                    'end' => Carbon::parse($branch->{$endColumn})->format('H:i:s'),
                    'day_part' => $dayPart,
                    'distance' => min($distances),
                ];
            })
            ->filter()
            ->sortBy('distance');

        $schedule = $candidates->first();

        if (! $schedule) {
            throw new \DomainException('The employee branch does not have a complete schedule for this D.T.R entry.');
        }

        unset($schedule['distance']);

        return $schedule;
    }

    private function assertNoAttendanceConflict(DtrSubmission $submission, Employee $employee, array $data): void
    {
        $official = Dtr::query()
            ->where('payroll_period_id', $submission->payroll_period_id)
            ->where('branch_id', $employee->branch_id)
            ->where('fingerprint_id', (string) ($employee->fingerprint_id ?: $employee->uid))
            ->whereDate('date_in', $data['date_in'])
            ->get();
        $visible = EmployeeVisibleDtr::query()
            ->where('payroll_period_id', $submission->payroll_period_id)
            ->forEmployee($employee)
            ->whereDate('date_in', $data['date_in'])
            ->get();

        $conflict = $official
            ->concat($visible)
            ->unique(fn (Dtr $record): string => $record->getTable().':'.$record->getKey())
            ->contains(fn (Dtr $record): bool => $this->intervalsConflict($record, $data));

        if ($conflict) {
            throw new \DomainException('A conflicting D.T.R, leave, or absence entry already exists for this employee and time range.');
        }
    }

    private function intervalsConflict(Dtr $record, array $data): bool
    {
        if (blank($record->date_in) || blank($record->time_in) || blank($record->date_out) || blank($record->time_out)) {
            return true;
        }

        try {
            $existingStart = Carbon::parse($record->date_in.' '.$record->time_in);
            $existingEnd = Carbon::parse($record->date_out.' '.$record->time_out);
            $newStart = Carbon::parse($data['date_in'].' '.$data['time_in']);
            $newEnd = Carbon::parse($data['date_out'].' '.$data['time_out']);

            return $existingStart->lessThan($newEnd) && $newStart->lessThan($existingEnd);
        } catch (\Throwable) {
            return true;
        }
    }

    private function assertPendingProof(DtrSubmission $submission): void
    {
        if ($submission->submission_type !== DtrSubmission::TYPE_PROOF) {
            throw new \DomainException('This record is not an On Field D.T.R request.');
        }

        if (! $submission->isPending()) {
            throw new \DomainException('This On Field D.T.R request was already reviewed.');
        }

        if ($submission->generated_dtr_id || $submission->generated_visible_dtr_id) {
            throw new \DomainException('This request already has generated D.T.R records.');
        }
    }

    private function assertHrReviewer(User $reviewer): void
    {
        if (! in_array($reviewer->role, ['hr', 'admin'], true)) {
            throw new \DomainException('Only an authorized HR or admin account can review On Field D.T.R requests.');
        }
    }

    private function sourcePayload(DtrSubmission $submission): array
    {
        return [
            'submission_uuid' => $submission->publicKey(),
            'employee_id' => $submission->employee_id,
            'payroll_period_id' => $submission->payroll_period_id,
            'branch_id' => $submission->branch_id,
            'date_in' => optional($submission->date_in)->toDateString(),
            'time_in' => $submission->time_in,
            'date_out' => optional($submission->date_out)->toDateString(),
            'time_out' => $submission->time_out,
            'proof_hash' => $submission->file_hash,
        ];
    }

    private function normalizeRemarks(?string $remarks): ?string
    {
        $remarks = trim((string) $remarks);

        return $remarks === '' ? null : $remarks;
    }
}
