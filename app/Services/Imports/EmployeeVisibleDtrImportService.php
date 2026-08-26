<?php

namespace App\Services\Imports;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Services\DtrDayPartService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployeeVisibleDtrImportService extends DtrImportService
{
    /** @var array<int, Collection<int, Employee>> */
    protected array $activeEmployeesByBranch = [];

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{total:int, successful:int, failed:int, skipped:int, batch_id:string, message:string, errors:array<int, array<string, mixed>>}
     */
    public function importRows(array $rows, string $importName, ?string $fallbackBatchId = null): array
    {
        $fallbackBatchId = $this->normalizeNullableString($fallbackBatchId) ?: Str::random(12);

        $result = [
            'total' => count($rows),
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'batch_id' => $fallbackBatchId,
            'message' => 'No rows were imported.',
            'errors' => [],
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

                foreach (collect($exception->errors())->flatten()->all() ?: ['Unable to validate D.T.R row.'] as $message) {
                    $result['errors'][] = [
                        'row' => $rowNumber,
                        'message' => $message,
                    ];
                }
            } catch (\Throwable $exception) {
                report($exception);

                $result['failed']++;
                $result['errors'][] = [
                    'row' => $rowNumber,
                    'message' => $exception->getMessage() ?: 'Unable to validate D.T.R row.',
                ];
            }
        }

        if ($result['errors'] !== []) {
            $result['message'] = 'Import cancelled. No D.T.R records were saved. Fix the listed rows and import again.';

            return $result;
        }

        if ($validatedRows !== []) {
            $result['batch_id'] = $validatedRows[0]['data']['batch_id'];
        }

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

                continue;
            }

            $metadata = $this->getImportMetadata($data, $importName);
            $existing = $this->findExistingRecord($data, $metadata);

            if ($this->hasConflictingDtr(
                $data,
                $metadata['day_part'] ?? DtrDayPartService::WHOLE_DAY,
                $pendingRecords,
                $existing?->getKey(),
            )) {
                $result['failed']++;
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

            return $result;
        }

        if ($rowsToImport === []) {
            $result['message'] = 'No new D.T.R records were imported. Every decoded row was already imported.';

            return $result;
        }

        try {
            DB::transaction(function () use ($rowsToImport, $importName, &$result): void {
                foreach ($rowsToImport as $validatedRow) {
                    $this->saveValidatedRow($validatedRow['data'], $importName);
                    $result['successful']++;
                }
            });

            $result['message'] = $result['skipped'] > 0
                ? "D.T.R preview import completed. {$result['successful']} imported/merged and {$result['skipped']} duplicate row(s) skipped."
                : 'D.T.R preview import completed successfully.';
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

        return $result;
    }

    protected function validateRow(array $row, string $fallbackBatchId): array
    {
        $data = parent::validateRow($row, $fallbackBatchId);
        $employee = $this->resolveEmployeeForSelectedBranch($data);

        $data['employee_id'] = $employee->getKey();

        if (filled($employee->fingerprint_id)) {
            $data['fingerprint_id'] = (string) $employee->fingerprint_id;
        }

        return $data;
    }

    protected function saveValidatedRow(array $data, string $importName): array
    {
        $metadata = $this->getImportMetadata($data, $importName);
        $sourceRowHash = $metadata['source_row_hash'] ?? $this->buildSourceRowHash($data);
        $existing = $this->findExistingRecord($data, $metadata);

        if ($existing && $this->hasProtectedManualState($existing)) {
            $existing->forceFill([
                'batch_id' => $data['batch_id'],
                'import_name' => $metadata['import_name'] ?? $importName,
                'employee_id' => $data['employee_id'],
                'latest_source_row_hash' => $sourceRowHash,
                'latest_source_payload' => $data,
                'needs_review' => true,
                'review_reason' => 'A newer biometric D.T.R row matched this manually edited or overtime-reviewed row. Review before changing it.',
            ])->save();

            return [
                'id' => $existing->id,
                'batch_id' => $existing->batch_id,
            ];
        }

        $record = $existing ?: new EmployeeVisibleDtr;

        $record->forceFill([
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
        ])->save();

        return [
            'id' => $record->id,
            'batch_id' => $record->batch_id,
        ];
    }

    protected function makePendingRecord(array $data, array $metadata): Dtr
    {
        return (new EmployeeVisibleDtr)->forceFill([
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
        ]);
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
            ->where('branch_id', $this->getImportedBranchId($data))
            ->tap(fn (Builder $query): Builder => $this->applyEmployeeIdentity($query, $data))
            ->whereBetween('date_in', [$rangeStart, $rangeEnd])
            ->get();
    }

    protected function findExistingRecord(array $data, array $metadata): ?EmployeeVisibleDtr
    {
        $query = EmployeeVisibleDtr::query()
            ->where('payroll_period_id', $data['payroll_period_id'])
            ->where('branch_id', $this->getImportedBranchId($data))
            ->whereNull('on_field_dtr_submission_id');
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

    protected function resolveEmployeeForSelectedBranch(array $data): Employee
    {
        $branchId = (int) ($data['branch_id'] ?? 0);
        $identity = $this->canonicalIdentity($data['fingerprint_id'] ?? null);

        if ($branchId < 1 || $identity === '') {
            throw ValidationException::withMessages([
                'fingerprint_id' => 'A valid branch and fingerprint ID are required to identify the employee.',
            ]);
        }

        $branchEmployees = $this->employeesForBranch($branchId);
        $matches = $branchEmployees
            ->filter(fn (Employee $employee): bool => filled($employee->fingerprint_id)
                && $this->canonicalIdentity($employee->fingerprint_id) === $identity)
            ->values();

        if ($matches->isEmpty()) {
            $matches = $branchEmployees
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
                    'Fingerprint ID %s matches multiple active employees in the selected branch. Correct the duplicate fingerprint IDs before importing.',
                    $data['fingerprint_id'],
                ),
            ]);
        }

        $outsideMatches = Employee::query()
            ->with('branch:id,branch_name')
            ->activeEmployment()
            ->whereNot('branch_id', $branchId)
            ->get(['id', 'uid', 'fingerprint_id', 'branch_id', 'firstname', 'middlename', 'lastname'])
            ->filter(fn (Employee $employee): bool => filled($employee->fingerprint_id)
                && $this->canonicalIdentity($employee->fingerprint_id) === $identity)
            ->values();

        if ($outsideMatches->isNotEmpty()) {
            $employee = $outsideMatches->first();
            $selectedBranch = Branch::query()->whereKey($branchId)->value('branch_name') ?: 'selected branch';

            throw ValidationException::withMessages([
                'fingerprint_id' => sprintf(
                    'Fingerprint ID %s belongs to %s in %s, not %s.',
                    $data['fingerprint_id'],
                    $employee->full_name,
                    $employee->branch?->branch_name ?: 'another branch',
                    $selectedBranch,
                ),
            ]);
        }

        throw ValidationException::withMessages([
            'fingerprint_id' => sprintf(
                'Fingerprint ID %s does not match an active employee in the selected branch.',
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
