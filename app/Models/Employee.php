<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Employee extends Model
{
    use HasPublicUuid, SoftDeletes;

    public const REGULAR_WORK_DAYS_PER_MONTH = 26;

    public const STATION_FIVE_LEAVE_DESIGNATIONS = [
        'FORECOURT ATTENDANT',
        'CASHIER/FORECOURT ATTENDANT',
        'CASHIER / FORECOURT ATTENDANT',
        'CASHIER/FOROCOURT ATTENDANT',
    ];

    public const STATION_SIX_LEAVE_DESIGNATIONS = self::STATION_FIVE_LEAVE_DESIGNATIONS;

    public const ENDED_EMPLOYMENT_TYPES = [
        'Resigned',
        'Terminated',
        'Force Resigned',
        'Death of Employee',
        'Death Employee',
    ];

    public const EMPLOYMENT_TYPES = [
        'Permanent',
        'Probationary',
        'Temporary',
        'Coterminous',
        'Contractual',
        'Casual',
        'Job Order',
        'Contract of Service',
        'Substitute',
        'Resigned',
        'Terminated',
        'Force Resigned',
        'Death of Employee',
    ];

    protected $fillable = [
        'user_id',
        'employee_import_batch_id',
        'employee_import_name',
        'employee_imported_at',
        'uid',
        'firstname',
        'middlename',
        'lastname',
        'gender',
        'birthdate',
        'status',
        'address',
        'mobile',
        'kids',
        'email',
        'designation_id',
        'fingerprint_id',
        'branch_id',
        'department_id',
        'hired_date',
        'employment_type',
        'schedule_type',
        'school_name',
        'school_level',
        'year_grad',
        'rate_type',
        'payment_type',
        'daily_rate',
        'monthly_rate',
        'allowance',
        'leave_credits',
        'birthday_leave_credits',
        'leave_credits_year',
        'gsis_no',
        'philhealth_no',
        'pagibig_no',
        'tin',
        'sss_no',
        'bank_id_no',
        'gsis',
        'philhealth',
        'pagibig',
        'sss',
        'salary_adjustment',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'hired_date' => 'date',
        'daily_rate' => 'decimal:2',
        'monthly_rate' => 'decimal:2',
        'allowance' => 'decimal:2',
        'salary_adjustment' => 'decimal:2',
        'leave_credits' => 'decimal:2',
        'birthday_leave_credits' => 'decimal:2',
        'employee_imported_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function ($employee): void {
            $employee->uid = self::normalizeUid($employee->uid);
            $employee->schedule_type = $employee->rate_type === 'daily'
                ? 'daily'
                : 'regular';

            if ($employee->rate_type === 'monthly') {
                $monthlyRate = (float) ($employee->monthly_rate ?? 0);
                $employee->daily_rate = $monthlyRate > 0
                    ? round($monthlyRate / self::REGULAR_WORK_DAYS_PER_MONTH, 2)
                    : null;
            } elseif ($employee->rate_type === 'daily') {
                $employee->monthly_rate = null;
            }
        });

        static::creating(function ($employee): void {
            $employee->uid ??= self::reserveNextUid();
            self::syncCounterToUid($employee->uid);

            $employee->schedule_type = $employee->rate_type === 'daily'
                ? 'daily'
                : 'regular';
            $employee->allowance ??= 0;
            $employee->salary_adjustment ??= 0;
            $employee->kids ??= 0;
            $employee->leave_credits ??= $employee->annualLeaveCredits();
            $employee->birthday_leave_credits ??= 1;
            $employee->leave_credits_year ??= now()->year;
        });
    }

    public static function normalizeUid(int|string|null $uid): ?string
    {
        if (blank($uid)) {
            return null;
        }

        $uid = preg_replace('/[^0-9]/', '', (string) $uid);

        if (blank($uid)) {
            return null;
        }

        return str_pad((string) ((int) $uid), 4, '0', STR_PAD_LEFT);
    }

    public static function reserveNextUid(): string
    {
        return DB::transaction(function (): string {
            $counter = Counter::lockForUpdate()->first();

            if (! $counter) {
                $counter = Counter::create(['uid' => 0]);
            }

            $counter->increment('uid');

            return str_pad((string) $counter->uid, 4, '0', STR_PAD_LEFT);
        });
    }

    public static function syncCounterToUid(int|string|null $uid): void
    {
        $number = (int) self::normalizeUid($uid);

        if ($number < 1) {
            return;
        }

        DB::transaction(function () use ($number): void {
            $counter = Counter::lockForUpdate()->first();

            if (! $counter) {
                Counter::create(['uid' => $number]);

                return;
            }

            if ((int) $counter->uid < $number) {
                $counter->forceFill(['uid' => $number])->save();
            }
        });
    }

    public static function companyIdFromUid(int|string|null $uid): ?string
    {
        $uid = self::normalizeUid($uid);
        if (blank($uid)) {
            return null;
        }

        return 'PF-'.$uid;
    }

    public function getCompanyIdAttribute(): ?string
    {
        return self::companyIdFromUid($this->uid);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employmentTypeChanges()
    {
        return $this->hasMany(EmployeeEmploymentTypeChange::class)
            ->orderByDesc('effective_date')
            ->orderByDesc('id');
    }

    public function latestEmploymentTypeChange()
    {
        return $this->hasOne(EmployeeEmploymentTypeChange::class)->ofMany([
            'effective_date' => 'max',
            'id' => 'max',
        ]);
    }

    public function employeeDeductions()
    {
        return $this->hasMany(EmployeeDeduction::class, 'employee_id');
    }

    public function loans()
    {
        return $this->hasMany(EmployeeLoan::class, 'employee_id');
    }

    public function loanRequests()
    {
        return $this->hasMany(EmployeeLoanRequest::class, 'employee_id');
    }

    public function payrollPeriodAdjustments()
    {
        return $this->hasMany(PayrollPeriodEmployeeAdjustment::class, 'employee_id');
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class, 'employee_id');
    }

    public function holidayExclusions()
    {
        return $this->hasMany(HolidayEmployeeExclusion::class, 'employee_id');
    }

    public function memos()
    {
        return $this->hasMany(Memo::class, 'employee_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->lastname.', '.($this->middlename ? $this->middlename.'. ' : '').$this->firstname);
    }

    public function getTenureAttribute(): string
    {
        if (blank($this->hired_date)) {
            return '0 year/s | 0 month/s | 0 day/s';
        }

        $diff = Carbon::parse($this->hired_date)->startOfDay()->diff(now()->startOfDay());

        return "{$diff->y} year/s | {$diff->m} month/s | {$diff->d} day/s";
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeActiveEmployment(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->whereNull($this->qualifyColumn('employment_type'))
                ->orWhereNotIn($this->qualifyColumn('employment_type'), self::ENDED_EMPLOYMENT_TYPES);
        });
    }

    public function hasEndedEmployment(): bool
    {
        return in_array($this->employment_type, self::ENDED_EMPLOYMENT_TYPES, true);
    }

    public static function employmentTypeOptions(): array
    {
        return array_combine(self::EMPLOYMENT_TYPES, self::EMPLOYMENT_TYPES);
    }

    public function resetLeaveCreditsIfNeeded(): void
    {
        if ((int) $this->leave_credits_year === now()->year) {
            return;
        }

        $this->forceFill([
            'leave_credits' => $this->annualLeaveCredits(),
            'birthday_leave_credits' => 1,
            'leave_credits_year' => now()->year,
        ])->saveQuietly();
    }

    public function annualLeaveCredits(): int
    {
        $designationTitle = $this->relationLoaded('designation')
            ? $this->designation?->title
            : Designation::query()->whereKey($this->designation_id)->value('title');

        $designationTitle = strtoupper(trim((string) $designationTitle));

        return in_array($designationTitle, self::STATION_FIVE_LEAVE_DESIGNATIONS, true)
            ? 5
            : 10;
    }
}
