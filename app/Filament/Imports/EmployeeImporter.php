<?php

namespace App\Filament\Imports;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class EmployeeImporter extends Importer
{
    private const WORK_DAYS_PER_MONTH = 26;

    protected static ?string $model = User::class;

    protected ?string $temporaryUsername = null;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('lastname')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:191']),

            ImportColumn::make('firstname')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:191']),

            ImportColumn::make('middlename')
                ->rules(['nullable', 'string', 'max:191']),

            ImportColumn::make('birthdate')
                ->label('Birthdate')
                ->guess(['birthdate', 'birth date'])
                ->castStateUsing(fn ($state): ?string => self::parseDate($state))
                ->rules(['nullable', 'date']),

            ImportColumn::make('gender')
                ->castStateUsing(fn ($state): ?string => self::normalizeLowerString($state))
                ->rules(['nullable', 'in:male,female']),

            ImportColumn::make('status')
                ->label('Civil Status')
                ->guess(['civil status', 'civil_status', 'status'])
                ->castStateUsing(fn ($state): ?string => self::normalizeNullableString($state))
                ->rules(['nullable', 'string', 'max:191']),

            ImportColumn::make('hired_date')
                ->label('Hired Date')
                ->guess(['hired date', 'hired_date'])
                ->castStateUsing(fn ($state): ?string => self::parseDate($state))
                ->rules(['nullable', 'date']),

            ImportColumn::make('designation')
                ->rules(['nullable', 'string', 'max:191']),

            ImportColumn::make('branch')
                ->rules(['nullable', 'string', 'max:191']),

            ImportColumn::make('department')
                ->rules(['nullable', 'string', 'max:191']),

            ImportColumn::make('kids')
                ->label('No. of Kids')
                ->guess(['no. of kids', 'no of kids', 'kids'])
                ->castStateUsing(fn ($state): int => self::parseInteger($state) ?? 0)
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('address')
                ->label('Permanent Address')
                ->guess(['permanent address', 'permanent_address', 'address'])
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('gsis')
                ->label('GSIS')
                ->rules(['nullable', 'string', 'max:191']),

            ImportColumn::make('philhealth')
                ->label('PhilHealth')
                ->rules(['nullable', 'string', 'max:191']),

            ImportColumn::make('tin')
                ->label('TIN')
                ->rules(['nullable', 'string', 'max:191']),

            ImportColumn::make('sss')
                ->label('SSS')
                ->rules(['nullable', 'string', 'max:191']),

            ImportColumn::make('bank_id_no')
                ->label('Bank ID No.')
                ->guess(['bankid', 'bank id', 'bank_id', 'bank id no', 'bank_id_no'])
                ->rules(['nullable', 'string', 'max:191']),

            ImportColumn::make('fingerprint_id')
                ->label('Fingerprint')
                ->guess(['fingerprint', 'fingerprint id', 'fingerprint_id'])
                ->rules(['nullable', 'string', 'max:191']),

            ImportColumn::make('employment_type')
                ->label('Employment Type')
                ->guess(['employment type', 'employment_type'])
                ->rules(['nullable', 'string', 'max:191']),

            ImportColumn::make('school_name')
                ->label('School Name')
                ->guess(['school name', 'school_name'])
                ->rules(['nullable', 'string', 'max:191']),

            ImportColumn::make('school_level')
                ->label('Highest Level Attended')
                ->guess(['highest level attended', 'school_level'])
                ->rules(['nullable', 'string', 'max:191']),

            ImportColumn::make('year_grad')
                ->label('Year Graduated')
                ->guess(['year graduated', 'year_grad'])
                ->castStateUsing(fn ($state): ?string => self::parseGraduationDate($state))
                ->rules(['nullable', 'date']),

            ImportColumn::make('rate_type')
                ->label('Rate Type')
                ->guess(['rate type', 'rate_type'])
                ->castStateUsing(fn ($state): ?string => self::normalizeLowerString($state))
                ->rules(['nullable', 'in:daily,monthly']),

