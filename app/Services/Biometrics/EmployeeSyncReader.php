<?php

namespace App\Services\Biometrics;

use RuntimeException;

class EmployeeSyncReader
{
    public function read(string $binary, array $export): array
    {
        if (strlen($binary) > config('biometrics.max_bytes') || strlen($binary) !== (int) ($export['size'] ?? -1)
            || ! hash_equals((string) ($export['sha256'] ?? ''), hash('sha256', $binary))) {
            throw new RuntimeException('Employee export checksum or size verification failed.');
        }

        $records = app(BiometricDtrBinCodec::class)->decode($binary);
        if (count($records) !== (int) ($export['record_count'] ?? -1) || count($records) > config('biometrics.max_records')) {
            throw new RuntimeException('Employee export record count is invalid.');
        }

        $employees = [];
        $ids = [];
        foreach ($records as $record) {
            $id = $record['id'] ?? null;
            $uid = $record['uid'] ?? null;
            $fingerprints = $record['fps'] ?? null;
            if (! is_int($id) || $id < 1 || isset($ids[$id]) || ! is_int($uid) || $uid < 1
                || ! is_string($record['name'] ?? null) || trim($record['name']) === ''
                || ! is_string($record['type'] ?? null) || ! is_string($record['sched'] ?? null)
                || ! is_string($record['created'] ?? null) || ! is_array($fingerprints) || count($fingerprints) !== 3
                || ! is_int($record['fp_count'] ?? null) || $record['fp_count'] !== count(array_filter($fingerprints, fn ($fingerprint) => $fingerprint > 0))
                || collect($fingerprints)->contains(fn ($fingerprint) => ! is_int($fingerprint) || $fingerprint < 0 || $fingerprint > 255)) {
                throw new RuntimeException('Employee export contains an invalid record.');
            }
            $ids[$id] = true;
            $employees[] = [
                'id' => $id,
                'uid' => $uid,
                'name' => trim($record['name']),
                'type' => $record['type'],
                'schedule' => $record['sched'],
                'fingerprint_ids' => array_values(array_filter($fingerprints, fn ($fingerprint) => $fingerprint > 0)),
                'fingerprint_count' => $record['fp_count'],
                'created' => $record['created'],
            ];
        }

        return [
            'employee_count' => count($employees),
            'employees' => $employees,
            'export' => $export,
            'message' => count($employees).' device employee'.(count($employees) === 1 ? '' : 's').' synchronized. HRIS employee records were not changed.',
        ];
    }
}
