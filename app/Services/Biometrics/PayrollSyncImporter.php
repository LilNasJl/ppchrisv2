<?php

namespace App\Services\Biometrics;

use App\Models\BiometricsSyncRequest;
use App\Models\Employee;
use App\Services\Imports\DtrImportService;
use RuntimeException;

class PayrollSyncImporter
{
    public function import(BiometricsSyncRequest $sync, string $binary, array $export): array
    {
        $records = $this->verifiedRecords($binary, $export);
        $identitySets = $this->fingerprintIdentitySets();
        $rows = [];
        $attemptedRecords = [];
        $attemptedSourceRows = [];
        $pending = 0;
        $inactiveSkippedRows = [];
        $unmatchedSkippedRows = [];
        $missingFingerprintSkippedRows = [];
        $skippedRowReasons = [];
        foreach ($records as $index => $record) {
            $date = (string) ($record['date_in'] ?? '');
            if ($date < $sync->date_from->toDateString() || $date > $sync->date_to->toDateString()) {
                throw new RuntimeException('Export contains an out-of-range DTR date.');
            }
            // Device identity is not an HRIS branch ID. Match employees globally through the importer.
            $sourceRow = $index + 1;
            $identity = $this->canonicalFingerprintIdentity($record['uid'] ?? null);
            if ($identity === '') {
                $missingFingerprintSkippedRows[] = $sourceRow;
                $skippedRowReasons[$sourceRow] = 'No fingerprint ID was supplied; this exported row was skipped.';

                continue;
            }
            if (isset($identitySets['active'][$identity])) {
                // Keep active matches in the normal importer so it can detect duplicate active identities.
            } elseif (isset($identitySets['inactive'][$identity])) {
                $inactiveSkippedRows[] = $sourceRow;
                $skippedRowReasons[$sourceRow] = 'Fingerprint ID '.$record['uid'].' belongs to an inactive employee or an employee whose employment has ended; this row was skipped.';

                continue;
            } else {
                $unmatchedSkippedRows[] = $sourceRow;
                $skippedRowReasons[$sourceRow] = 'No active HRIS employee matches fingerprint ID '.$record['uid'].'; this exported row was skipped.';

                continue;
            }
            if ($this->isDeferred($record)) {
                $pending++;

                continue;
            }
            $attemptedRecords[] = $record;
            $attemptedSourceRows[] = $sourceRow;
            $rows[] = [
                'batch_id' => $sync->id, 'payroll_period_id' => $sync->payroll_period_id, 'fingerprint_id' => $record['uid'] ?? null,
                'date_in' => $date, 'time_in' => $record['time_in'] ?? null, 'date_out' => $record['date_out'] ?? null, 'time_out' => $record['time_out'] ?? null,
                'schedule_type' => $record['sched'] ?? null, 'schedule_start' => $record['sched_start'] ?? null, 'schedule_end' => $record['sched_end'] ?? null,
                'day_part' => $record['day_part'] ?? null, 'entry_source' => $record['entry_source'] ?? null, 'absence_candidate' => $record['absence_candidate'] ?? false,
                'regular_schedule' => $record['regular_schedule'] ?? null, 'absence_split' => $record['absence_split'] ?? null,
                'source_session_id' => $record['session_id'] ?? null, 'source_filename' => $export['file_name'] ?? 'payroll.bin',
                'source_file_hash' => $export['sha256'],
            ];
        }
        $result = $rows ? app(DtrImportService::class)->importRows($rows, 'Biometrics Payroll: '.$sync->device_name) :
            ['successful' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => [], 'message' => 'No completed records were available to import.'];
        $importSkippedExportRows = [];
        $importSkipReasons = [];
        foreach ($result['skipped_rows'] ?? [] as $importRow) {
            $importRow = (int) $importRow;
            $sourceRow = $attemptedSourceRows[$importRow - 1] ?? null;
            if ($sourceRow !== null) {
                $importSkippedExportRows[$sourceRow] = true;
                $importSkipReasons[$sourceRow] = 'An existing or previously imported DTR was preserved; this row was skipped.';
            }
        }
        foreach ($result['notices'] ?? [] as $notice) {
            $importRow = (int) ($notice['row'] ?? 0);
            $sourceRow = $attemptedSourceRows[$importRow - 1] ?? null;
            if ($sourceRow !== null && isset($importSkippedExportRows[$sourceRow])) {
                $importSkipReasons[$sourceRow] = (string) ($notice['message'] ?? $importSkipReasons[$sourceRow]);
            }
        }
        $result['import_skipped_export_rows'] = array_map('intval', array_keys($importSkippedExportRows));
        $result['skipped_row_reasons'] = $skippedRowReasons + $importSkipReasons;
        $result['pending'] = $pending;
        $result['export'] = $export;
        $result['inactive_skipped'] = count($inactiveSkippedRows);
        $result['inactive_skipped_rows'] = $inactiveSkippedRows;
        $result['unmatched_skipped'] = count($unmatchedSkippedRows);
        $result['unmatched_fingerprint_skipped_rows'] = $unmatchedSkippedRows;
        $result['missing_fingerprint_skipped'] = count($missingFingerprintSkippedRows);
        $result['missing_fingerprint_skipped_rows'] = $missingFingerprintSkippedRows;
        $result['skipped_row_reasons'] = $skippedRowReasons;
        $sourceSkippedCount = count($skippedRowReasons);
        $result['total'] = (int) ($result['total'] ?? 0) + $sourceSkippedCount;
        $result['skipped'] = (int) ($result['skipped'] ?? 0) + $sourceSkippedCount;
        if (isset($result['summary']) && is_array($result['summary'])) {
            $result['summary']['total'] = (int) ($result['summary']['total'] ?? 0) + $sourceSkippedCount;
            $result['summary']['skipped'] = (int) ($result['summary']['skipped'] ?? 0) + $sourceSkippedCount;
            $result['summary']['total_records_processed'] = (int) ($result['summary']['total_records_processed'] ?? 0) + $sourceSkippedCount;
            $result['summary']['skipped_inactive_employees'] = count($inactiveSkippedRows);
            $result['summary']['skipped_unmatched_fingerprints'] = count($unmatchedSkippedRows);
            $result['summary']['skipped_missing_fingerprints'] = count($missingFingerprintSkippedRows);
        }
        $result['row_details'] = $this->issueRowDetails($attemptedRecords, $result);
        $skipMessages = [];
        if ($inactiveSkippedRows !== []) {
            $skipMessages[] = count($inactiveSkippedRows).' row'.(count($inactiveSkippedRows) === 1 ? '' : 's').' skipped because the matched employee is inactive or has ended employment.';
        }
        if ($unmatchedSkippedRows !== []) {
            $skipMessages[] = count($unmatchedSkippedRows).' row'.(count($unmatchedSkippedRows) === 1 ? '' : 's').' skipped because no active HRIS employee matches the fingerprint ID.';
        }
        if ($missingFingerprintSkippedRows !== []) {
            $skipMessages[] = count($missingFingerprintSkippedRows).' row'.(count($missingFingerprintSkippedRows) === 1 ? '' : 's').' skipped because the export has no fingerprint ID.';
        }
        if ($skipMessages !== []) {
            $prefix = $rows === [] && $pending === 0
                ? 'No eligible D.T.R. records were imported.'
                : rtrim((string) ($result['message'] ?? 'D.T.R. import completed.'));
            $result['message'] = $prefix.' '.implode(' ', $skipMessages);
        }
        if (($result['conflicts'] ?? 0) > 0) {
            $result['message'] .= ' Review '.$result['conflicts'].' existing session conflict(s); their saved entries were preserved.';
        }

        return $result;
    }

