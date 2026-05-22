<?php

namespace App\Filament\Imports;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\Holiday;
use App\Services\DtrCalculator;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class DtrImporter extends Importer
{
    protected static ?string $model = Dtr::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('batch_id')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:191']),

            ImportColumn::make('payroll_period_id')
                ->requiredMapping()
                ->guess(['Period ID', 'period_id'])
                ->castStateUsing(fn ($state) => self::parseInteger($state))
                ->rules(['required', 'integer']),

            // ImportColumn::make('sample')
            //     ->requiredMapping()
            //     ->rules(['required', 'integer']),

            ImportColumn::make('branch_id')
                ->requiredMapping()
                ->guess(['Branch ID', 'branch_id'])
                ->castStateUsing(fn ($state) => self::parseInteger($state))
                ->rules(['required', 'exists:branches,id']),

            ImportColumn::make('fingerprint_id')
                ->requiredMapping()
                ->guess(['Fingerprint ID', 'uid', 'employee_id', 'employee_uid'])
                ->rules(['required', 'integer']),

            ImportColumn::make('date_in')
                ->requiredMapping()
                ->castStateUsing(fn ($state) => self::parseDate($state))
                ->rules(['required', 'date']),

            ImportColumn::make('time_in')
                ->requiredMapping()
                ->castStateUsing(fn ($state) => self::parseTime($state))
                ->rules(['required', 'date_format:H:i:s']),

            ImportColumn::make('date_out')
                ->castStateUsing(fn ($state) => self::parseDate($state))
                ->rules(['nullable', 'date']),

            ImportColumn::make('time_out')
                ->castStateUsing(fn ($state) => self::parseTime($state))
                ->rules(['nullable', 'date_format:H:i:s']),

            ImportColumn::make('schedule_type')
                ->requiredMapping()
                ->guess(['Schedule Type', 'sched', 'schedule'])
                ->rules(['required']),

            ImportColumn::make('schedule_start')
                ->guess(['Schedule Start', 'sched_start'])
                ->castStateUsing(fn ($state) => self::parseTime($state))
                ->rules(['nullable', 'date_format:H:i:s']),

            ImportColumn::make('schedule_end')
                ->guess(['Schedule End', 'sched_end'])
                ->castStateUsing(fn ($state) => self::parseTime($state))
                ->rules(['nullable', 'date_format:H:i:s']),
        ];
    }

    public function saveRecord(): void
    {
        $importData = $this->getImportMetadata();

        $this->record->forceFill($importData);

        parent::saveRecord();

        $this->record->forceFill($importData)->saveQuietly();
    }

    public function resolveRecord(): ?Model
    {
        return new Dtr;
    }

    protected function getEmployeeDailyRate(): ?float
    {
        $dailyRate = $this->getEmployee()?->daily_rate;

        return filled($dailyRate) ? (float) $dailyRate : null;
    }

    protected function getEmployee(): ?Employee
    {
        $fingerprintId = $this->getImportedFingerprintId();

        if (blank($fingerprintId)) {
            return null;
        }

        return Employee::query()
            ->activeEmployment()
            ->where(fn ($query) => $query
                ->where('fingerprint_id', $fingerprintId)
                ->orWhere('uid', $fingerprintId))
            ->first();
    }

    protected function getImportMetadata(): array
    {
        return [
            'is_imported' => 1,
            'branch_id' => $this->getImportedBranchId(),
            'daily_rate' => $this->getEmployeeDailyRate(),
            ...$this->getHolidayData(),
            ...$this->getImportedCalculationData(),
        ];
    }

    protected function getImportedFingerprintId(): mixed
    {
        return $this->data['fingerprint_id'] ?? $this->record?->fingerprint_id;
    }

    protected function getImportedDateIn(): mixed
    {
        return $this->data['date_in'] ?? $this->record?->date_in;
    }

    protected function getImportedBranchId(): mixed
    {
        return $this->data['branch_id'] ?? $this->record?->branch_id ?? $this->getEmployee()?->branch_id;
    }

    protected function getHolidayData(): array
    {
        $dateIn = $this->getImportedDateIn();

        if (blank($dateIn)) {
            return [
                'is_holiday' => 0,
                'holiday_id' => null,
                'holiday_type' => null,
                'holiday_rate' => null,
            ];
        }

        $holiday = Holiday::query()
            ->with('type')
            ->whereDate('date', Carbon::parse($dateIn)->toDateString())
            ->first();

        if (! $holiday) {
            return [
                'is_holiday' => 0,
                'holiday_id' => null,
                'holiday_type' => null,
                'holiday_rate' => null,
            ];
        }

        return [
            'is_holiday' => 1,
            'holiday_id' => $holiday->id,
            'holiday_type' => $holiday->type?->type,
            'holiday_rate' => $holiday->type?->rate,
        ];
    }

    protected function getImportedCalculationData(): array
    {
        $scheduleType = $this->normalizeScheduleType($this->data['schedule_type'] ?? null);

        if ($scheduleType === 'Forgot to Punch') {
            return [
                'schedule_type' => $scheduleType,
                'schedule_start' => null,
                'schedule_end' => null,
                ...$this->emptyCalculationData(isAbsent: true),
            ];
        }

        foreach (['date_in', 'time_in'] as $key) {
            if (blank($this->data[$key] ?? null)) {
                return $this->emptyCalculationData();
            }
        }

        $actualIn = Carbon::parse("{$this->data['date_in']} {$this->data['time_in']}");
        $actualOut = filled($this->data['date_out'] ?? null) && filled($this->data['time_out'] ?? null)
            ? Carbon::parse("{$this->data['date_out']} {$this->data['time_out']}")
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
                'schedule_start' => $this->data['schedule_start'] ?? '00:00:00',
                'schedule_end' => $this->data['schedule_end'] ?? '00:00:00',
                ...app(DtrCalculator::class)->calculate(
                    dateIn: $this->data['date_in'],
                    timeIn: $this->data['time_in'],
                    dateOut: $this->data['date_out'],
                    timeOut: $this->data['time_out'],
                    scheduleStart: null,
                    scheduleEnd: null,
                    scheduleType: $scheduleType,
                    overtimeOnly: true,
                ),
                'is_absent' => false,
            ];
        }

        $scheduleStartValue = $this->getScheduleStartValue($scheduleType);
        $scheduleEndValue = $this->getScheduleEndValue($scheduleType);

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
                dateIn: $this->data['date_in'],
                timeIn: $this->data['time_in'],
                dateOut: $this->data['date_out'],
                timeOut: $this->data['time_out'],
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

    protected function getScheduleStartValue(string $scheduleType): ?string
    {
        if ($scheduleType === 'Saturday') {
            return $this->data['schedule_start'] ?? $this->getBranch()?->reg_sched_start ?? '08:00:00';
        }

        return $this->data['schedule_start'] ?? null;
    }

    protected function getScheduleEndValue(string $scheduleType): ?string
    {
        if ($scheduleType === 'Saturday') {
            return '11:00:00';
        }

        return $this->data['schedule_end'] ?? null;
    }

    protected function getScheduleStartColumn(string $scheduleType): ?string
    {
        return in_array($scheduleType, ['Regular', 'Saturday'], true)
            ? 'reg_sched_start'
            : null;
    }

    protected function getBranch(): ?Branch
    {
        $branchId = $this->getImportedBranchId();

        return filled($branchId) ? Branch::query()->find($branchId) : null;
    }

    protected function usesRegularSchedule(string $scheduleType): bool
    {
        return str($scheduleType)
            ->lower()
            ->contains('regular');
    }

    protected static function parseInteger($state): ?int
    {
        $state = self::cleanSpreadsheetValue($state);

        if (blank($state)) {
            return null;
        }

        if (! is_numeric($state)) {
            return null;
        }

        return (int) $state;
    }

    protected static function parseDate(?string $state): ?string
    {
        $state = self::cleanSpreadsheetValue($state);

        if (blank($state)) {
            return null;
        }

        try {
            return Carbon::parse($state)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected static function parseTime($state): ?string
    {
        $state = self::cleanSpreadsheetValue($state);

        if (blank($state)) {
            return null;
        }

        // Excel numeric time support
        if (is_numeric($state)) {
            return Carbon::createFromTimestamp(($state - 25569) * 86400)
                ->second(0)
                ->format('H:i:s');
        }

        try {
            return Carbon::parse($state)->second(0)->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected static function cleanSpreadsheetValue($state)
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

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Imported '.Number::format($import->successful_rows).' rows.';

        if ($failed = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failed).' failed.';
        }

        return $body;
    }
}
