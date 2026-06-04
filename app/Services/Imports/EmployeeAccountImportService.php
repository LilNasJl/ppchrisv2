<?php

namespace App\Services\Imports;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeAccountImportService
{
    private const WORK_DAYS_PER_MONTH = 26;

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{total:int, successful:int, failed:int, errors:array<int, array<string, mixed>>}
     */
    public function importRows(array $rows, string $batchId, ?string $importName = null): array
    {
        $result = [
            'total' => count($rows),
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;

            try {
                $this->importRow(is_array($row) ? $row : [], $rowNumber, $batchId, $importName);
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
                    'message' => $exception->getMessage() ?: 'Unable to import employee.',
                ];
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{user_id:int, employee_id:int, username:?string}
     */
    public function importRow(array $row, int $rowNumber, ?string $batchId = null, ?string $importName = null): array
    {
        $data = $this->normalizeRow($row);

        $validator = Validator::make($data, [
            'employee_id' => ['required', 'string', 'max:20', Rule::unique('employees', 'uid')],
            'lastname' => ['required', 'string', 'max:191'],
            'firstname' => ['required', 'string', 'max:191'],
            'middlename' => ['nullable', 'string', 'max:191'],
            'birthdate' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female'],
            'status' => ['nullable', 'string', 'max:191'],
            'hired_date' => ['nullable', 'date'],
            'designation' => ['nullable', 'string', 'max:191'],
            'branch' => ['nullable', 'string', 'max:191'],
            'department' => ['nullable', 'string', 'max:191'],
            'address' => ['nullable', 'string', 'max:255'],
            'pagibig' => ['nullable', 'string', 'max:191'],
            'philhealth' => ['nullable', 'string', 'max:191'],
            'tin' => ['nullable', 'string', 'max:191'],
            'sss' => ['nullable', 'string', 'max:191'],
            'bank_id_no' => ['nullable', 'string', 'max:191'],
            'fingerprint_id' => ['nullable', 'string', 'max:191'],
            'employment_type' => ['nullable', 'string', 'max:191'],
            'rate_type' => ['nullable', 'in:daily,monthly'],
            'payment_type' => ['nullable', 'in:cash,atm'],
            'daily_rate' => ['nullable', 'numeric', 'min:0'],
            'monthly_rate' => ['nullable', 'numeric', 'min:0'],
        ], [
            'employee_id.required' => 'The employee_id / company ID is required. Example: PF-0001.',
            'employee_id.unique' => 'The employee_id / company ID already exists.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return DB::transaction(function () use ($data, $batchId, $importName): array {
            $temporaryUsername = 'import_'.Str::lower((string) Str::ulid());

            $user = new User;
            $user->forceFill([
                'name' => $temporaryUsername,
                'username' => $temporaryUsername,
                'email' => null,
                'password' => Hash::make('PASSWORD1.'),
                'role' => 'employee',
                'is_disabled' => false,
            ])->save();

            $employee = Employee::create($this->employeeData($user, $data, $batchId, $importName));
            $username = User::companyUsernameFromUid($employee->uid);

            $user->forceFill([
                'name' => $username,
                'username' => $username,
            ])->save();

            return [
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'username' => $username,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeRow(array $row): array
    {
        return [
            'employee_id' => Employee::normalizeUid($this->pick($row, ['employee_id', 'employee id', 'company_id', 'company id', 'id no', 'id_no'])),
            'lastname' => $this->normalizeNullableString($this->pick($row, ['lastname', 'last name', 'last_name', 'surname'])),
            'firstname' => $this->normalizeNullableString($this->pick($row, ['firstname', 'first name', 'first_name', 'given name'])),
            'middlename' => $this->normalizeNullableString($this->pick($row, ['middlename', 'middle name', 'middle_name'])),
            'birthdate' => $this->parseDate($this->pick($row, ['birthdate', 'birth date', 'birth_date'])),
            'gender' => $this->normalizeLowerString($this->pick($row, ['gender', 'sex'])),
            'status' => $this->normalizeNullableString($this->pick($row, ['status', 'civil status', 'civil_status'])),
            'hired_date' => $this->parseDate($this->pick($row, ['hired_date', 'hired date', 'date hired'])),
            'designation' => $this->normalizeNullableString($this->pick($row, ['designation', 'position', 'title'])),
            'branch' => $this->normalizeNullableString($this->pick($row, ['branch', 'branch name', 'branch_name'])),
            'department' => $this->normalizeNullableString($this->pick($row, ['department', 'department name', 'department_name'])),
            'address' => $this->normalizeNullableString($this->pick($row, ['address', 'permanent address', 'permanent_address'])),
            'pagibig' => $this->normalizeNullableString($this->pick($row, ['pagibig', 'pagibig no', 'pagibig_no', 'hdmf', 'hdmf no', 'hdmf_no'])),
            'philhealth' => $this->normalizeNullableString($this->pick($row, ['philhealth', 'philhealth no', 'philhealth_no'])),
            'tin' => $this->normalizeNullableString($this->pick($row, ['tin', 'tin no', 'tin_no'])),
            'sss' => $this->normalizeNullableString($this->pick($row, ['sss', 'sss no', 'sss_no'])),
            'bank_id_no' => $this->normalizeNullableString($this->pick($row, ['bank_id', 'bank_id_no', 'bank id no', 'bank id', 'bankid'])),
            'fingerprint_id' => $this->normalizeNullableString($this->pick($row, ['fingerprint_id', 'fingerprint id', 'fingerprint', 'fp'])),
            'employment_type' => $this->normalizeNullableString($this->pick($row, ['employment_type', 'employment type'])),
            'rate_type' => $this->normalizeLowerString($this->pick($row, ['rate_type', 'rate type'])),
            'payment_type' => $this->normalizeLowerString($this->pick($row, ['payment_type', 'payment type'])),
            'daily_rate' => $this->parseDecimal($this->pick($row, ['daily_rate', 'daily rate'])),
            'monthly_rate' => $this->parseDecimal($this->pick($row, ['monthly_rate', 'monthly rate', 'basic monthly', 'basic_monthly'])),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function employeeData(User $user, array $data, ?string $batchId = null, ?string $importName = null): array
    {
        $salary = $this->salaryData($data);

        return [
            'user_id' => $user->id,
            'employee_import_batch_id' => $batchId,
            'employee_import_name' => $importName,
            'employee_imported_at' => filled($batchId) ? now() : null,
            'uid' => $data['employee_id'],
            'firstname' => $data['firstname'] ?? null,
            'middlename' => $data['middlename'] ?? null,
            'lastname' => $data['lastname'] ?? null,
            'gender' => $data['gender'] ?? null,
            'birthdate' => $data['birthdate'] ?? null,
            'status' => $data['status'] ?? null,
            'hired_date' => $data['hired_date'] ?? null,
            'designation_id' => $this->designationId($data['designation'] ?? null),
            'branch_id' => $this->branchId($data['branch'] ?? null),
            'department_id' => $this->departmentId($data['department'] ?? null),
            'address' => $data['address'] ?? null,
            'pagibig' => $data['pagibig'] ?? null,
            'philhealth' => $data['philhealth'] ?? null,
            'tin' => $data['tin'] ?? null,
            'sss' => $data['sss'] ?? null,
            'bank_id_no' => $data['bank_id_no'] ?? null,
            'fingerprint_id' => $data['fingerprint_id'] ?? null,
            'employment_type' => $data['employment_type'] ?? null,
            'rate_type' => $salary['rate_type'],
            'payment_type' => $data['payment_type'] ?? null,
            'daily_rate' => $salary['daily_rate'],
            'monthly_rate' => $salary['monthly_rate'],
            'allowance' => 0,
            'salary_adjustment' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{rate_type:string, daily_rate:?float, monthly_rate:?float}
     */
    protected function salaryData(array $data): array
    {
        $dailyRate = $data['daily_rate'] ?? null;
        $monthlyRate = $data['monthly_rate'] ?? null;
        $rateType = $data['rate_type'] ?? null;

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
        $title = $this->normalizeNullableString($title);

        return filled($title)
            ? Designation::query()->where('title', $title)->value('id')
            : null;
    }

    protected function departmentId(?string $name): ?int
    {
        $name = $this->normalizeNullableString($name);

        return filled($name)
            ? Department::query()->where('name', $name)->value('id')
            : null;
    }

    protected function branchId(?string $name): ?int
    {
        $name = $this->normalizeNullableString($name);

        return filled($name)
            ? Branch::query()->where('branch_name', $name)->value('id')
            : null;
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

    protected function parseDate(mixed $state): ?string
    {
        $state = $this->normalizeNullableString($state);

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

    protected function parseGraduationDate(mixed $state): ?string
    {
        $state = $this->normalizeNullableString($state);

        if (blank($state)) {
            return null;
        }

        if (preg_match('/^\d{4}$/', $state)) {
            return "{$state}-01-01";
        }

        return $this->parseDate($state);
    }

    protected function parseDecimal(mixed $state): ?float
    {
        $state = $this->normalizeNullableString($state);

        if (blank($state)) {
            return null;
        }

        $state = preg_replace('/[^0-9.\-]/', '', $state);

        return is_numeric($state) ? (float) $state : null;
    }

    protected function parseInteger(mixed $state): ?int
    {
        $state = $this->normalizeNullableString($state);

        return is_numeric($state) ? (int) $state : null;
    }

    protected function normalizeLowerString(mixed $state): ?string
    {
        $state = $this->normalizeNullableString($state);

        return filled($state) ? strtolower($state) : null;
    }

    protected function normalizeEmail(mixed $state): ?string
    {
        $state = $this->normalizeNullableString($state);

        return filled($state) ? strtolower($state) : null;
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
