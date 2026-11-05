<?php

namespace App\Services;

use App\Models\ActionHistory;
use App\Models\Dtr;
use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\PayrollPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Firmware supplies candidates, not authority to deduct leave or holiday pay. */
class AutomaticAbsenceService
{
    public const SOURCE = 'auto_absence';

    public function calculation(array $data, ?Employee $employee = null): array
    {
        $resolvedHoliday = filled($data['date_in'] ?? null)
            ? app(HolidayEntitlementService::class)->resolveForEmployeeDate(
                $employee,
                (string) $data['date_in'],
                isset($data['branch_id']) ? (int) $data['branch_id'] : ($employee?->branch_id ? (int) $employee->branch_id : null),
            )
            : ['holiday' => null, 'excluded' => false];

        $isApplicableHoliday = $resolvedHoliday['holiday'] !== null && ! $resolvedHoliday['excluded'];

        return [
            'schedule_type' => $isApplicableHoliday ? 'Holiday' : 'Absent',
            'entry_source' => self::SOURCE,
            'day_part' => $data['day_part'],
            'is_absent' => ! $isApplicableHoliday,
            'absence_minutes' => $isApplicableHoliday
                ? 0
                : app(DtrDayPartService::class)->absenceMinutes($data['date_in'], $data['day_part']),
            'work_hrs' => 0, 'credited_work_hrs' => 0,
            'late' => 0, 'undertime' => 0, 'overtime' => 0, 'early_clock_in' => 0,
            'credited_overtime' => 0, 'credited_early_clock_in' => 0,
            'early_clock_in_approved' => false, 'overtime_approved' => false,
        ];
    }

    public function assessCandidate(array $data, ?Employee $employee): array
    {
        $leaves = $employee && $data['day_part'] === 'whole_day'
            ? $this->leavesForDate($employee, $data['date_in']) : collect();

        return [$data, $this->exclusionReason($data, $employee, $leaves)];
    }

    public function exclusionReason(array $data, ?Employee $employee, ?Collection $leaves = null): ?string
    {
        if ($data['day_part'] !== 'whole_day') {
            return 'Legacy half-day absence ignored. Actual half-day duty already determines the missing-half deduction.';
        }
        $period = PayrollPeriod::query()->lockForUpdate()->find($data['payroll_period_id']);
        if (! $period || $period->is_locked) {
            throw new \DomainException('Automatic absence cannot change a locked payroll period.');
        }
        $employee = $employee ? Employee::query()->lockForUpdate()->find($employee->id) : null;
        if (! $employee || $employee->hasEndedEmployment() || (int) $employee->branch_id !== (int) $data['branch_id']) {
            return 'No unique active employee in this branch.';
        }
        if ($employee->schedule_type !== 'regular') {
            return 'Employee is not on the HRIS regular schedule.';
        }
        if (app(PayrollCalculator::class)->isEmployeePayrollExcluded($period, $employee)) {
            return 'Employee or branch is excluded from this payroll period.';
        }
        $date = Carbon::parse($data['date_in']);
        if ($date->isSunday()) {
            return 'Sunday/rest day is excluded from automatic absence.';
        }
        if ($date->isSaturday() && ($data['schedule_start'] !== '08:00:00' || $data['schedule_end'] !== '11:00:00')) {
            return 'Saturday absence requires the regular 08:00-11:00 workday.';
        }
        if ($date->isSaturday() && app(SaturdayWfhPolicyService::class)->isApprovedWfh($employee, $data['date_in'])) {
            return 'Saturday WFH is assigned to this employee; a biometric punch is not required.';
        }
        if (! $employee->hired_date || $date->lt($employee->hired_date->startOfDay())) {
            return 'Employment coverage is not confirmed for this date.';
        }
        if (now('Asia/Manila')->lte(Carbon::parse($data['date_in'].' '.$data['schedule_end'], 'Asia/Manila'))) {
            return 'Scheduled workday has not finished.';
        }
        $leaves ??= $this->leavesForDate($employee, $data['date_in']);
        // Keep the original whole-day source when only part is approved leave.
        // Payroll overlays that leave without creating a second absence row.
        if ($leaves->contains(fn (Leave $leave): bool => $leave->status === 'Pending')) {
            return 'Pending leave requires review before automatic absence.';
        }
        $approved = $leaves->where('status', 'Approved');
        if ($approved->contains(fn (Leave $leave): bool => ! $leave->is_half_day)
            || $approved->pluck('half_day_period')->intersect(['morning', 'afternoon'])->unique()->count() === 2) {
            return 'Approved leave covers the workday.';
        }
        if (DtrChangeRequest::query()->where('employee_id', $employee->id)->where('status', 'Pending')
            ->where(fn ($q) => $q->whereDate('dtr_date', $data['date_in'])
                ->orWhere(fn ($q) => $q->whereDate('date_from', '<=', $data['date_in'])->whereDate('date_to', '>=', $data['date_in'])))->exists()) {
            return 'Attendance correction is awaiting approval.';
        }
        $records = $this->records($data, $employee);
        foreach ($records as $record) {
            if ($record->leave_id) {
                continue; // approved coverage was resolved above
            }
            if ($record->entry_source === self::SOURCE) {
                return 'Automatic absence already exists; review legacy records before reimport.';
            }
            if ($record->is_absent) {
                return 'An existing absence condition already covers this date.';
            }

            return 'Attendance exists on this date. Half-day duty or incomplete punches must not create an absence row.';
        }

        return null;
    }