    public function archivedRowDetails(string $binary, array $export, array $result): array
    {
        $records = $this->verifiedRecords($binary, $export);
        $notSubmittedRows = $this->preImportSkippedRows($result);
        $attemptedRecords = [];
        foreach ($records as $index => $record) {
            if (! isset($notSubmittedRows[$index + 1]) && ! $this->isDeferred($record)) {
                $attemptedRecords[] = $record;
            }
        }

        return $this->issueRowDetails($attemptedRecords, $result);
    }

    public function archivedRecordsPage(string $binary, array $export, int $page, int $perPage = 25, array $result = []): array
    {
        $records = $this->verifiedRecords($binary, $export);
        $skippedExportRows = $this->skippedExportRows($result);
        $inactiveSkippedRows = array_fill_keys(array_map('intval', $result['inactive_skipped_rows'] ?? []), true);
        $unmatchedSkippedRows = array_fill_keys(array_map('intval', $result['unmatched_fingerprint_skipped_rows'] ?? []), true);
        $missingFingerprintSkippedRows = array_fill_keys(array_map('intval', $result['missing_fingerprint_skipped_rows'] ?? []), true);
        $importSkippedRows = array_fill_keys(array_map('intval', $result['import_skipped_export_rows'] ?? []), true);
        $notSubmittedRows = $this->preImportSkippedRows($result);
        $skipReasons = $result['skipped_row_reasons'] ?? [];
        $attemptedRow = 0;
        $start = ($page - 1) * $perPage;
        $rows = [];

        foreach ($records as $index => $record) {
            $sourceRow = $index + 1;
            $isSkipped = isset($skippedExportRows[$sourceRow]);
            $inactiveSkipped = isset($inactiveSkippedRows[$sourceRow]);
            $unmatchedSkipped = isset($unmatchedSkippedRows[$sourceRow]);
            $missingFingerprintSkipped = isset($missingFingerprintSkippedRows[$sourceRow]);
            $importSkipped = isset($importSkippedRows[$sourceRow]);
            $notSubmitted = isset($notSubmittedRows[$sourceRow]);
            $deferred = ! $notSubmitted && ! $importSkipped && $this->isDeferred($record);
            $wasSubmitted = ! $notSubmitted && ! $this->isDeferred($record);
            if ($wasSubmitted) {
                $attemptedRow++;
            }
            if ($index >= $start && $index < $start + $perPage) {
                $rows[] = [
                    'source_row' => $sourceRow,
                    'import_row' => $wasSubmitted ? $attemptedRow : null,
                    'deferred' => $deferred,
                    'inactive_skipped' => $inactiveSkipped,
                    'unmatched_skipped' => $unmatchedSkipped,
                    'missing_fingerprint_skipped' => $missingFingerprintSkipped,
                    'import_skipped' => $importSkipped,
                    'skipped' => $isSkipped,
                    'skip_reason' => $skipReasons[$sourceRow] ?? $skipReasons[(string) $sourceRow] ?? null,
                    'record' => $record,
                ];
            }
        }

        return ['total' => count($records), 'rows' => $rows];
    }