            ImportColumn::make('payment_type')
                ->label('Payment Type')
                ->guess(['payment type', 'payment_type'])
                ->castStateUsing(fn ($state): ?string => self::normalizeLowerString($state))
                ->rules(['nullable', 'in:cash,atm']),

            ImportColumn::make('daily_rate')
                ->label('Daily Rate')
                ->guess(['daily rate', 'daily_rate'])
                ->castStateUsing(fn ($state): ?float => self::parseDecimal($state))
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('monthly_rate')
                ->label('Basic Monthly')
                ->guess(['basic monthly', 'basic_monthly', 'monthly rate', 'monthly_rate'])
                ->castStateUsing(fn ($state): ?float => self::parseDecimal($state))
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('email')
                ->castStateUsing(fn ($state): ?string => self::normalizeEmail($state))
                ->rules(['nullable', 'email', 'max:191', 'unique:users,email']),
        ];
    }

    public function resolveRecord(): ?Model
    {
        return new User;
    }

    public function fillRecord(): void
    {
        $this->temporaryUsername = 'import_'.Str::lower((string) Str::ulid());

        $this->record->forceFill([
            'name' => $this->temporaryUsername,
            'username' => $this->temporaryUsername,
            'email' => $this->data['email'] ?? null,
            'password' => Hash::make('PASSWORD1.'),
            'role' => 'employee',
            'is_disabled' => false,
        ]);
    }

    public function saveRecord(): void
    {
        DB::transaction(function (): void {
            $this->record->save();

            $employee = Employee::create($this->employeeData());
            $username = User::companyUsernameFromUid($employee->uid);

            $this->record->forceFill([
                'name' => $username,
                'username' => $username,
            ])->save();
        });
    }

    protected function employeeData(): array
    {
        $salary = $this->salaryData();

        return [
            'user_id' => $this->record->id,
            'firstname' => $this->data['firstname'] ?? null,
            'middlename' => $this->data['middlename'] ?? null,
            'lastname' => $this->data['lastname'] ?? null,
            'gender' => $this->data['gender'] ?? null,
            'birthdate' => $this->data['birthdate'] ?? null,
            'status' => $this->data['status'] ?? null,
            'hired_date' => $this->data['hired_date'] ?? null,
            'designation_id' => $this->designationId($this->data['designation'] ?? null),
            'branch_id' => $this->branchId($this->data['branch'] ?? null),
            'department_id' => $this->departmentId($this->data['department'] ?? null),
            'kids' => $this->data['kids'] ?? 0,
            'address' => $this->data['address'] ?? null,
            'gsis' => $this->data['gsis'] ?? null,
            'philhealth' => $this->data['philhealth'] ?? null,
            'tin' => $this->data['tin'] ?? null,
            'sss' => $this->data['sss'] ?? null,
            'bank_id_no' => $this->data['bank_id_no'] ?? null,
            'fingerprint_id' => $this->data['fingerprint_id'] ?? null,
            'employment_type' => $this->data['employment_type'] ?? null,
            'school_name' => $this->data['school_name'] ?? null,
            'school_level' => $this->data['school_level'] ?? null,
            'year_grad' => $this->data['year_grad'] ?? null,
            'rate_type' => $salary['rate_type'],
            'payment_type' => $this->data['payment_type'] ?? null,
            'daily_rate' => $salary['daily_rate'],
            'monthly_rate' => $salary['monthly_rate'],
            'email' => $this->data['email'] ?? null,
            'allowance' => 0,
            'salary_adjustment' => 0,
        ];
    }

    protected function salaryData(): array
    {
        $dailyRate = $this->data['daily_rate'] ?? null;
        $monthlyRate = $this->data['monthly_rate'] ?? null;
        $rateType = $this->data['rate_type'] ?? null;

        if (filled($dailyRate) && blank($monthlyRate)) {
            return [
                'rate_type' => 'daily',
                'daily_rate' => $dailyRate,
                'monthly_rate' => null,
            ];
        }

        if (blank($dailyRate) && filled($monthlyRate)) {
            return [
                'rate_type' => 'monthly',
                'daily_rate' => $this->dailyRateFromMonthly($monthlyRate),
                'monthly_rate' => $monthlyRate,
            ];
        }

        if ($rateType === 'daily') {
            return [
                'rate_type' => 'daily',
                'daily_rate' => $dailyRate,
                'monthly_rate' => null,
            ];
        }

        return [
            'rate_type' => 'monthly',
            'daily_rate' => $this->dailyRateFromMonthly($monthlyRate),
            'monthly_rate' => $monthlyRate,
        ];
    }

    protected function dailyRateFromMonthly(mixed $monthlyRate): ?float
    {
        $monthlyRate = filled($monthlyRate) ? (float) $monthlyRate : 0.0;

        return $monthlyRate > 0
            ? round($monthlyRate / self::WORK_DAYS_PER_MONTH, 2)
            : null;
    }

    protected function designationId(?string $title): ?int
    {
        $title = self::normalizeNullableString($title);

        if (blank($title)) {
            return null;
        }

        $designation = Designation::query()
            ->where('title', $title)
            ->first();

        if ($designation) {
            return $designation->id;
        }

        $designation = new Designation;
        $designation->forceFill([
            'title' => $title,
            'description' => 'Imported',
        ])->save();

        return $designation->id;
    }

    protected function departmentId(?string $name): ?int
    {
        $name = self::normalizeNullableString($name);

        if (blank($name)) {
            return null;
        }

        $department = Department::query()
            ->where('name', $name)
            ->first();

        if ($department) {
            return $department->id;
        }

        $department = new Department;
        $department->forceFill([
            'name' => $name,
            'description' => 'Imported',
        ])->save();

        return $department->id;
    }

    protected function branchId(?string $name): ?int
    {
        $name = self::normalizeNullableString($name);

        if (blank($name)) {
            return null;
        }

        $branch = Branch::query()
            ->where('branch_name', $name)
            ->first();

        if ($branch) {
            return $branch->id;
        }

        $branch = new Branch;
        $branch->forceFill([
            'branch_name' => $name,
            'branch_address' => 'Imported',
            'mobile_no' => null,
            'employee_id' => null,
        ])->save();

        return $branch->id;
    }

    protected static function parseDate(mixed $state): ?string
    {
        $state = self::normalizeNullableString($state);

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

    protected static function parseGraduationDate(mixed $state): ?string
    {
        $state = self::normalizeNullableString($state);

        if (blank($state)) {
            return null;
        }

        if (preg_match('/^\d{4}$/', $state)) {
            return "{$state}-01-01";
        }

        return self::parseDate($state);
    }

    protected static function parseDecimal(mixed $state): ?float
    {
        $state = self::normalizeNullableString($state);

        if (blank($state)) {
            return null;
        }

        $state = str_replace([',', '₱', 'PHP', 'php'], '', $state);

        return is_numeric($state) ? (float) $state : null;
    }

    protected static function parseInteger(mixed $state): ?int
    {
        $state = self::normalizeNullableString($state);

        return is_numeric($state) ? (int) $state : null;
    }

    protected static function normalizeLowerString(mixed $state): ?string
    {
        $state = self::normalizeNullableString($state);

        return filled($state) ? strtolower($state) : null;
    }

    protected static function normalizeEmail(mixed $state): ?string
    {
        $state = self::normalizeNullableString($state);

        return filled($state) ? strtolower($state) : null;
    }

    protected static function normalizeNullableString(mixed $state): ?string
    {
        if (! is_string($state)) {
            return filled($state) ? (string) $state : null;
        }

        $state = trim($state);

        return filled($state) ? $state : null;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Imported '.Number::format($import->successful_rows).' employee accounts.';

        if ($failed = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failed).' failed.';
        }

        return $body;
    }
}