    protected function records(array $data, Employee $employee): Collection
    {
        $ids = array_unique(array_filter([(string) $data['fingerprint_id'], (string) $employee->fingerprint_id,
            (string) (int) ($employee->fingerprint_id ?: $employee->uid)]));
        $official = Dtr::query()->where('branch_id', $data['branch_id'])->whereIn('fingerprint_id', $ids)
            ->where('date_in', $data['date_in'])->lockForUpdate()->get();
        $visible = EmployeeVisibleDtr::query()->where('branch_id', $data['branch_id'])
            ->where(fn ($q) => $q->where('employee_id', $employee->id)->orWhereIn('fingerprint_id', $ids))
            ->where('date_in', $data['date_in'])->lockForUpdate()->get();

        return $official->concat($visible);
    }

    protected function leavesForDate(Employee $employee, string $date): Collection
    {
        return Leave::query()->where('employee_id', $employee->id)->whereIn('status', ['Pending', 'Approved'])
            ->where('leave_from', '<', Carbon::parse($date)->addDay()->toDateString())
            ->where(fn ($q) => $q->where('leave_to', '>=', $date)->orWhereNull('leave_to'))->get();
    }

    /** Actual scans replace automatic absence; final approved leave can reconcile manual absence too. */
    public function supersede(Dtr $attendance): void
    {
        if (! $attendance->is_absent && $attendance->entry_source !== self::SOURCE) {
            $approvedLeave = $attendance->leave_id && $attendance->leave?->status === 'Approved';
            $exists = false;
            foreach ([Dtr::class, EmployeeVisibleDtr::class] as $model) {
                $exists |= $model::query()->where('branch_id', $attendance->branch_id)
                    ->where('payroll_period_id', $attendance->payroll_period_id)->where('fingerprint_id', $attendance->fingerprint_id)
                    ->where('date_in', $attendance->date_in)->when(! $approvedLeave, fn ($q) => $q->where('entry_source', self::SOURCE))
                    ->where(fn ($q) => $q->where('is_absent', true)->orWhere('entry_source', self::SOURCE))->exists();
            }
            if (! $exists) {
                return;
            }
            DB::transaction(fn () => $this->supersedeLocked($attendance));
        }
    }