    public function savedRejectedRecordsPage(array $rowDetails, int $recordCount, int $pending, int $page, int $perPage = 25): ?array
    {
        // Importer row numbers match export row numbers only when no provisional row was deferred.
        // Never present a partial error snapshot as the complete device export.
        if ($recordCount < 1 || $pending !== 0 || count($rowDetails) !== $recordCount) {
            return null;
        }

        $rows = [];
        for ($number = 1; $number <= $recordCount; $number++) {
            if (! is_array($rowDetails[$number] ?? null)) {
                return null;
            }
            if ($number > ($page - 1) * $perPage && $number <= $page * $perPage) {
                $rows[] = ['source_row' => $number, 'import_row' => $number, 'deferred' => false, 'inactive_skipped' => false, 'record' => $rowDetails[$number]];
            }
        }

        return ['total' => $recordCount, 'rows' => $rows];
    }

    private function verifiedRecords(string $binary, array $export): array
    {
        if (strlen($binary) > config('biometrics.max_bytes') || strlen($binary) !== (int) ($export['size'] ?? -1)
            || ! hash_equals((string) ($export['sha256'] ?? ''), hash('sha256', $binary))) {
            throw new RuntimeException('Export checksum or size verification failed.');
        }
        $records = app(BiometricDtrBinCodec::class)->decode($binary);
        if (count($records) !== (int) ($export['record_count'] ?? -1) || count($records) > config('biometrics.max_records') || ! $records) {
            throw new RuntimeException('Export record count is invalid.');
        }

        return $records;
    }

