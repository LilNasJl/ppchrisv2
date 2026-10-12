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

/** Firmware supplies candidates, not authority to deduct leave or holiday pay. */
class AutomaticAbsenceService
{
    public const SOURCE = 'auto_absence';

    public function calculation(array $data): array
    {
        return [
            'schedule_type' => 'Absent',
            'entry_source' => self::SOURCE,
            'day_part' => $data['day_part'],
            'is_absent' => true,
            'absence_minutes' => app(DtrDayPartService::class)->absenceMinutes($data['date_in'], $data['day_part']),
            'work_hrs' => 0, 'credited_work_hrs' => 0,
            'late' => 0, 'undertime' => 0, 'overtime' => 0, 'early_clock_in' => 0,
            'credited_overtime' => 0, 'credited_early_clock_in' => 0,
            'early_clock_in_approved' => false, 'overtime_approved' => false,
        ];
    }

    public function exclusionReason(array $data, ?Employee $employee): ?string
    {
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
        if ($date->isWeekend()) {
            return 'Weekend: existing Saturday/rest-day rules are unchanged.';
        }
        if (! $employee->hired_date || $date->lt($employee->hired_date->startOfDay())) {
            return 'Employment coverage is not confirmed for this date.';
        }
        if (now('Asia/Manila')->lte(Carbon::parse($data['date_in'].' '.$data['schedule_end'], 'Asia/Manila'))) {
            return 'Scheduled workday has not finished.';
        }
        $holiday = Holiday::query()->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $data['branch_id']))
            ->where(fn ($q) => $q->whereDate('date', $data['date_in'])
                ->orWhere(fn ($q) => $q->where('is_recurring', true)->where('month_day', $date->format('m-d'))))->exists();
        if ($holiday) {
            return 'Holiday/non-working date requires HRIS attendance rules.';
        }
        $leaves = Leave::query()->where('employee_id', $employee->id)->whereIn('status', ['Pending', 'Approved'])
            ->whereDate('leave_from', '<=', $data['date_in'])
            ->where(fn ($q) => $q->whereDate('leave_to', '>=', $data['date_in'])->orWhereNull('leave_to'))->get();
        foreach ($leaves as $leave) {
            if ($leave->status === 'Pending' || ! $leave->is_half_day || $data['day_part'] === 'whole_day'
                || $leave->half_day_period === $data['day_part']) {
                return 'Approved or pending leave covers this day-part.';
            }
        }
        if (DtrChangeRequest::query()->where('employee_id', $employee->id)->where('status', 'Pending')
            ->where(fn ($q) => $q->whereDate('dtr_date', $data['date_in'])
                ->orWhere(fn ($q) => $q->whereDate('date_from', '<=', $data['date_in'])->whereDate('date_to', '>=', $data['date_in'])))->exists()) {
            return 'Attendance correction is awaiting approval.';
        }
        $records = $this->records($data, $employee);
        $part = $data['day_part'];
        $otherPartPresent = false;
        foreach ($records as $record) {
            if ($record->entry_source === self::SOURCE) {
                if ($record->day_part === 'whole_day' || $record->day_part === $part || $part === 'whole_day') {
                    return 'Automatic absence already exists.';
                }

                continue;
            }
            if ($record->is_absent || $record->leave_id) {
                if ($part === 'whole_day' || $record->day_part === 'whole_day' || $record->day_part === $part) {
                    return 'Existing approved attendance condition covers this day-part.';
                }

                continue;
            }
            if (! app(DtrDayPartService::class)->isRegularScheduleType($record->schedule_type)
                || blank($record->time_in) || blank($record->time_out) || blank($record->date_out)
                || Carbon::parse($record->date_out)->toDateString() !== $data['date_in']) {
                return 'Incomplete/non-regular attendance needs review, not automatic absence.';
            }
            // Use the candidate's configured split, not the export's payroll half-day window.
            $in = $record->time_in;
            $out = $record->time_out;
            if ($out <= $in) {
                return 'Invalid attendance interval requires review.';
            }
            $morning = $in < $data['absence_split'] && $out > $data['schedule_start'];
            $afternoon = $in < $data['schedule_end'] && $out > $data['absence_split'];
            if (($part === 'whole_day' && ($morning || $afternoon))
                || ($part === 'morning' && $morning) || ($part === 'afternoon' && $afternoon)) {
                return 'Actual attendance exists in the proposed absent day-part.';
            }
            $otherPartPresent |= $part === 'morning' ? $afternoon : $morning;
        }

        return $part !== 'whole_day' && ! $otherPartPresent ? 'Import the other day-part attendance before its half-day absence.' : null;
    }

    protected function records(array $data, Employee $employee): Collection
    {
        $ids = array_unique(array_filter([(string) $data['fingerprint_id'], (string) $employee->fingerprint_id,
            (string) (int) ($employee->fingerprint_id ?: $employee->uid)]));
        $official = Dtr::query()->where('branch_id', $data['branch_id'])->whereIn('fingerprint_id', $ids)
            ->whereDate('date_in', $data['date_in'])->lockForUpdate()->get();
        $visible = EmployeeVisibleDtr::query()->where('branch_id', $data['branch_id'])
            ->where(fn ($q) => $q->where('employee_id', $employee->id)->orWhereIn('fingerprint_id', $ids))
            ->whereDate('date_in', $data['date_in'])->lockForUpdate()->get();

        return $official->concat($visible);
    }

    /** Only automatic records are superseded. Manual absences and locked data remain protected. */
    public function supersede(Dtr $attendance): void
    {
        if (! $attendance->is_absent && $attendance->entry_source !== self::SOURCE) {
            $exists = false;
            foreach ([Dtr::class, EmployeeVisibleDtr::class] as $model) {
                $exists |= $model::query()->where('branch_id', $attendance->branch_id)
                    ->where('payroll_period_id', $attendance->payroll_period_id)->where('fingerprint_id', $attendance->fingerprint_id)
                    ->whereDate('date_in', $attendance->date_in)->where('entry_source', self::SOURCE)->where('is_absent', true)->exists();
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
        // An incomplete or non-regular correction suppresses automatic inference for this day.
        if (! $attendance->leave_id && (! app(DtrDayPartService::class)->isRegularScheduleType($attendance->schedule_type)
            || blank($attendance->time_in) || blank($attendance->time_out))) {
            $part = 'whole_day';
        }
        foreach ([Dtr::class, EmployeeVisibleDtr::class] as $model) {
            $records = $model::query()->where('branch_id', $attendance->branch_id)
                ->where('payroll_period_id', $attendance->payroll_period_id)->where('fingerprint_id', $attendance->fingerprint_id)
                ->whereDate('date_in', $attendance->date_in)->where('entry_source', self::SOURCE)->where('is_absent', true)->lockForUpdate()->get();
            foreach ($records as $record) {
                if ($part !== 'whole_day' && $record->day_part !== 'whole_day' && $record->day_part !== $part) {
                    continue;
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
                    'actor_name' => auth()->user()?->name ?: 'DTR import', 'action' => 'automatic_absence_superseded',
                    'model_type' => $model, 'model_id' => $record->id,
                    'summary' => 'Automatic absence superseded by attendance/approved leave.',
                    'before_data' => $before, 'after_data' => $record->getAttributes(),
                ]);
            }
        }
    }
}