    protected function supersedeLocked(Dtr $attendance): void
    {
        if ($attendance->is_absent || $attendance->entry_source === self::SOURCE) {
            return;
        }
        $period = PayrollPeriod::query()->lockForUpdate()->find($attendance->payroll_period_id);
        if (! $period || $period->is_locked) {
            throw new \DomainException('Attendance correction cannot change a locked payroll period.');
        }
        $part = $attendance->day_part ?: 'whole_day';
        if (! in_array($part, ['morning', 'afternoon', 'whole_day'], true)) {
            $part = 'whole_day';
        }
        // ANY actual attendance removes the derived absence for the entire date.
        // The existing payroll half-day rule handles a missing other half.
        if (! $attendance->leave_id) {
            $part = 'whole_day';
        }
        foreach ([Dtr::class, EmployeeVisibleDtr::class] as $model) {
            $approvedLeave = $attendance->leave_id && $attendance->leave?->status === 'Approved';
            $records = $model::query()->where('branch_id', $attendance->branch_id)
                ->where('payroll_period_id', $attendance->payroll_period_id)->where('fingerprint_id', $attendance->fingerprint_id)
                ->where('date_in', $attendance->date_in)->when(! $approvedLeave, fn ($q) => $q->where('entry_source', self::SOURCE))
                ->where(fn ($q) => $q->where('is_absent', true)->orWhere('entry_source', self::SOURCE))->lockForUpdate()->get();
            foreach ($records as $record) {
                if ($part !== 'whole_day' && $record->day_part !== 'whole_day' && $record->day_part !== $part) {
                    continue;
                }
                if ($approvedLeave && $record->entry_source === self::SOURCE
                    && $record->day_part === 'whole_day' && $part !== 'whole_day') {
                    continue; // preserve imported source; LeavePayService overlays partial leave
                }
                $before = $record->getAttributes();
                if ($part !== 'whole_day' && $record->day_part === 'whole_day') {
                    $remaining = $part === 'morning' ? 'afternoon' : 'morning';
                    $record->forceFill(['day_part' => $remaining,
                        'absence_minutes' => app(DtrDayPartService::class)->absenceMinutes($record->date_in, $remaining)])->save();
                } else {
                    $record->delete(); // recoverable soft-delete; never delete the source scan
                }
                ActionHistory::query()->create([
                    'actor_name' => auth()->user()?->name ?: 'DTR import',
                    'action' => $approvedLeave ? 'absence_reconciled_with_leave' : 'automatic_absence_superseded',
                    'model_type' => $model, 'model_id' => $record->id,
                    'summary' => $approvedLeave ? 'Absence covered by approved leave #'.$attendance->leave_id : 'Automatic absence superseded by attendance.',
                    'before_data' => $before, 'after_data' => $record->getAttributes(),
                ]);
            }
        }
    }

    /**
     * Reconcile existing unlocked auto_absence DTRs when a holiday is added or updated.
     */
    public function reconcileForHoliday(Holiday $holiday): int
    {
        if (! Schema::hasTable('dtrs') || ! Schema::hasTable('payroll_periods') || ! Schema::hasTable('employees')) {
            return 0;
        }

        $dateStr = $holiday->date ? Carbon::parse($holiday->date)->toDateString() : null;
        $monthDay = $holiday->month_day ?: ($dateStr ? Carbon::parse($dateStr)->format('m-d') : null);
        if (! $dateStr && ! $monthDay) {
            return 0;
        }

        $unlockedPeriodIds = PayrollPeriod::query()
            ->where('is_locked', false)
            ->pluck('id')
            ->all();
        if ($unlockedPeriodIds === []) {
            return 0;
        }

        $entitlement = app(HolidayEntitlementService::class);
        $reconciled = 0;

        foreach ([Dtr::class, EmployeeVisibleDtr::class] as $model) {
            $table = (new $model)->getTable();
            if (! Schema::hasTable($table)) {
                continue;
            }

            $recordsOnDate = $model::query()
                ->whereIn('payroll_period_id', $unlockedPeriodIds)
                ->where('is_locked', false)
                ->when(filled($holiday->branch_id), fn ($q) => $q->where('branch_id', $holiday->branch_id))
                ->get()
                ->filter(function ($record) use ($holiday, $dateStr, $monthDay): bool {
                    if (blank($record->date_in)) {
                        return false;
                    }
                    $recDate = Carbon::parse($record->date_in);

                    return ($dateStr && $recDate->toDateString() === $dateStr)
                        || ((bool) $holiday->is_recurring && $monthDay && $recDate->format('m-d') === $monthDay);
                });

            foreach ($recordsOnDate as $record) {
                $employee = $this->resolveRecordEmployee($record);
                $resolved = $entitlement->resolveForEmployeeDate(
                    $employee,
                    (string) $record->date_in,
                    (int) $record->branch_id,
                );

                if (! $resolved['holiday']) {
                    continue;
                }

                $matchedHoliday = $resolved['holiday'];
                $excluded = (bool) $resolved['excluded'];

                if ($this->isAutomaticOrImportedAbsenceRecord($record)) {
                    if ($excluded) {
                        continue;
                    }

                    $dateObj = Carbon::parse($record->date_in);
                    if ($dateObj->isSaturday() && $employee && app(SaturdayWfhPolicyService::class)->isApprovedWfh($employee, $dateObj->toDateString())) {
                        $record->delete();

                        continue;
                    }

                    if ($record->is_absent || strcasecmp((string) $record->schedule_type, 'Holiday') !== 0 || ! (bool) $record->is_holiday) {
                        $before = $record->getAttributes();
                        $attributes = array_filter([
                            'entry_source' => $record->entry_source ?: self::SOURCE,
                            'schedule_type' => 'Holiday',
                            'is_absent' => false,
                            'absence_minutes' => 0,
                            'work_hrs' => 0,
                            'credited_work_hrs' => 0,
                            'is_holiday' => true,
                            'holiday_id' => $matchedHoliday->id,
                            'holiday_type' => $matchedHoliday->type?->type,
                            'holiday_rate' => $matchedHoliday->type?->rate !== null ? (float) $matchedHoliday->type->rate : null,
                            'holiday_excluded' => false,
                        ], fn ($val, $col) => Schema::hasColumn($table, $col), ARRAY_FILTER_USE_BOTH);

                        $record->forceFill($attributes)->saveQuietly();

                        if (Schema::hasTable('action_histories')) {
                            ActionHistory::query()->create([
                                'actor_name' => auth()->user()?->name ?: 'Holiday calendar',
                                'action' => 'holiday_reconciled_dtr',
                                'model_type' => $model,
                                'model_id' => $record->id,
                                'summary' => 'Automatic absence entry overridden and marked as Holiday.',
                                'before_data' => $before,
                                'after_data' => $record->getAttributes(),
                            ]);
                        }
                        $reconciled++;
                    }

                    continue;
                }

                if (! $record->is_absent) {
                    $attributes = array_filter([
                        'is_holiday' => ! $excluded,
                        'holiday_id' => $excluded ? null : $matchedHoliday->id,
                        'holiday_type' => $excluded ? null : $matchedHoliday->type?->type,
                        'holiday_rate' => $excluded ? null : ($matchedHoliday->type?->rate !== null ? (float) $matchedHoliday->type->rate : null),
                        'holiday_excluded' => $excluded,
                    ], fn ($val, $col) => Schema::hasColumn($table, $col), ARRAY_FILTER_USE_BOTH);

                    $record->forceFill($attributes)->saveQuietly();
                }
            }
        }

        return $reconciled;
    }

