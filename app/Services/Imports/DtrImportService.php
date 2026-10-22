<?php

namespace App\Services\Imports;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\AutomaticAbsenceService;
use App\Services\DtrCalculator;
use App\Services\DtrDailyAggregationService;
use App\Services\DtrDayPartService;
use App\Services\DtrEntryConflictService;
use App\Services\DtrOvertimeTransferService;
use App\Services\DtrSynchronizationService;
use App\Services\HolidayEntitlementService;
use App\Services\SaturdayWfhDtrService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DtrImportService
{
    protected ?Collection $activeImportEmployees = null;

    protected function activeImportEmployees(): Collection
    {
        return $this->activeImportEmployees ??= Employee::query()->activeEmployment()->get();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{total:int, successful:int, failed:int, skipped:int, already_existing:int, invalid:int, batch_id:string, message:string, summary:array<string, int>, errors:array<int, array<string, mixed>>}
     */
    public function importRows(array $rows, string $importName, ?string $fallbackBatchId = null): array
    {
        $fallbackBatchId = $this->normalizeNullableString($fallbackBatchId) ?: Str::random(12);

        $result = [
            'total' => count($rows),
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'skipped_rows' => [],
            'already_existing' => 0,
            'conflicts' => 0,
            'invalid' => 0,
            'batch_id' => $fallbackBatchId,
            'message' => 'No rows were imported.',
            'summary' => [
                'total_records_processed' => count($rows),
                'newly_imported_records' => 0,
                'skipped_already_existing_records' => 0,
                'invalid_or_failed_records' => 0,
            ],
            'errors' => [],
            'notices' => [],
        ];

        $validatedRows = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;

            try {
                $validatedRows[] = [
                    'row' => $rowNumber,
                    'data' => $this->validateRow(is_array($row) ? $row : [], $fallbackBatchId),
                ];
            } catch (ValidationException $exception) {
                $rowErrors = collect($exception->errors())->flatten()->all();

                $result['failed']++;
                $result['invalid']++;

                foreach ($rowErrors ?: ['Unable to validate D.T.R row.'] as $message) {
                    $result['errors'][] = [
                        'row' => $rowNumber,
                        'message' => $message,
                    ];
                }
            } catch (\Throwable $exception) {
                report($exception);

                $result['failed']++;
                $result['invalid']++;
                $result['errors'][] = [
                    'row' => $rowNumber,
                    'message' => $exception->getMessage() ?: 'Unable to validate D.T.R row.',
                ];
            }
        }

        if ($result['errors'] !== []) {
            $result['message'] = 'Import cancelled. No D.T.R records were saved. Fix the listed rows and import again.';

            return $this->withSummary($result);
        }

        if ($validatedRows !== []) {
            $result['batch_id'] = $validatedRows[0]['data']['batch_id'];
            usort($validatedRows, fn (array $a, array $b): int => (int) $this->isAbsenceRow($a['data']) <=> (int) $this->isAbsenceRow($b['data']));
        }

        return $this->withImportLock($validatedRows, $fallbackBatchId, function () use ($rows, $validatedRows, $importName, &$result): array {
            $pendingRecords = collect();
            $rowsToImport = [];
            $seenSourceRowHashes = [];
            $sourceRowHashes = array_map(
                fn (array $validatedRow): string => $this->buildSourceRowHash($validatedRow['data']),
                $validatedRows,
            );
            $existingSourceRowHashes = $this->getExistingSourceRowHashes($sourceRowHashes);

            foreach ($validatedRows as $index => $validatedRow) {
                $data = $validatedRow['data'];
                $sourceRowHash = $sourceRowHashes[$index];

                if (
                    filled($sourceRowHash)
                    && (
                        isset($seenSourceRowHashes[$sourceRowHash])
                        || isset($existingSourceRowHashes[$sourceRowHash])
                    )
                ) {
                    $result['skipped']++;
                    $result['already_existing']++;
                    $result['skipped_rows'][] = $validatedRow['row'];
                    $reason = isset($seenSourceRowHashes[$sourceRowHash])
                        ? 'Duplicate DTR session in this export; this row was skipped.'
                        : 'This DTR session was already imported; this row was skipped.';
                    $result['notices'][] = ['row' => $validatedRow['row'], 'message' => $reason];

                    continue;
                }

                $metadata = $this->getImportMetadata($data, $importName);

                if ($this->hasExistingOrConflictingDatabaseRecord($data, $metadata)) {
                    $result['skipped']++;
                    $result['already_existing']++;
                    $result['conflicts']++;
                    $result['skipped_rows'][] = $validatedRow['row'];
                    $result['notices'][] = ['row' => $validatedRow['row'], 'message' => 'An existing DTR conflicts with this session. It was preserved; review incomplete or corrected entries before importing again.'];

                    if (filled($sourceRowHash)) {
                        $seenSourceRowHashes[$sourceRowHash] = true;
                    }

                    continue;
                }

                if ($this->hasBatchConflict(
                    $data,
                    $metadata['day_part'] ?? DtrDayPartService::WHOLE_DAY,
                    $pendingRecords,
                )) {
                    $result['failed']++;
                    $result['invalid']++;
                    $result['errors'][] = [
                        'row' => $validatedRow['row'],
                        'message' => 'A conflicting D.T.R, leave, or absence entry overlaps this punch interval, or an earlier punch remains open.',
                    ];
                }

                $pendingRecords->push($this->makePendingRecord($data, $metadata));
                $rowsToImport[] = $validatedRow;

                if (filled($sourceRowHash)) {
                    $seenSourceRowHashes[$sourceRowHash] = true;
                }
            }

            if ($result['errors'] !== []) {
                $result['message'] = 'Import cancelled. No D.T.R records were saved. Fix the listed rows and import again.';

                return $this->withSummary($result);
            }

            if ($rowsToImport === []) {
                $result['wfh_created'] = app(SaturdayWfhDtrService::class)->materializeForValidatedRows($validatedRows);
                $result['message'] = $result['wfh_created'] > 0
                    ? "No new device rows were imported. {$result['wfh_created']} Saturday WFH D.T.R entries were created."
                    : 'No new D.T.R records were imported. Every decoded row was already imported.';

                return $this->withSummary($result);
            }

            try {
                DB::transaction(function () use ($rowsToImport, $validatedRows, $importName, &$result): void {
                    $dailyAggregation = app(DtrDailyAggregationService::class);
                    $savedIds = [];
                    $importedDataRows = [];

                    $dailyAggregation->withoutAutomaticRecalculation(function () use ($rowsToImport, $importName, &$result, &$savedIds, &$importedDataRows): void {
                        foreach ($rowsToImport as $validatedRow) {
                            $saved = $this->saveValidatedRow($validatedRow['data'], $importName);

                            if (! empty($saved['skipped'])) {
                                $result['skipped']++;
                                $result['already_existing']++;
                                $result['skipped_rows'][] = $validatedRow['row'];
                                $result['notices'][] = [
                                    'row' => $validatedRow['row'],
                                    'message' => $saved['reason'] ?? 'An existing DTR was preserved; this row was skipped to prevent a duplicate.',
                                ];

                                continue;
                            }

                            $result['successful']++;
                            $savedIds[] = $saved['id'];
                            $importedDataRows[] = $validatedRow['data'];
                        }
                    });

                    if ($importedDataRows !== []) {
                        $dailyAggregation->recalculateRows($importedDataRows);
                    }

                    if ($savedIds !== [] && Schema::hasTable('employee_visible_dtrs')) {
                        $officialRecords = Dtr::query()->whereIn('id', $savedIds)->get();
                        app(DtrSynchronizationService::class)->syncOfficialBatchToVisible($officialRecords);
                    }

                    $result['wfh_created'] = app(SaturdayWfhDtrService::class)->materializeForValidatedRows($validatedRows);
                });

                $result['message'] = $result['skipped'] > 0
                    ? "D.T.R import completed. {$result['successful']} imported and {$result['skipped']} existing or excluded row(s) skipped."
                    : 'D.T.R import completed successfully.';
            } catch (\Throwable $exception) {
                report($exception);

                $result['successful'] = 0;
                $result['failed'] = count($rows) - $result['skipped'];
                $result['message'] = 'Import failed. No D.T.R records were saved.';
                $result['errors'][] = [
                    'row' => 0,
                    'message' => $exception->getMessage() ?: 'Unable to save D.T.R import.',
                ];
            }

            return $this->withSummary($result);
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{id:int, batch_id:?string}
     */
    public function importRow(array $row, string $importName, string $fallbackBatchId): array
    {
        $data = $this->validateRow($row, $fallbackBatchId);

        return DB::transaction(function () use ($data, $importName): array {
            $dailyAggregation = app(DtrDailyAggregationService::class);
            $result = $dailyAggregation->withoutAutomaticRecalculation(
                fn (): array => $this->saveValidatedRow($data, $importName),
            );

            $dailyAggregation->recalculateRows([$data]);

            if (! empty($result['id']) && Schema::hasTable('employee_visible_dtrs')) {
                $official = Dtr::query()->find($result['id']);

                if ($official) {
                    app(DtrSynchronizationService::class)->syncOfficialToVisible($official);
                }
            }

            app(SaturdayWfhDtrService::class)->materializeScope((int) $data['payroll_period_id'], (int) $data['branch_id']);

            return $result;
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function validateRow(array $row, string $fallbackBatchId): array
    {
        $isAbsence = $this->isAbsenceRow($row);
        $isIncompletePunch = $this->isIncompletePunchRow($row);
        $requiredColumnErrors = $this->getRequiredColumnErrors($row);
        $data = $this->normalizeRow($row, $fallbackBatchId);

        if ($isIncompletePunch) {
            $data['date_out'] = null;
            $data['time_out'] = null;
            $data['schedule_type'] = 'Forgot to Punch';
            $data['schedule_start'] = null;
            $data['schedule_end'] = null;
        }

        $isForgotToPunch = $this->resolveScheduleType($data) === 'Forgot to Punch';

        if ($requiredColumnErrors !== []) {
            throw ValidationException::withMessages([
                'required_columns' => $requiredColumnErrors,
            ]);
        }

        $validator = Validator::make($data, [
            'batch_id' => ['required', 'string', 'max:191'],
            'payroll_period_id' => ['required', 'integer'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'fingerprint_id' => ['required', 'integer'],
            'date_in' => ['required', 'date'],
            'time_in' => [$isAbsence ? 'nullable' : 'required', 'date_format:H:i:s'],
            'date_out' => [$isForgotToPunch || $isAbsence ? 'nullable' : 'required', 'date'],
            'time_out' => [$isForgotToPunch || $isAbsence ? 'nullable' : 'required', 'date_format:H:i:s'],
            'schedule_type' => ['required', 'string', 'max:191'],
            'schedule_start' => [$isForgotToPunch ? 'nullable' : 'required', 'date_format:H:i:s'],
            'schedule_end' => [$isForgotToPunch ? 'nullable' : 'required', 'date_format:H:i:s'],
            'day_part' => ['nullable', 'string', 'max:32'],
            'source_session_id' => ['nullable', 'string', 'max:191'],
            'source_filename' => ['nullable', 'string', 'max:191'],
            'source_file_hash' => ['nullable', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/i'],
        ], [
            'batch_id.required' => 'Batch ID is required.',
            'payroll_period_id.required' => 'Period ID is required.',
            'branch_id.exists' => 'Branch ID does not exist in the system.',
            'fingerprint_id.required' => 'Fingerprint ID is required.',
            'date_in.required' => 'Date In is required or has an invalid date format.',
            'date_out.required' => 'Date Out is required or has an invalid date format.',
            'time_in.required' => 'Time In is required or has an invalid time format.',
            'time_out.required' => 'Time Out is required or has an invalid time format.',
            'schedule_type.required' => 'Schedule Type is required.',
            'schedule_start.required' => 'Schedule Start is required or has an invalid time format.',
            'schedule_end.required' => 'Schedule End is required or has an invalid time format.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $data['source_branch_id'] = $data['branch_id'];
        $data['branch_id'] = $this->getImportedBranchId($data);

        if (blank($data['branch_id'])) {
            throw ValidationException::withMessages([
                'fingerprint_id' => 'This fingerprint ID does not match an active employee with a branch. A legacy file must provide a valid Branch ID.',
            ]);
        }

        if ($isAbsence) {
            if (! $data['absence_candidate'] || $data['entry_source'] !== AutomaticAbsenceService::SOURCE
                || ! app(DtrDayPartService::class)->isRegularScheduleType($data['regular_schedule'])
                || ! in_array($data['day_part'], ['morning', 'afternoon', 'whole_day'], true)
                || filled($data['time_in']) || filled($data['time_out'])
                || (filled($data['date_out']) && $data['date_out'] !== $data['date_in'])
                || $data['schedule_start'] >= $data['schedule_end']) {
                throw ValidationException::withMessages(['absence' => 'Absent rows require the explicit regular-schedule candidate marker, valid workday window, and empty punch times.']);
            }
            $data['date_out'] = $data['date_in'];
        }

        $payrollPeriod = PayrollPeriod::query()->find($data['payroll_period_id']);

        if (! $payrollPeriod) {
            throw ValidationException::withMessages([
                'payroll_period_id' => 'Period ID does not exist in the system.',
            ]);
        }

        if ($payrollPeriod->is_locked) {
            throw ValidationException::withMessages([
                'payroll_period_id' => 'The selected payroll period is locked and cannot accept imported D.T.R records.',
            ]);
        }

        $dateIn = Carbon::parse($data['date_in'])->startOfDay();

        if (! $dateIn->betweenIncluded($payrollPeriod->date_start, $payrollPeriod->date_end)) {
            throw ValidationException::withMessages([
                'date_in' => sprintf(
                    'Date In must be within the selected payroll period (%s to %s).',
                    $payrollPeriod->date_start->format('M d, Y'),
                    $payrollPeriod->date_end->format('M d, Y'),
                ),
            ]);
        }

        try {
            $this->getImportedCalculationData($data);
        } catch (\DomainException $exception) {
            throw ValidationException::withMessages([
                'overtime_transfer' => $exception->getMessage(),
            ]);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{id:int, batch_id:?string, skipped?:bool}
     */
    protected function saveValidatedRow(array $data, string $importName): array
    {
        $this->lockImportPeriod($data);
        if ($this->isAbsenceRow($data)) {
            [$data, $reason] = app(AutomaticAbsenceService::class)->assessCandidate($data, $this->getAbsenceEmployee($data));
            if ($reason) {
                return ['id' => 0, 'batch_id' => $data['batch_id'], 'skipped' => true, 'reason' => $reason];
            }
        }
        $metadata = $this->getImportMetadata($data, $importName);

        if ($this->hasExistingOrConflictingDatabaseRecord($data, $metadata)) {
            return [
                'id' => 0,
                'batch_id' => $data['batch_id'] ?? null,
                'skipped' => true,
            ];
        }

        if ($this->replacesSaturdayWfh($data)) {
            app(SaturdayWfhDtrService::class)->supersedeForActualImport($data);
        }

        $record = new Dtr;

        $record->forceFill($this->filterAttributesForTable('dtrs', [
            'batch_id' => $data['batch_id'],
            'payroll_period_id' => $data['payroll_period_id'],
            'branch_id' => $data['branch_id'],
            'fingerprint_id' => $data['fingerprint_id'],
            'date_in' => $data['date_in'],
            'time_in' => $data['time_in'],
            'date_out' => $data['date_out'],
            'time_out' => $data['time_out'],
            'schedule_type' => $data['schedule_type'],
            'schedule_start' => $data['schedule_start'],
            'schedule_end' => $data['schedule_end'],
            ...$metadata,
        ]))->save();

        app(AutomaticAbsenceService::class)->supersede($record);

        return [
            'id' => $record->id,
            'batch_id' => $record->batch_id,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function filterAttributesForTable(string $table, array $attributes): array
    {
        static $tableColumns = [];

        if (! isset($tableColumns[$table])) {
            $tableColumns[$table] = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        }

        if ($tableColumns[$table] === []) {
            return $attributes;
        }

        return array_intersect_key($attributes, array_flip($tableColumns[$table]));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, string>
     */
    protected function getRequiredColumnErrors(array $row): array
    {
        $isIncompletePunch = $this->isIncompletePunchRow($row);
        $scheduleType = $this->normalizeScheduleType(
            $this->normalizeNullableString(
                $this->pick($row, ['schedule_type', 'schedule type', 'sched', 'schedule']),
            ),
        );

        $requiredColumns = [
            'Batch ID' => ['batch_id', 'batch id', 'batch'],
            'Period ID' => ['payroll_period_id', 'period id', 'period_id', 'payroll period id'],
            'Fingerprint ID' => ['fingerprint_id', 'fingerprint id', 'uid', 'employee_id', 'employee_uid', 'user id'],
            'Date In' => ['date_in', 'date in'],
            'Time In' => ['time_in', 'time in'],
            'Schedule Type' => ['schedule_type', 'schedule type', 'sched', 'schedule'],
        ];

        if ($this->isAbsenceRow($row)) {
            unset($requiredColumns['Time In']);
            $requiredColumns += ['Schedule Start' => ['schedule_start', 'schedule start', 'sched_start'],
                'Schedule End' => ['schedule_end', 'schedule end', 'sched_end']];
        }

        if (! $this->isAbsenceRow($row) && ! $isIncompletePunch && $scheduleType !== 'Forgot to Punch') {
            $requiredColumns += [
                'Date Out' => ['date_out', 'date out'],
                'Time Out' => ['time_out', 'time out'],
                'Schedule Start' => ['schedule_start', 'schedule start', 'sched_start'],
                'Schedule End' => ['schedule_end', 'schedule end', 'sched_end'],
            ];
        }

        $errors = [];

        foreach ($requiredColumns as $label => $aliases) {
            if (blank($this->cleanSpreadsheetValue($this->pick($row, $aliases)))) {
                $errors[] = "{$label} is required.";
            }
        }

        return $errors;
    }

    /**
     * A punch is incomplete when either side of its out timestamp is absent.
     * Non-empty malformed values still reach the normal date/time validator.
     *
     * @param  array<string, mixed>  $row
     */
    protected function isIncompletePunchRow(array $row): bool
    {
        if ($this->isAbsenceRow($row)) {
            return false;
        }
        $dateOut = $this->cleanSpreadsheetValue($this->pick($row, ['date_out', 'date out']));
        $timeOut = $this->cleanSpreadsheetValue($this->pick($row, ['time_out', 'time out']));

        return blank($dateOut) || blank($timeOut);
    }

    protected function isAbsenceRow(array $row): bool
    {
        return strcasecmp(trim((string) $this->pick($row, ['schedule_type', 'schedule type', 'sched', 'schedule'])), 'Absent') === 0;
    }

    protected function lockImportPeriod(array $data): void
    {
        $period = PayrollPeriod::query()->lockForUpdate()->find($data['payroll_period_id']);
        if (! $period || $period->is_locked) {
            throw new \DomainException('The payroll period is locked or unavailable.');
        }
    }

    protected function getAbsenceEmployee(array $data): ?Employee
    {
        return $this->getEmployee($data);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeRow(array $row, string $fallbackBatchId): array
    {
        return [
            'batch_id' => $this->normalizeNullableString($this->pick($row, ['batch_id', 'batch id', 'batch'])) ?: $fallbackBatchId,
            'payroll_period_id' => $this->parseInteger($this->pick($row, ['payroll_period_id', 'period id', 'period_id', 'payroll period id'])),
            'branch_id' => $this->parseInteger($this->pick($row, ['branch_id', 'branch id'])),
            'fingerprint_id' => $this->parseInteger($this->pick($row, ['fingerprint_id', 'fingerprint id', 'uid', 'employee_id', 'employee_uid', 'user id'])),
            'date_in' => $this->parseDate($this->pick($row, ['date_in', 'date in'])),
            'time_in' => $this->parseTime($this->pick($row, ['time_in', 'time in'])),
            'date_out' => $this->parseDate($this->pick($row, ['date_out', 'date out'])),
            'time_out' => $this->parseTime($this->pick($row, ['time_out', 'time out'])),
            'schedule_type' => $this->normalizeNullableString($this->pick($row, ['schedule_type', 'schedule type', 'sched', 'schedule'])),
            'schedule_start' => $this->parseTime($this->pick($row, ['schedule_start', 'schedule start', 'sched_start'])),
            'schedule_end' => $this->parseTime($this->pick($row, ['schedule_end', 'schedule end', 'sched_end'])),
            'day_part' => $this->normalizeNullableString($this->pick($row, ['day_part', 'day part'])),
            'entry_source' => $this->normalizeNullableString($this->pick($row, ['entry_source', 'entry source'])),
            'absence_candidate' => filter_var($this->pick($row, ['absence_candidate', 'absence candidate']), FILTER_VALIDATE_BOOLEAN),
            'regular_schedule' => $this->normalizeNullableString($this->pick($row, ['regular_schedule', 'regular schedule'])),
            'absence_split' => $this->parseTime($this->pick($row, ['absence_split', 'absence split'])),
            'source_session_id' => $this->normalizeNullableString($this->pick($row, ['source_session_id', 'source session id', 'session_id', 'session id'])),
            'source_filename' => $this->normalizeNullableString($this->pick($row, ['source_filename', 'source filename', 'filename'])),
            'source_file_hash' => strtolower((string) ($this->normalizeNullableString($this->pick($row, ['source_file_hash', 'source file hash', 'file_hash', 'file hash'])) ?? '')) ?: null,
            'hris_transfer_format' => $this->normalizeNullableString($this->pick($row, ['hris_transfer_format', 'hris transfer format'])),
            'hris_transfer_version' => $this->parseInteger($this->pick($row, ['hris_transfer_version', 'hris transfer version'])),
            'early_overtime_minutes' => $this->parseInteger($this->pick($row, ['early_overtime_minutes', 'early overtime minutes'])),
            'overtime_minutes' => $this->parseInteger($this->pick($row, ['overtime_minutes', 'overtime minutes'])),
            'early_overtime_status' => $this->normalizeNullableString($this->pick($row, ['early_overtime_status', 'early overtime status'])),
            'after_overtime_status' => $this->normalizeNullableString($this->pick($row, ['after_overtime_status', 'after overtime status'])),
            'credited_early_overtime_minutes' => $this->parseInteger($this->pick($row, ['credited_early_overtime_minutes', 'credited early overtime minutes'])),
            'credited_overtime_minutes' => $this->parseInteger($this->pick($row, ['credited_overtime_minutes', 'credited overtime minutes'])),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getImportMetadata(array $data, string $importName): array
    {
        return [
            'is_imported' => 1,
            'import_name' => $this->normalizeNullableString($importName),
            'employee_id' => $this->getEmployee($data)?->id,
            'source_branch_id' => $data['source_branch_id'] ?? null,
            'source_session_id' => $data['source_session_id'] ?? null,
            'source_filename' => $data['source_filename'] ?? null,
            'source_file_hash' => $data['source_file_hash'] ?? null,
            'source_row_hash' => $this->buildSourceRowHash($data),
            'branch_id' => $this->getImportedBranchId($data),
            'daily_rate' => $this->getEmployeeDailyRate($data),
            ...$this->getHolidayData($data),
            ...$this->getImportedCalculationData($data),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildSourceRowHash(array $data): string
    {
        $fields = [
            (string) ($data['payroll_period_id'] ?? ''),
            (string) $this->getImportedBranchId($data),
            (string) ($data['fingerprint_id'] ?? ''),
            (string) ($data['date_in'] ?? ''),
            (string) ($data['time_in'] ?? ''),
            (string) ($data['date_out'] ?? ''),
            (string) ($data['time_out'] ?? ''),
            $this->resolveScheduleType($data),
            (string) ($data['schedule_start'] ?? ''),
            (string) ($data['schedule_end'] ?? ''),
        ];
        if ($this->isAbsenceRow($data)) {
            $fields[] = $data['day_part'];
            $fields[] = AutomaticAbsenceService::SOURCE;
        }

        return hash('sha256', implode('|', $fields));
    }

    /**
     * @param  array<int, string>  $sourceRowHashes
     * @return array<string, true>
     */
    protected function getExistingSourceRowHashes(array $sourceRowHashes): array
    {
        $existing = [];

        collect($sourceRowHashes)
            ->unique()
            ->chunk(1000)
            ->each(function ($hashes) use (&$existing): void {
                Dtr::query()
                    ->whereIn('source_row_hash', $hashes->all())
                    ->pluck('source_row_hash')
                    ->each(function (string $hash) use (&$existing): void {
                        $existing[$hash] = true;
                    });
            });

        return $existing;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function getEmployeeDailyRate(array $data): ?float
    {
        $dailyRate = $this->getEmployee($data)?->daily_rate;

        return filled($dailyRate) ? (float) $dailyRate : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function getEmployee(array $data): ?Employee
    {
        $fingerprintId = $data['fingerprint_id'] ?? null;

        if (blank($fingerprintId)) {
            return null;
        }

        $identity = $this->canonicalEmployeeIdentity($fingerprintId);
        $matches = $this->activeImportEmployees()
            ->filter(fn (Employee $employee): bool => $this->canonicalEmployeeIdentity($employee->fingerprint_id ?: $employee->uid) === $identity)
            ->values();

        if ($matches->count() > 1) {
            throw ValidationException::withMessages([
                'fingerprint_id' => 'This fingerprint ID belongs to multiple active employees. Resolve the duplicate identity before importing.',
            ]);
        }

        return $matches->first();
    }

    protected function canonicalEmployeeIdentity(mixed $value): string
    {
        $value = trim((string) $value);

        return ctype_digit($value) ? (ltrim($value, '0') ?: '0') : mb_strtolower($value);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function getImportedBranchId(array $data): mixed
    {
        return $this->getEmployee($data)?->branch_id ?? $data['branch_id'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getHolidayData(array $data): array
    {
        $dateIn = $data['date_in'] ?? null;

        if (blank($dateIn)) {
            return [
                'is_holiday' => 0,
                'holiday_id' => null,
                'holiday_type' => null,
                'holiday_rate' => null,
                'holiday_excluded' => false,
            ];
        }

        return app(HolidayEntitlementService::class)
            ->dtrHolidayData($this->getEmployee($data), $dateIn, $this->getImportedBranchId($data));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getImportedCalculationData(array $data): array
    {
        if ($this->isAbsenceRow($data)) {
            return app(AutomaticAbsenceService::class)->calculation($data);
        }

        return app(DtrOvertimeTransferService::class)->applyImportedPayload(
            $data,
            $this->calculateImportedDtrData($data),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function calculateImportedDtrData(array $data): array
    {
        $scheduleType = $this->resolveScheduleType($data);

        if ($scheduleType === 'Forgot to Punch') {
            return [
                'schedule_type' => $scheduleType,
                'day_part' => DtrDayPartService::WHOLE_DAY,
                'entry_source' => DtrDayPartService::SOURCE_IMPORTED,
                'schedule_start' => null,
                'schedule_end' => null,
                'absence_minutes' => 0,
                ...$this->emptyCalculationData(),
            ];
        }

        foreach (['date_in', 'time_in'] as $key) {
            if (blank($data[$key] ?? null)) {
                return $this->emptyCalculationData();
            }
        }

        $actualIn = Carbon::parse("{$data['date_in']} {$data['time_in']}");
        $actualOut = filled($data['date_out'] ?? null) && filled($data['time_out'] ?? null)
            ? Carbon::parse("{$data['date_out']} {$data['time_out']}")
            : null;

        if (! $actualOut) {
            return [
                'schedule_type' => $scheduleType,
                ...$this->emptyCalculationData(),
            ];
        }

        if ($actualOut->lessThan($actualIn)) {
            $actualOut->addDay();
        }

        if ($scheduleType === 'Overtime') {
            return [
                'schedule_type' => $scheduleType,
                'day_part' => DtrDayPartService::WHOLE_DAY,
                'entry_source' => DtrDayPartService::SOURCE_IMPORTED,
                'schedule_start' => $data['schedule_start'] ?? '00:00:00',
                'schedule_end' => $data['schedule_end'] ?? '00:00:00',
                'absence_minutes' => 0,
                ...app(DtrCalculator::class)->calculate(
                    dateIn: $data['date_in'],
                    timeIn: $data['time_in'],
                    dateOut: $data['date_out'],
                    timeOut: $data['time_out'],
                    scheduleStart: null,
                    scheduleEnd: null,
                    scheduleType: $scheduleType,
                    overtimeOnly: true,
                ),
                'is_absent' => false,
            ];
        }

        $scheduleStartValue = $this->getScheduleStartValue($data, $scheduleType);
        $scheduleEndValue = $this->getScheduleEndValue($data, $scheduleType);

        if (blank($scheduleStartValue) || blank($scheduleEndValue)) {
            return [
                'schedule_type' => $scheduleType,
                'day_part' => DtrDayPartService::WHOLE_DAY,
                'entry_source' => DtrDayPartService::SOURCE_IMPORTED,
                'absence_minutes' => 0,
                ...$this->emptyCalculationData(),
            ];
        }

        $dayPart = $this->resolveImportedDayPart($data, $scheduleType, $scheduleStartValue, $scheduleEndValue);

        if ($dayPart === DtrDayPartService::UNCLASSIFIED) {
            return [
                'schedule_type' => $scheduleType,
                'day_part' => $dayPart,
                'entry_source' => DtrDayPartService::SOURCE_IMPORTED,
                'schedule_start' => $scheduleStartValue,
                'schedule_end' => $scheduleEndValue,
                'absence_minutes' => 0,
                ...$this->emptyCalculationData(),
                'is_absent' => false,
            ];
        }

        [$scheduleStartValue, $scheduleEndValue] = app(DtrDayPartService::class)
            ->scheduleWindow($data['date_in'], $scheduleStartValue, $scheduleEndValue, $dayPart);

        return [
            'schedule_type' => $scheduleType,
            'day_part' => $dayPart,
            'entry_source' => DtrDayPartService::SOURCE_IMPORTED,
            'schedule_start' => $scheduleStartValue,
            'schedule_end' => $scheduleEndValue,
            'absence_minutes' => 0,
            ...app(DtrCalculator::class)->calculate(
                dateIn: $data['date_in'],
                timeIn: $data['time_in'],
                dateOut: $data['date_out'],
                timeOut: $data['time_out'],
                scheduleStart: $scheduleStartValue,
                scheduleEnd: $scheduleEndValue,
                scheduleStartColumn: $this->getScheduleStartColumn($scheduleType),
                scheduleType: $scheduleType,
                dayPart: $dayPart,
            ),
            'is_absent' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveImportedDayPart(
        array $data,
        string $scheduleType,
        string $scheduleStart,
        string $scheduleEnd,
    ): string {
        return match ($scheduleType) {
            'Brkn1' => DtrDayPartService::MORNING,
            'Brkn2' => DtrDayPartService::AFTERNOON,
            'Regular' => app(DtrDayPartService::class)->classifyRegularPunch(
                dateIn: $data['date_in'],
                timeIn: $data['time_in'],
                dateOut: $data['date_out'],
                timeOut: $data['time_out'],
                scheduleStart: $scheduleStart,
                scheduleEnd: $scheduleEnd,
                scheduleType: $scheduleType,
            ),
            default => DtrDayPartService::WHOLE_DAY,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $metadata
     */
    protected function hasExistingOrConflictingDatabaseRecord(array $data, array $metadata): bool
    {
        if ($this->isAbsenceRow($data)) {
            return false; // eligibility is checked after actual punches are saved
        }

        return $this->hasDatabaseConflict(
            $data,
            $metadata['day_part'] ?? DtrDayPartService::WHOLE_DAY,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function hasDatabaseConflict(
        array $data,
        ?string $dayPart,
        ?int $ignoreRecordId = null,
    ): bool {
        $records = $this->getPotentialConflictRecords($data)
            ->reject(fn (Dtr $record): bool => $ignoreRecordId !== null && (int) $record->getKey() === $ignoreRecordId)
            ->values();

        return $this->recordsConflictWithRow($records, $data, $dayPart);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  Collection<int, Dtr>|null  $pendingRecords
     */
    protected function hasBatchConflict(
        array $data,
        ?string $dayPart,
        $pendingRecords = null,
    ): bool {
        if (! $pendingRecords) {
            return false;
        }

        $records = $pendingRecords
            ->filter(
                fn (Dtr $record): bool => (int) $record->payroll_period_id === (int) $data['payroll_period_id']
                    && (int) $record->branch_id === (int) $this->getImportedBranchId($data)
                    && (string) $record->fingerprint_id === (string) $data['fingerprint_id'],
            )
            ->values();

        return $this->recordsConflictWithRow($records, $data, $dayPart);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function hasConflictingDtr(
        array $data,
        ?string $dayPart,
        $additionalRecords = null,
        ?int $ignoreRecordId = null,
    ): bool {
        $records = $this->getPotentialConflictRecords($data)
            ->reject(fn (Dtr $record): bool => $ignoreRecordId !== null && (int) $record->getKey() === $ignoreRecordId);

        if ($additionalRecords) {
            $records = $records
                ->concat($additionalRecords->filter(
                    fn (Dtr $record): bool => (int) $record->payroll_period_id === (int) $data['payroll_period_id']
                        && (int) $record->branch_id === (int) $this->getImportedBranchId($data)
                        && (string) $record->fingerprint_id === (string) $data['fingerprint_id'],
                ))
                ->values();
        }

        return $this->recordsConflictWithRow($records, $data, $dayPart);
    }

    /**
     * @param  Collection<int, Dtr>  $records
     * @param  array<string, mixed>  $data
     */
    protected function recordsConflictWithRow(Collection $records, array $data, ?string $dayPart): bool
    {
        if ($this->isAbsenceRow($data)) {
            return false;
        }

        return app(DtrEntryConflictService::class)->conflictsWith($records, $data, $dayPart);
    }

    /**
     * @param  array<int, array{row:int, data:array<string, mixed>}>  $validatedRows
     */
    protected function withImportLock(array $validatedRows, string $fallbackBatchId, callable $callback): array
    {
        $firstData = $validatedRows[0]['data'] ?? [];
        $periodId = (string) ($firstData['payroll_period_id'] ?? 'none');
        $lockKey = 'dtr-import-period:'.$periodId;

        return Cache::lock($lockKey, 300)->block(10, $callback);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    protected function withSummary(array $result): array
    {
        $result['already_existing'] = (int) ($result['already_existing'] ?? $result['skipped'] ?? 0);
        $result['invalid'] = (int) ($result['invalid'] ?? $result['failed'] ?? 0);
        $result['summary'] = [
            'total' => (int) ($result['total'] ?? 0),
            'imported' => (int) ($result['successful'] ?? 0),
            'already_existing' => (int) $result['already_existing'],
            'skipped' => (int) ($result['skipped'] ?? $result['already_existing']),
            'invalid' => (int) $result['invalid'],
            'failed' => (int) ($result['failed'] ?? 0),
            'total_records_processed' => (int) ($result['total'] ?? 0),
            'newly_imported_records' => (int) ($result['successful'] ?? 0),
            'skipped_already_existing_records' => (int) $result['already_existing'],
            'invalid_or_failed_records' => (int) ($result['failed'] ?? 0),
        ];

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, Dtr>
     */
    protected function getPotentialConflictRecords(array $data)
    {
        if (
            blank($data['payroll_period_id'] ?? null)
            || blank($this->getImportedBranchId($data))
            || blank($data['fingerprint_id'] ?? null)
            || blank($data['date_in'] ?? null)
        ) {
            return collect();
        }

        $rangeStart = Carbon::parse($data['date_in'])->subDay()->toDateString();
        $rangeEnd = filled($data['date_out'] ?? null)
            ? Carbon::parse($data['date_out'])->toDateString()
            : Carbon::parse($data['date_in'])->toDateString();

        $records = Dtr::query()
            ->where('payroll_period_id', $data['payroll_period_id'])
            ->where('branch_id', $this->getImportedBranchId($data))
            ->where('fingerprint_id', (string) $data['fingerprint_id'])
            ->whereBetween('date_in', [$rangeStart, $rangeEnd])
            ->get();

        return $this->replacesSaturdayWfh($data)
            ? $records->reject(fn (Dtr $record): bool => $record->entry_source === SaturdayWfhDtrService::SOURCE
                && $record->date_in === $data['date_in'] && ! $record->is_locked)->values()
            : $records;
    }

    /** @param array<string, mixed> $data */
    protected function replacesSaturdayWfh(array $data): bool
    {
        return ! $this->isAbsenceRow($data)
            && $this->resolveScheduleType($data) !== 'Overtime'
            && Carbon::parse($data['date_in'])->isSaturday();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $metadata
     */
    protected function makePendingRecord(array $data, array $metadata): Dtr
    {
        return (new Dtr)->forceFill([
            'batch_id' => $data['batch_id'],
            'payroll_period_id' => $data['payroll_period_id'],
            'branch_id' => $data['branch_id'],
            'fingerprint_id' => $data['fingerprint_id'],
            'date_in' => $data['date_in'],
            'time_in' => $data['time_in'],
            'date_out' => $data['date_out'],
            'time_out' => $data['time_out'],
            'schedule_type' => $data['schedule_type'],
            'schedule_start' => $data['schedule_start'],
            'schedule_end' => $data['schedule_end'],
            ...$metadata,
        ]);
    }

    protected function emptyCalculationData(bool $isAbsent = false): array
    {
        return app(DtrCalculator::class)->emptyCalculationData($isAbsent);
    }

    protected function normalizeScheduleType(?string $scheduleType): string
    {
        $value = str($scheduleType ?? 'Regular')->lower();
        if ($value->trim()->toString() === 'absent') {
            return 'Absent';
        }

        if ($value->contains('forgot')) {
            return 'Forgot to Punch';
        }

        if ($value->contains('saturday')) {
            return 'Saturday';
        }

        if ($value->contains('over')) {
            return 'Overtime';
        }

        if ($value->contains('shift1')) {
            return 'Shift1';
        }

        if ($value->contains('shift2')) {
            return 'Shift2';
        }

        if ($value->contains('shift3')) {
            return 'Shift3';
        }

        if ($value->contains('brkn1') || $value->contains('broken shift 1')) {
            return 'Brkn1';
        }

        if ($value->contains('brkn2') || $value->contains('broken shift 2')) {
            return 'Brkn2';
        }

        return $value->contains('regular') ? 'Regular' : (string) ($scheduleType ?: 'Regular');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveScheduleType(array $data): string
    {
        $scheduleType = $this->normalizeScheduleType($data['schedule_type'] ?? null);
        $isSaturday = filled($data['date_in'] ?? null)
            && Carbon::parse($data['date_in'])->isSaturday();

        if (
            $isSaturday
            && $this->employeeUsesSaturdaySchedule($data)
            && ! in_array($scheduleType, ['Overtime', 'Forgot to Punch'], true)
        ) {
            return 'Saturday';
        }

        if ($scheduleType === 'Saturday' && ! $this->employeeUsesSaturdaySchedule($data)) {
            return $this->inferNonSaturdayScheduleType($data);
        }

        return $scheduleType;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function employeeUsesSaturdaySchedule(array $data): bool
    {
        return str($this->getEmployee($data)?->rate_type ?? '')
            ->lower()
            ->contains('month');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function inferNonSaturdayScheduleType(array $data): string
    {
        $branch = $this->getBranch($data);
        $scheduleStart = $data['schedule_start'] ?? null;

        if ($branch && filled($scheduleStart)) {
            try {
                $normalizedStart = Carbon::parse($scheduleStart)->format('H:i:s');

                foreach ([
                    'reg_sched_start' => 'Regular',
                    'shift1_start' => 'Shift1',
                    'shift2_start' => 'Shift2',
                    'shift3_start' => 'Shift3',
                    'broken_shift1_start' => 'Brkn1',
                    'broken_shift2_start' => 'Brkn2',
                ] as $column => $type) {
                    if (filled($branch->{$column}) && Carbon::parse($branch->{$column})->format('H:i:s') === $normalizedStart) {
                        return $type;
                    }
                }
            } catch (\Throwable) {
                // The validator will report malformed schedule values.
            }
        }

        return $this->normalizeScheduleType($this->getEmployee($data)?->schedule_type ?: 'Regular');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function getScheduleStartValue(array $data, string $scheduleType): ?string
    {
        if ($scheduleType === 'Saturday') {
            return '08:00:00';
        }

        return $data['schedule_start'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function getScheduleEndValue(array $data, string $scheduleType): ?string
    {
        if ($scheduleType === 'Saturday') {
            return '11:00:00';
        }

        return $data['schedule_end'] ?? null;
    }

    protected function getScheduleStartColumn(string $scheduleType): ?string
    {
        return in_array($scheduleType, ['Regular', 'Saturday'], true)
            ? 'reg_sched_start'
            : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function getBranch(array $data): ?Branch
    {
        $branchId = $this->getImportedBranchId($data);

        return filled($branchId) ? Branch::query()->find($branchId) : null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $aliases
     */
    protected function pick(array $row, array $aliases): mixed
    {
        $lookup = [];

        foreach ($row as $key => $value) {
            $lookup[$this->normalizeKey((string) $key)] = $value;
        }

        foreach ($aliases as $alias) {
            $key = $this->normalizeKey($alias);

            if (array_key_exists($key, $lookup)) {
                return $lookup[$key];
            }
        }

        return null;
    }

    protected function normalizeKey(string $key): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower($key)), '_');
    }

    protected function parseInteger(mixed $state): ?int
    {
        $state = $this->cleanSpreadsheetValue($state);

        if (blank($state) || ! is_numeric($state)) {
            return null;
        }

        return (int) $state;
    }

    protected function parseDate(mixed $state): ?string
    {
        $state = $this->cleanSpreadsheetValue($state);

        if (blank($state)) {
            return null;
        }

        if (is_numeric($state) && (float) $state > 1000) {
            return Carbon::create(1899, 12, 30)
                ->addDays((int) $state)
                ->format('Y-m-d');
        }

        $state = trim((string) $state);

        foreach ([
            'Y-m-d',
            'Y/m/d',
            'd/m/Y',
            'j/n/Y',
            'd-m-Y',
            'j-n-Y',
            'm/d/Y',
            'n/j/Y',
            'm-d-Y',
            'n-j-Y',
        ] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $state);

                if ($date && $date->format($format) === $state) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                //
            }
        }

        try {
            return Carbon::parse($state)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseTime(mixed $state): ?string
    {
        $state = $this->cleanSpreadsheetValue($state);

        if (blank($state)) {
            return null;
        }

        if (is_numeric($state)) {
            $seconds = (int) round((((float) $state) - floor((float) $state)) * 86400);
            $seconds = ($seconds % 86400 + 86400) % 86400;

            return sprintf(
                '%02d:%02d:%02d',
                intdiv($seconds, 3600),
                intdiv($seconds % 3600, 60),
                $seconds % 60,
            );
        }

        try {
            return Carbon::parse($state)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function cleanSpreadsheetValue(mixed $state): mixed
    {
        if (! is_string($state)) {
            return $state;
        }

        $state = trim($state);

        if (preg_match('/^=\s*"([^"]*)"$/', $state, $matches)) {
            return $matches[1];
        }

        return $state;
    }

    protected function normalizeNullableString(mixed $state): ?string
    {
        if (! is_string($state)) {
            return filled($state) ? (string) $state : null;
        }

        $state = trim($state);

        return filled($state) ? $state : null;
    }
}