    private function isDeferred(array $record): bool
    {
        $absent = strtolower(trim((string) ($record['sched'] ?? ''))) === 'absent';
        $forgot = str_contains(strtolower((string) ($record['sched'] ?? '')), 'forgot');

        return ! $absent && ! $forgot && (empty($record['time_out']) || ($record['day_part'] ?? '') === 'provisional');
    }

    private function fingerprintIdentitySets(): array
    {
        $inactiveEmployees = Employee::query()->inactiveEmployment()->get(['id', 'fingerprint_id', 'uid']);
        $inactiveIdentities = $this->employeeFingerprintIdentities($inactiveEmployees);
        $activeEmployees = Employee::query()->activeEmployment()
            ->when($inactiveEmployees->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $inactiveEmployees->modelKeys()))
            ->get(['fingerprint_id', 'uid']);
        $activeIdentities = $this->employeeFingerprintIdentities($activeEmployees);

        return ['active' => $activeIdentities, 'inactive' => array_diff_key($inactiveIdentities, $activeIdentities)];
    }

    private function skippedExportRows(array $result): array
    {
        $skippedRows = [];
        foreach ([
            $result['inactive_skipped_rows'] ?? [],
            $result['unmatched_fingerprint_skipped_rows'] ?? [],
            $result['missing_fingerprint_skipped_rows'] ?? [],
            $result['import_skipped_export_rows'] ?? [],
            array_keys($result['skipped_row_reasons'] ?? []),
        ] as $rows) {
            foreach ($rows as $row) {
                $row = (int) $row;
                if ($row > 0) {
                    $skippedRows[$row] = true;
                }
            }
        }

        return $skippedRows;
    }

    private function preImportSkippedRows(array $result): array
    {
        $skippedRows = [];
        foreach ([
            $result['inactive_skipped_rows'] ?? [],
            $result['unmatched_fingerprint_skipped_rows'] ?? [],
            $result['missing_fingerprint_skipped_rows'] ?? [],
        ] as $rows) {
            foreach ($rows as $row) {
                $row = (int) $row;
                if ($row > 0) {
                    $skippedRows[$row] = true;
                }
            }
        }

        return $skippedRows;
    }

    private function employeeFingerprintIdentities(iterable $employees): array
    {
        $identities = [];
        foreach ($employees as $employee) {
            $identity = $this->canonicalFingerprintIdentity($employee->fingerprint_id ?: $employee->uid);
            if ($identity !== '') {
                $identities[$identity] = true;
            }
        }

        return $identities;
    }

    private function canonicalFingerprintIdentity(mixed $value): string
    {
        if (! is_int($value) && ! is_string($value)) {
            return '';
        }
        $value = trim((string) $value);

        return $value === '' ? '' : (ctype_digit($value) ? (ltrim($value, '0') ?: '0') : mb_strtolower($value));
    }

    private function issueRowDetails(array $records, array $result): array
    {
        $attemptedRecords = [];
        foreach ($records as $record) {
            if (! $this->isDeferred($record)) {
                $attemptedRecords[] = $record;
            }
        }

        $details = [];
        foreach (array_merge($result['errors'] ?? [], $result['notices'] ?? []) as $issue) {
            $row = (int) ($issue['row'] ?? 0);
            if ($row > 0 && isset($attemptedRecords[$row - 1])) {
                $details[$row] = $attemptedRecords[$row - 1];
            }
        }

        return $details;
    }
}