    public function reconcileForScope(?int $payrollPeriodId = null, ?int $branchId = null, ?int $employeeId = null): int
    {
        if (! Schema::hasTable('holidays') || ! Schema::hasTable('payroll_periods')) {
            return 0;
        }

        $unlockedPeriods = PayrollPeriod::query()
            ->where('is_locked', false)
            ->when($payrollPeriodId, fn ($q) => $q->whereKey($payrollPeriodId))
            ->get();
        if ($unlockedPeriods->isEmpty()) {
            return 0;
        }

        if (! app()->runningUnitTests() && Schema::hasTable('holiday_types')) {
            $years = $unlockedPeriods
                ->flatMap(fn (PayrollPeriod $p): array => array_filter([
                    $p->date_start ? Carbon::parse($p->date_start)->year : null,
                    $p->date_end ? Carbon::parse($p->date_end)->year : null,
                    now('Asia/Manila')->year,
                ]))
                ->unique()
                ->values();

            foreach ($years as $yr) {
                $officialDates = array_keys(app(PhilippineHolidayService::class)->getOfficialHolidays((int) $yr));
                $foundDates = Holiday::query()
                    ->whereNull('branch_id')
                    ->whereIn('date', $officialDates)
                    ->pluck('date')
                    ->map(fn ($d) => Carbon::parse($d)->toDateString())
                    ->all();

                if (count(array_diff($officialDates, $foundDates)) > 0) {
                    app(PhilippineHolidayService::class)->syncHolidaysForYear((int) $yr);
                }
            }
        }

        $unlockedPeriodIds = $unlockedPeriods->pluck('id')->all();
        $targetEmployee = $employeeId ? Employee::query()->find($employeeId) : null;
        $targetVariants = $targetEmployee
            ? app(SaturdayWfhDtrService::class)->fingerprintVariants($targetEmployee->fingerprint_id ?: $targetEmployee->uid)
            : [];

        $entitlement = app(HolidayEntitlementService::class);
        $reconciled = 0;

        foreach ([Dtr::class, EmployeeVisibleDtr::class] as $model) {
            $table = (new $model)->getTable();
            if (! Schema::hasTable($table)) {
                continue;
            }

            $records = $model::query()
                ->whereIn('payroll_period_id', $unlockedPeriodIds)
                ->where('is_locked', false)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($employeeId, function ($q) use ($table, $employeeId, $targetVariants): void {
                    $q->where(function ($sub) use ($table, $employeeId, $targetVariants): void {
                        if (Schema::hasColumn($table, 'employee_id')) {
                            $sub->where('employee_id', $employeeId);
                        }
                        if ($targetVariants !== [] && Schema::hasColumn($table, 'fingerprint_id')) {
                            $sub->orWhereIn('fingerprint_id', $targetVariants);
                        }
                    });
                })
                ->get();

            foreach ($records as $record) {
                if (blank($record->date_in)) {
                    continue;
                }

                $employee = $this->resolveRecordEmployee($record);
                $resolved = $entitlement->resolveForEmployeeDate(
                    $employee,
                    (string) $record->date_in,
                    (int) $record->branch_id,
                );

                if (! $resolved['holiday']) {
                    continue;
                }

                $matchedHoliday = $resolved['holiday'];
                $excluded = (bool) $resolved['excluded'];

                if ($this->isAutomaticOrImportedAbsenceRecord($record)) {
                    if ($excluded) {
                        continue;
                    }

                    $dateObj = Carbon::parse($record->date_in);
                    if ($dateObj->isSaturday() && $employee && app(SaturdayWfhPolicyService::class)->isApprovedWfh($employee, $dateObj->toDateString())) {
                        $record->delete();

                        continue;
                    }

                    if ($record->is_absent || strcasecmp((string) $record->schedule_type, 'Holiday') !== 0 || ! (bool) $record->is_holiday) {
                        $before = $record->getAttributes();
                        $attributes = array_filter([
                            'entry_source' => $record->entry_source ?: self::SOURCE,
                            'schedule_type' => 'Holiday',
                            'is_absent' => false,
                            'absence_minutes' => 0,
                            'work_hrs' => 0,
                            'credited_work_hrs' => 0,
                            'is_holiday' => true,
                            'holiday_id' => $matchedHoliday->id,
                            'holiday_type' => $matchedHoliday->type?->type,
                            'holiday_rate' => $matchedHoliday->type?->rate !== null ? (float) $matchedHoliday->type->rate : null,
                            'holiday_excluded' => false,
                        ], fn ($val, $col) => Schema::hasColumn($table, $col), ARRAY_FILTER_USE_BOTH);

                        $record->forceFill($attributes)->saveQuietly();

                        if (Schema::hasTable('action_histories')) {
                            ActionHistory::query()->create([
                                'actor_name' => auth()->user()?->name ?: 'Holiday calendar',
                                'action' => 'holiday_reconciled_dtr',
                                'model_type' => $model,
                                'model_id' => $record->id,
                                'summary' => 'Automatic absence entry overridden and marked as Holiday.',
                                'before_data' => $before,
                                'after_data' => $record->getAttributes(),
                            ]);
                        }
                        $reconciled++;
                    }

                    continue;
                }

                if (! $record->is_absent) {
                    if ((bool) $record->is_holiday !== (! $excluded) || (int) ($record->holiday_id ?? 0) !== (int) ($excluded ? 0 : $matchedHoliday->id)) {
                        $attributes = array_filter([
                            'is_holiday' => ! $excluded,
                            'holiday_id' => $excluded ? null : $matchedHoliday->id,
                            'holiday_type' => $excluded ? null : $matchedHoliday->type?->type,
                            'holiday_rate' => $excluded ? null : ($matchedHoliday->type?->rate !== null ? (float) $matchedHoliday->type->rate : null),
                            'holiday_excluded' => $excluded,
                        ], fn ($val, $col) => Schema::hasColumn($table, $col), ARRAY_FILTER_USE_BOTH);

                        $record->forceFill($attributes)->saveQuietly();
                    }
                }
            }
        }

        return $reconciled;
    }

    protected function isAutomaticOrImportedAbsenceRecord(Dtr $record): bool
    {
        if ($record->entry_source === self::SOURCE) {
            return true;
        }

        if (in_array((string) $record->entry_source, ['manual', 'absence'], true)) {
            return false;
        }

        return blank($record->time_in)
            && blank($record->time_out)
            && ! $record->leave_id
            && ((bool) $record->is_absent || in_array(strtolower((string) $record->schedule_type), ['absent', 'holiday'], true));
    }

    protected function resolveRecordEmployee(Dtr $record): ?Employee
    {
        $employee = filled($record->employee_id ?? null)
            ? Employee::query()->find($record->employee_id)
            : null;

        if (! $employee && filled($record->fingerprint_id ?? null)) {
            $variants = app(SaturdayWfhDtrService::class)->fingerprintVariants($record->fingerprint_id);
            $employee = Employee::query()
                ->where('branch_id', $record->branch_id)
                ->where(function ($q) use ($variants): void {
                    $q->whereIn('fingerprint_id', $variants)->orWhereIn('uid', $variants);
                })
                ->first();
        }

        return $employee;
    }
}
