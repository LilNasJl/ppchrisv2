<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\DtrSubmission;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
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

    public function submit(Employee $employee, array $data): DtrSubmission
    {
        return DB::transaction(function () use ($employee, $data): DtrSubmission {
            $employee = Employee::query()
                ->with(['branch'])
                ->lockForUpdate()
                ->find($employee->getKey());

            if (! $employee || $employee->trashed()) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Employee is not active or available.',
                ]);
            }

            $branch = $employee->branch;

            if (! $branch || $branch->trashed()) {
                throw ValidationException::withMessages([
                    'employee_id' => 'The employee must have an assigned branch to submit On Field DTR.',
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

            $normalizedEntries = $this->normalizeAndValidateSubmissionEntries($period, $employee, $branch, $data);
            $firstEntry = $normalizedEntries[0];

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

            $existingSubmissions = DtrSubmission::query()
                ->where('submission_type', DtrSubmission::TYPE_PROOF)
                ->whereIn('status', [DtrSubmission::STATUS_PENDING, DtrSubmission::STATUS_APPROVED])
                ->where('employee_id', $employee->getKey())
                ->where('payroll_period_id', $period->getKey())
                ->lockForUpdate()
                ->get();

            foreach ($normalizedEntries as $entry) {
                $duplicateExists = $existingSubmissions->contains(function (DtrSubmission $existing) use ($entry): bool {
                    foreach ($existing->normalizedEntries() as $existingEntry) {
                        if (
                            $existingEntry['date_in'] === $entry['date_in']
                            && $existingEntry['time_in'] === $entry['time_in']
                            && $existingEntry['date_out'] === $entry['date_out']
                            && $existingEntry['time_out'] === $entry['time_out']
                        ) {
                            return true;
                        }
                    }

                    return false;
                });

                if ($duplicateExists) {
                    throw ValidationException::withMessages([
                        'date_in' => 'An identical pending or approved On Field DTR request already exists.',
                    ]);
                }
            }

            $description = trim((string) ($data['description'] ?? ''));

            $attributes = [
                'submitted_by_employee_id' => $employee->getKey(),
                'employee_id' => $employee->getKey(),
                'employee_name_snapshot' => trim($employee->lastname.', '.$employee->firstname),
                'employee_company_id_snapshot' => $employee->company_id,
                'payroll_period_id' => $period->getKey(),
                'branch_id' => $branch->getKey(),
                'branch_name_snapshot' => $branch->branch_name,
                'date_in' => $firstEntry['date_in'],
                'time_in' => $firstEntry['time_in'],
                'date_out' => $firstEntry['date_out'],
                'time_out' => $firstEntry['time_out'],
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
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('dtr_submissions', 'schedule_type')) {
                $attributes['schedule_type'] = $firstEntry['schedule_type'];
                $attributes['schedule_start'] = $firstEntry['schedule_start'];
                $attributes['schedule_end'] = $firstEntry['schedule_end'];
                $attributes['entries'] = $normalizedEntries;
            }

            return DtrSubmission::query()->create($attributes);
        }, 3);
    }

    public function approve(DtrSubmission $submission, User $reviewer, ?string $remarks = null): DtrSubmission
    {
        $this->assertHrReviewer($reviewer);

        return DB::transaction(function () use ($submission, $reviewer, $remarks): DtrSubmission {
            $submission = DtrSubmission::query()
                ->with(['submittedByEmployee.branch', 'employee.branch', 'payrollPeriod'])
                ->lockForUpdate()
                ->findOrFail($submission->getKey());

            $this->assertPendingProof($submission);

            $submitter = $submission->submittedByEmployee;
            $employee = $submission->employee;
            $branch = $employee?->branch;
            $period = $submission->payrollPeriod;

            if (! $submitter || $submitter->trashed()) {
                throw new \DomainException('The submitting employee is no longer active.');
            }

            if (! $employee || $employee->trashed() || (int) $employee->getKey() !== (int) $submission->employee_id) {
                throw new \DomainException('The employee binding changed. Review or reject this request instead.');
            }

            if (! $branch || (int) $branch->getKey() !== (int) $submission->branch_id) {
                throw new \DomainException('The bound employee branch changed after submission. Review or reject this request instead.');
            }

            if (! $period || $period->is_locked) {
                throw new \DomainException('The selected payroll period is locked and can no longer receive D.T.R entries.');
            }

            $entries = $submission->normalizedEntries();
            $builtEntries = [];

            foreach ($entries as $index => $entry) {
                $data = $this->buildDtrData($submission, $employee, $branch, $entry, $index);
                $this->assertNoAttendanceConflict($submission, $employee, $data);
                $builtEntries[] = [
                    'data' => $data,
                    'entry' => $entry,
                    'index' => $index,
                ];
            }

            $firstOfficial = null;
            $firstVisible = null;
            $hasDtrSourceId = \Illuminate\Support\Facades\Schema::hasColumn('employee_visible_dtrs', 'dtr_source_id');

            foreach ($builtEntries as $item) {
                $data = $item['data'];
                $official = Dtr::query()->create($data);

                $visibleAttributes = [
                    ...$data,
                    'employee_id' => $employee->getKey(),
                    'latest_source_row_hash' => $data['source_row_hash'],
                    'latest_source_payload' => $this->sourcePayload($submission, $item['entry'], $item['index']),
                    'is_manually_edited' => false,
                    'needs_review' => false,
                ];

                if ($hasDtrSourceId) {
                    $visibleAttributes['dtr_source_id'] = $official->getKey();
                }

                $visible = EmployeeVisibleDtr::query()->create($visibleAttributes);

                $firstOfficial ??= $official;
                $firstVisible ??= $visible;
            }

            $submission->forceFill([
                'status' => DtrSubmission::STATUS_APPROVED,
                'reviewed_by_user_id' => $reviewer->getKey(),
                'reviewer_remarks' => $this->normalizeRemarks($remarks),
                'reviewed_at' => now(),
                'generated_dtr_id' => $firstOfficial?->getKey(),
                'generated_visible_dtr_id' => $firstVisible?->getKey(),
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

    /**
     * @return array<int, array{date_in: string, time_in: string, date_out: string, time_out: string, schedule_type: ?string, schedule_start: ?string, schedule_end: ?string}>
     */
    private function normalizeAndValidateSubmissionEntries(
        PayrollPeriod $period,
        Employee $employee,
        Branch $branch,
        array $data,
    ): array {
        $hasEntriesArray = array_key_exists('entries', $data);
        $rawEntries = $hasEntriesArray ? $data['entries'] : [$data];

        if (! is_array($rawEntries) || count($rawEntries) === 0) {
            throw ValidationException::withMessages([
                'entries' => 'At least one D.T.R entry is required.',
            ]);
        }

        $normalized = [];

        foreach (array_values($rawEntries) as $index => $entry) {
            if (! is_array($entry)) {
                throw ValidationException::withMessages([
                    'entries' => 'Invalid D.T.R entry format.',
                ]);
            }

            [$dateIn, $timeIn, $dateOut, $timeOut] = $this->validateAttendanceValues($period, $entry);

            $rawSchedType = trim((string) ($entry['schedule_type'] ?? ''));
            $rawSchedStart = trim((string) ($entry['schedule_start'] ?? ''));
            $rawSchedEnd = trim((string) ($entry['schedule_end'] ?? ''));

            if ($hasEntriesArray || $rawSchedType !== '' || $rawSchedStart !== '' || $rawSchedEnd !== '') {
                if ($rawSchedType === '') {
                    throw ValidationException::withMessages([
                        'schedule_type' => 'Sched Type is required for each D.T.R entry.',
                    ]);
                }

                if ($rawSchedStart === '') {
                    throw ValidationException::withMessages([
                        'schedule_start' => 'Sched Start is required for each D.T.R entry.',
                    ]);
                }

                if ($rawSchedEnd === '') {
                    throw ValidationException::withMessages([
                        'schedule_end' => 'Sched End is required for each D.T.R entry.',
                    ]);
                }

                try {
                    $scheduleStart = Carbon::parse($rawSchedStart)->format('H:i:s');
                    $scheduleEnd = Carbon::parse($rawSchedEnd)->format('H:i:s');
                } catch (\Throwable) {
                    throw ValidationException::withMessages([
                        'schedule_start' => 'Enter valid Sched Start and Sched End times.',
                    ]);
                }

                $scheduleType = $this->normalizeScheduleTypeLabel($rawSchedType);
            } else {
                $scheduleType = null;
                $scheduleStart = null;
                $scheduleEnd = null;

                try {
                    $resolved = $this->resolveSchedule($employee, $branch, $dateIn, $timeIn);
                    $scheduleType = $resolved['type'];
                    $scheduleStart = $resolved['start'];
                    $scheduleEnd = $resolved['end'];
                } catch (\Throwable) {
                    // Leave nullable until HR approval resolves or rejects
                }
            }

            $normalized[] = [
                'date_in' => $dateIn,
                'time_in' => $timeIn,
                'date_out' => $dateOut,
                'time_out' => $timeOut,
                'schedule_type' => $scheduleType,
                'schedule_start' => $scheduleStart,
                'schedule_end' => $scheduleEnd,
            ];
        }

        // Prevent overlapping intervals within the same submission
        $count = count($normalized);
        for ($i = 0; $i < $count; $i++) {
            $startA = Carbon::parse($normalized[$i]['date_in'].' '.$normalized[$i]['time_in']);
            $endA = Carbon::parse($normalized[$i]['date_out'].' '.$normalized[$i]['time_out']);

            for ($j = $i + 1; $j < $count; $j++) {
                $startB = Carbon::parse($normalized[$j]['date_in'].' '.$normalized[$j]['time_in']);
                $endB = Carbon::parse($normalized[$j]['date_out'].' '.$normalized[$j]['time_out']);

                if ($startA->lessThan($endB) && $startB->lessThan($endA)) {
                    throw ValidationException::withMessages([
                        'entries' => 'Submitted D.T.R entries cannot have overlapping date and time ranges.',
                    ]);
                }
            }
        }

        return $normalized;
    }

    private function normalizeScheduleTypeLabel(string $raw): string
    {
        return match (strtolower(trim($raw))) {
            'regular', 'regular work' => 'Regular',
            'flexible', 'flex' => 'Flexible',
            'shift1', 'shift 1', 'shift_1', 'shift-based' => 'Shift1',
            'shift2', 'shift 2', 'shift_2' => 'Shift2',
            'shift3', 'shift 3', 'shift_3' => 'Shift3',
            'broken1', 'broken shift 1', 'broken_1', 'brkn1' => 'Brkn1',
            'broken2', 'broken shift 2', 'broken_2', 'brkn2' => 'Brkn2',
            'broken3', 'broken shift 3', 'broken_3', 'brkn3' => 'Brkn3',
            'saturday', 'regular saturday' => 'Saturday',
            'overtime', 'overtime only' => 'Overtime',
            default => trim($raw),
        };
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

    private function buildDtrData(
        DtrSubmission $submission,
        Employee $employee,
        Branch $branch,
        ?array $entry = null,
        int $entryIndex = 0,
    ): array {
        $dateIn = Carbon::parse($entry['date_in'] ?? $submission->date_in)->toDateString();
        $dateOut = Carbon::parse($entry['date_out'] ?? $submission->date_out)->toDateString();
        $timeIn = Carbon::parse($entry['time_in'] ?? $submission->time_in)->format('H:i:s');
        $timeOut = Carbon::parse($entry['time_out'] ?? $submission->time_out)->format('H:i:s');

        $explicitType = $entry['schedule_type'] ?? $submission->schedule_type ?? null;
        $explicitStart = $entry['schedule_start'] ?? $submission->schedule_start ?? null;
        $explicitEnd = $entry['schedule_end'] ?? $submission->schedule_end ?? null;

        if (filled($explicitType) && filled($explicitStart) && filled($explicitEnd)) {
            $normalizedType = $this->normalizeScheduleTypeLabel((string) $explicitType);
            $schedule = [
                'type' => $normalizedType,
                'column' => match ($normalizedType) {
                    'Shift1' => 'shift1_start',
                    'Shift2' => 'shift2_start',
                    'Shift3' => 'shift3_start',
                    'Brkn1' => 'broken_shift1_start',
                    'Brkn2' => 'broken_shift2_start',
                    'Brkn3' => 'broken_shift3_start',
                    default => 'reg_sched_start',
                },
                'start' => Carbon::parse((string) $explicitStart)->format('H:i:s'),
                'end' => Carbon::parse((string) $explicitEnd)->format('H:i:s'),
                'day_part' => match ($normalizedType) {
                    'Brkn1' => DtrDayPartService::MORNING,
                    'Brkn2' => DtrDayPartService::AFTERNOON,
                    default => DtrDayPartService::WHOLE_DAY,
                },
            ];
        } else {
            $schedule = $this->resolveSchedule($employee, $branch, $dateIn, $timeIn);
        }

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
                overtimeOnly: $schedule['type'] === 'Overtime',
                dayPart: $dayPart,
            );

        $sourcePayload = $this->sourcePayload($submission, $entry, $entryIndex);
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

    private function sourcePayload(DtrSubmission $submission, ?array $entry = null, int $entryIndex = 0): array
    {
        return [
            'submission_uuid' => $submission->publicKey(),
            'entry_index' => $entryIndex,
            'employee_id' => $submission->employee_id,
            'payroll_period_id' => $submission->payroll_period_id,
            'branch_id' => $submission->branch_id,
            'date_in' => $entry['date_in'] ?? optional($submission->date_in)->toDateString(),
            'time_in' => $entry['time_in'] ?? $submission->time_in,
            'date_out' => $entry['date_out'] ?? optional($submission->date_out)->toDateString(),
            'time_out' => $entry['time_out'] ?? $submission->time_out,
            'schedule_type' => $entry['schedule_type'] ?? $submission->schedule_type,
            'schedule_start' => $entry['schedule_start'] ?? $submission->schedule_start,
            'schedule_end' => $entry['schedule_end'] ?? $submission->schedule_end,
            'proof_hash' => $submission->file_hash,
        ];
    }

    private function normalizeRemarks(?string $remarks): ?string
    {
        $remarks = trim((string) $remarks);

        return $remarks === '' ? null : $remarks;
    }
}
