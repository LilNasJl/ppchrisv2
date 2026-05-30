<?php

namespace App\Services\Imports;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Services\DtrCalculator;
use App\Services\HolidayEntitlementService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DtrImportService
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{total:int, successful:int, failed:int, batch_id:string, errors:array<int, array<string, mixed>>}
     */
    public function importRows(array $rows, string $importName, ?string $fallbackBatchId = null): array
    {
        $fallbackBatchId = $this->normalizeNullableString($fallbackBatchId) ?: Str::random(12);

        $result = [
            'total' => count($rows),
            'successful' => 0,
            'failed' => 0,
            'batch_id' => $fallbackBatchId,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;

            try {
                $this->importRow(is_array($row) ? $row : [], $importName, $fallbackBatchId);
                $result['successful']++;
            } catch (ValidationException $exception) {
                $result['failed']++;
                $result['errors'][] = [
                    'row' => $rowNumber,
                    'message' => collect($exception->errors())->flatten()->implode(' '),
                ];
            } catch (\Throwable $exception) {
                report($exception);

                $result['failed']++;
                $result['errors'][] = [
                    'row' => $rowNumber,
                    'message' => $exception->getMessage() ?: 'Unable to import D.T.R row.',
                ];
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{id:int, batch_id:?string}
     */
    public function importRow(array $row, string $importName, string $fallbackBatchId): array
    {
        $data = $this->normalizeRow($row, $fallbackBatchId);

        $validator = Validator::make($data, [
            'batch_id' => ['required', 'string', 'max:191'],
            'payroll_period_id' => ['required', 'integer'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'fingerprint_id' => ['required', 'integer'],
            'date_in' => ['required', 'date'],
            'time_in' => ['required', 'date_format:H:i:s'],
            'date_out' => ['nullable', 'date'],
            'time_out' => ['nullable', 'date_format:H:i:s'],
            'schedule_type' => ['required', 'string', 'max:191'],
            'schedule_start' => ['nullable', 'date_format:H:i:s'],
            'schedule_end' => ['nullable', 'date_format:H:i:s'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return DB::transaction(function () use ($data, $importName): array {
            $record = new Dtr;

            $record->forceFill([
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
                ...$this->getImportMetadata($data, $importName),
            ])->save();

            return [
                'id' => $record->id,
                'batch_id' => $record->batch_id,
            ];
        });
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
            'branch_id' => $this->getImportedBranchId($data),
            'daily_rate' => $this->getEmployeeDailyRate($data),
            ...$this->getHolidayData($data),
            ...$this->getImportedCalculationData($data),
        ];
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

        $fingerprintId = (string) $fingerprintId;
        $paddedUid = is_numeric($fingerprintId)
            ? str_pad((string) ((int) $fingerprintId), 4, '0', STR_PAD_LEFT)
            : $fingerprintId;

        return Employee::query()
            ->activeEmployment()
            ->where(fn ($query) => $query
                ->where('fingerprint_id', $fingerprintId)
                ->orWhere('uid', $fingerprintId)
                ->orWhere('uid', $paddedUid))
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function getImportedBranchId(array $data): mixed
    {
        return $data['branch_id'] ?? $this->getEmployee($data)?->branch_id;
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
        $scheduleType = $this->normalizeScheduleType($data['schedule_type'] ?? null);

        if ($scheduleType === 'Forgot to Punch') {
            return [
                'schedule_type' => $scheduleType,
                'schedule_start' => null,
                'schedule_end' => null,
                ...$this->emptyCalculationData(isAbsent: true),
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
                'schedule_start' => $data['schedule_start'] ?? '00:00:00',
                'schedule_end' => $data['schedule_end'] ?? '00:00:00',
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
                ...$this->emptyCalculationData(),
            ];
        }

        return [
            'schedule_type' => $scheduleType,
            'schedule_start' => $scheduleStartValue,
            'schedule_end' => $scheduleEndValue,
            ...app(DtrCalculator::class)->calculate(
                dateIn: $data['date_in'],
                timeIn: $data['time_in'],
                dateOut: $data['date_out'],
                timeOut: $data['time_out'],
                scheduleStart: $scheduleStartValue,
                scheduleEnd: $scheduleEndValue,
                scheduleStartColumn: $this->getScheduleStartColumn($scheduleType),
                scheduleType: $scheduleType,
            ),
            'is_absent' => false,
        ];
    }

    protected function emptyCalculationData(bool $isAbsent = false): array
    {
        return app(DtrCalculator::class)->emptyCalculationData($isAbsent);
    }

    protected function normalizeScheduleType(?string $scheduleType): string
    {
        $value = str($scheduleType ?? 'Regular')->lower();

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
    protected function getScheduleStartValue(array $data, string $scheduleType): ?string
    {
        if ($scheduleType === 'Saturday') {
            return $data['schedule_start'] ?? $this->getBranch($data)?->reg_sched_start ?? '08:00:00';
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
            return Carbon::createFromTimestamp(((float) $state - 25569) * 86400)
                ->second(0)
                ->format('H:i:s');
        }

        try {
            return Carbon::parse($state)->second(0)->format('H:i:s');
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
