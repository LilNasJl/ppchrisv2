<?php

namespace App\Services\Imports;

use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Services\AutomaticAbsenceService;
use App\Services\DtrDayPartService;
use App\Services\DtrSynchronizationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployeeVisibleDtrImportService extends DtrImportService
{
    /** @var array<int, Collection<int, Employee>> */
    protected array $activeEmployeesByBranch = [];

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
            'already_existing' => 0,
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
                $result['failed']++;
                $result['invalid']++;

                foreach (collect($exception->errors())->flatten()->all() ?: ['Unable to validate D.T.R row.'] as $message) {
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

                if (filled($sourceRowHash) && (isset($seenSourceRowHashes[$sourceRowHash]) || isset($existingSourceRowHashes[$sourceRowHash]))) {
                    $result['skipped']++;
                    $result['already_existing']++;

                    continue;
                }

                $metadata = $this->getImportMetadata($data, $importName);

                if ($this->hasExistingOrConflictingDatabaseRecord($data, $metadata)) {
                    $result['skipped']++;
                    $result['already_existing']++;

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
                $result['message'] = 'No new D.T.R records were imported. Every decoded row was already imported.';

                return $this->withSummary($result);
            }

            try {
                DB::transaction(function () use ($rowsToImport, $importName, &$result): void {
                    $savedIds = [];

                    foreach ($rowsToImport as $validatedRow) {
                        $saved = $this->saveValidatedRow($validatedRow['data'], $importName);

                        if (! empty($saved['skipped'])) {
                            $result['skipped']++;
                            $result['already_existing']++;
                            if (! empty($saved['reason'])) {
                                $result['notices'][] = ['row' => $validatedRow['row'], 'message' => $saved['reason']];
                            }

                            continue;
                        }

                        $result['successful']++;
                        $savedIds[] = $saved['id'];
                    }

                    if ($savedIds !== [] && Schema::hasTable('dtrs')) {
                        $visibleRecords = EmployeeVisibleDtr::query()->whereIn('id', $savedIds)->get();
                        app(DtrSynchronizationService::class)->syncVisibleBatchToOfficial($visibleRecords);
                    }
                });

                $result['message'] = $result['skipped'] > 0
                    ? "D.T.R import completed and synchronized with HR. {$result['successful']} imported and {$result['skipped']} existing or excluded row(s) skipped."
                    : 'D.T.R import completed and synchronized with HR.';
            } catch (\Throwable $exception) {
                report($exception);

                $result['successful'] = 0;
                $result['failed'] = count($rows) - $result['skipped'];
                $result['message'] = 'Import failed. No employee-visible D.T.R records were saved.';
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
            $result = $this->saveValidatedRow($data, $importName);

            if (! empty($result['id']) && empty($result['skipped']) && Schema::hasTable('dtrs')) {
                $visible = EmployeeVisibleDtr::query()->find($result['id']);

                if ($visible) {
                    app(DtrSynchronizationService::class)->syncVisibleToOfficial($visible);
                }
            }

            return $result;
        });
    }

    protected function validateRow(array $row, string $fallbackBatchId): array
    {
        $data = parent::validateRow($row, $fallbackBatchId);

        $employee = $this->resolveEmployeeByFingerprint($data);
        $sourceBranchId = $data['source_branch_id'] ?? null;
        $recordBranchId = filled($sourceBranchId)
            ? (int) $sourceBranchId
            : (int) ($employee->branch_id ?? 0);

        if ($recordBranchId < 1) {
            throw ValidationException::withMessages([
                'fingerprint_id' => 'This fingerprint ID does not match an active employee with a branch.',
            ]);
        }

        // A branch-specific legacy import remains attributed to its station.
        // A branch-neutral import is grouped under each matched employee's HRIS branch.
        $data['branch_id'] = $recordBranchId;

        $data['employee_id'] = $employee->getKey();

        if (filled($employee->fingerprint_id)) {
            $data['fingerprint_id'] = (string) $employee->fingerprint_id;
        }

        return $data;
    }

    protected function getImportMetadata(array $data, string $importName): array
    {
        $metadata = parent::getImportMetadata($data, $importName);
        unset($metadata['source_branch_id']);

        // Station-side DTRs retain the upload station for SIC/RC visibility.
        $metadata['branch_id'] = $data['branch_id'];

        return $metadata;
    }

    protected function hasExistingOrConflictingDatabaseRecord(array $data, array $metadata): bool
    {
        if ($this->isAbsenceRow($data)) {
            return false;
        }
        $existing = $this->findExistingRecord($data, $metadata);

        if ($existing) {
            if (Schema::hasTable('dtrs') && blank($existing->dtr_source_id)) {
                app(DtrSynchronizationService::class)->syncVisibleToOfficial($existing);
            }

            return true;
        }

        return $this->hasDatabaseConflict(
            $data,
            $metadata['day_part'] ?? DtrDayPartService::WHOLE_DAY,
        );
    }

    protected function saveValidatedRow(array $data, string $importName): array
    {
        $this->lockImportPeriod($data);
        if ($this->isAbsenceRow($data)) {
            [$data, $reason] = app(AutomaticAbsenceService::class)->assessCandidate($data, $this->getEmployee($data));
            if ($reason) {
                return ['id' => 0, 'batch_id' => $data['batch_id'], 'skipped' => true, 'reason' => $reason];
            }
        }
        $metadata = $this->getImportMetadata($data, $importName);
        $existing = $this->findExistingRecord($data, $metadata);

        if ($existing || $this->hasDatabaseConflict($data, $metadata['day_part'] ?? DtrDayPartService::WHOLE_DAY)) {
            if ($existing && Schema::hasTable('dtrs') && blank($existing->dtr_source_id)) {
                app(DtrSynchronizationService::class)->syncVisibleToOfficial($existing);
            }

            return [
                'id' => $existing?->id ?? 0,
                'batch_id' => $existing?->batch_id ?? ($data['batch_id'] ?? null),
                'skipped' => true,
            ];
        }

        $record = new EmployeeVisibleDtr;

        $record->forceFill($this->filterAttributesForTable('employee_visible_dtrs', [
            'batch_id' => $data['batch_id'],
            'payroll_period_id' => $data['payroll_period_id'],
            'branch_id' => $data['branch_id'],
            'employee_id' => $data['employee_id'],
            'fingerprint_id' => (string) $data['fingerprint_id'],
            'date_in' => $data['date_in'],
            'time_in' => $data['time_in'],
            'date_out' => $data['date_out'],
            'time_out' => $data['time_out'],
            'schedule_type' => $data['schedule_type'],
            'schedule_start' => $data['schedule_start'],
            'schedule_end' => $data['schedule_end'],
            ...$metadata,
            'latest_source_row_hash' => null,
            'latest_source_payload' => null,
            'needs_review' => false,
            'review_reason' => null,
        ]))->save();

        app(AutomaticAbsenceService::class)->supersede($record);

        return [
            'id' => $record->id,
            'batch_id' => $record->batch_id,
        ];
    }

    protected function makePendingRecord(array $data, array $metadata): Dtr
    {
        return (new EmployeeVisibleDtr)->forceFill($this->filterAttributesForTable('employee_visible_dtrs', [
            'batch_id' => $data['batch_id'],
            'payroll_period_id' => $data['payroll_period_id'],
            'branch_id' => $data['branch_id'],
            'employee_id' => $data['employee_id'],
            'fingerprint_id' => (string) $data['fingerprint_id'],
            'date_in' => $data['date_in'],
            'time_in' => $data['time_in'],
            'date_out' => $data['date_out'],
            'time_out' => $data['time_out'],
            'schedule_type' => $data['schedule_type'],
            'schedule_start' => $data['schedule_start'],
            'schedule_end' => $data['schedule_end'],
            ...$metadata,
        ]));
    }

    protected function getExistingSourceRowHashes(array $sourceRowHashes): array
    {
        $existing = [];

        collect($sourceRowHashes)
            ->filter()
            ->unique()
            ->chunk(1000)
            ->each(function ($hashes) use (&$existing): void {
                EmployeeVisibleDtr::query()
                    ->whereIn('source_row_hash', $hashes->all())
                    ->pluck('source_row_hash')
                    ->each(function (string $hash) use (&$existing): void {
                        $existing[$hash] = true;
                    });
            });

        return $existing;
    }

    protected function getPotentialConflictRecords(array $data)
    {
        if (
            blank($data['payroll_period_id'] ?? null)
            || blank($this->getImportedBranchId($data))
            || (blank($data['employee_id'] ?? null) && blank($data['fingerprint_id'] ?? null))
            || blank($data['date_in'] ?? null)
        ) {
            return collect();
        }

        $rangeStart = Carbon::parse($data['date_in'])->subDay()->toDateString();
        $rangeEnd = filled($data['date_out'] ?? null)
            ? Carbon::parse($data['date_out'])->toDateString()
            : Carbon::parse($data['date_in'])->toDateString();

        return EmployeeVisibleDtr::query()
            ->where('payroll_period_id', $data['payroll_period_id'])
            ->when(filled($data['source_branch_id'] ?? null), fn (Builder $query): Builder => $query
                ->where('branch_id', $data['branch_id']))
            ->tap(fn (Builder $query): Builder => $this->applyEmployeeIdentity($query, $data))
            ->whereBetween('date_in', [$rangeStart, $rangeEnd])
            ->get();
    }

    protected function findExistingRecord(array $data, array $metadata): ?EmployeeVisibleDtr
    {
        $query = EmployeeVisibleDtr::query()
            ->where('payroll_period_id', $data['payroll_period_id'])
            ->whereNull('on_field_dtr_submission_id');
        if (filled($data['source_branch_id'] ?? null)) {
            $query->where('branch_id', $data['branch_id']);
        }
        if (! $this->isAbsenceRow($data)) {
            $query->where(fn (Builder $q) => $q->whereNull('entry_source')->orWhere('entry_source', '!=', AutomaticAbsenceService::SOURCE));
        }
        $this->applyEmployeeIdentity($query, $data);

        if (filled($data['source_session_id'] ?? null)) {
            $sessionMatch = (clone $query)
                ->where('source_session_id', $data['source_session_id'])
                ->first();

            if ($sessionMatch) {
                return $sessionMatch;
            }
        }

        $scheduleType = $this->resolveScheduleType($data);
        $dayPart = $metadata['day_part'] ?? DtrDayPartService::WHOLE_DAY;

        return $query
            ->whereDate('date_in', $data['date_in'])
            ->where('day_part', $dayPart)
            ->when($scheduleType === 'Overtime', fn (Builder $query): Builder => $query
                ->where('schedule_type', 'Overtime')
                ->where('time_in', $data['time_in']))
            ->when($scheduleType !== 'Overtime', fn (Builder $query): Builder => $query
                ->where(fn (Builder $query): Builder => $query
                    ->where('schedule_type', '!=', 'Overtime')
                    ->orWhereNull('schedule_type')))
            ->orderBy('id')
            ->first();
    }

    protected function hasProtectedManualState(EmployeeVisibleDtr $record): bool
    {
        return $record->isControlledOnFieldDtr()
            || (bool) $record->is_manually_edited
            || filled($record->manual_edited_at)
            || in_array($record->overtime_status, ['Approved', 'Rejected'], true)
            || (bool) $record->early_clock_in_approved
            || (bool) $record->overtime_approved;
    }

    protected function getEmployee(array $data): ?Employee
    {
        if (filled($data['employee_id'] ?? null)) {
            return Employee::query()->find($data['employee_id']);
        }

        return parent::getEmployee($data);
    }

    protected function applyEmployeeIdentity(Builder $query, array $data): Builder
    {
        if (filled($data['employee_id'] ?? null)) {
            return $query->where(function (Builder $query) use ($data): void {
                $query
                    ->where('employee_id', $data['employee_id'])
                    ->orWhere(function (Builder $query) use ($data): void {
                        $query
                            ->whereNull('employee_id')
                            ->where('fingerprint_id', (string) $data['fingerprint_id']);
                    });
            });
        }

        return $query->where('fingerprint_id', (string) $data['fingerprint_id']);
    }

    protected function resolveEmployeeByFingerprint(array $data): Employee
    {
        $identity = $this->canonicalIdentity($data['fingerprint_id'] ?? null);

        if ($identity === '') {
            throw ValidationException::withMessages([
                'fingerprint_id' => 'A valid fingerprint ID is required to identify the employee.',
            ]);
        }

        $activeEmployees = $this->activeImportEmployees();
        $matches = $activeEmployees
            ->filter(fn (Employee $employee): bool => filled($employee->fingerprint_id)
                && $this->canonicalIdentity($employee->fingerprint_id) === $identity)
            ->values();

        if ($matches->isEmpty()) {
            $matches = $activeEmployees
                ->filter(fn (Employee $employee): bool => blank($employee->fingerprint_id)
                    && $this->canonicalIdentity($employee->uid) === $identity)
                ->values();
        }

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->count() > 1) {
            throw ValidationException::withMessages([
                'fingerprint_id' => sprintf(
                    'Fingerprint ID %s matches multiple active employees. Correct the duplicate fingerprint IDs before importing.',
                    $data['fingerprint_id'],
                ),
            ]);
        }

        throw ValidationException::withMessages([
            'fingerprint_id' => sprintf(
                'Fingerprint ID %s does not match an active employee.',
                $data['fingerprint_id'],
            ),
        ]);
    }

    protected function employeesForBranch(int $branchId): Collection
    {
        return $this->activeEmployeesByBranch[$branchId] ??= Employee::query()
            ->activeEmployment()
            ->where('branch_id', $branchId)
            ->get(['id', 'uid', 'fingerprint_id', 'branch_id', 'firstname', 'middlename', 'lastname']);
    }

    protected function canonicalIdentity(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return ctype_digit($value)
            ? (ltrim($value, '0') ?: '0')
            : mb_strtolower($value);
    }
}
