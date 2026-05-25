<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\PayrollPeriod;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class LeaveDtrService
{
    public function approveLeaveWithPaidDtr(Leave $leave, PayrollPeriod $payrollPeriod, ?string $comment = null, ?int $reviewedBy = null): void
    {
        DB::transaction(function () use ($leave, $payrollPeriod, $comment, $reviewedBy): void {
            $leave = Leave::query()
                ->with(['employee.branch', 'employee.designation'])
                ->lockForUpdate()
                ->findOrFail($leave->id);

            $payrollPeriod = PayrollPeriod::query()
                ->lockForUpdate()
                ->findOrFail($payrollPeriod->id);

            $this->validateLeaveCanUsePayrollPeriod($leave, $payrollPeriod);
            $this->ensureNoExistingDtrForLeaveDates($leave, $payrollPeriod);

            $leave->approveRequest($comment, $reviewedBy);
            $leave->refresh();

            $this->createPaidLeaveDtrRows($leave, $payrollPeriod);
        });
    }

    protected function validateLeaveCanUsePayrollPeriod(Leave $leave, PayrollPeriod $payrollPeriod): void
    {
        if ($leave->status !== 'Pending') {
            throw new RuntimeException('Only pending leave requests can be approved.');
        }

        if ($payrollPeriod->is_locked) {
            throw new RuntimeException('Selected payroll period is locked.');
        }

        if (blank($payrollPeriod->date_start) || blank($payrollPeriod->date_end)) {
            throw new RuntimeException('Selected payroll period has an invalid date range.');
        }

        $leaveFrom = Carbon::parse($leave->leave_from)->startOfDay();
        $leaveTo = Carbon::parse($leave->leave_to ?: $leave->leave_from)->startOfDay();
        $periodStart = Carbon::parse($payrollPeriod->date_start)->startOfDay();
        $periodEnd = Carbon::parse($payrollPeriod->date_end)->startOfDay();

        if ($leave->is_half_day && ! $leaveFrom->isSameDay($leaveTo)) {
            throw new RuntimeException('Half-day leave must use the same start and end date.');
        }

        if ($leaveFrom->lt($periodStart) || $leaveTo->gt($periodEnd)) {
            throw new RuntimeException('Leave dates must be inside the selected payroll period range.');
        }

        if (! $leave->employee) {
            throw new RuntimeException('Leave request is missing an employee profile.');
        }

        if (blank($leave->employee->branch_id)) {
            throw new RuntimeException('Employee branch is required before creating leave D.T.R entries.');
        }

        if (blank($this->fingerprintId($leave->employee))) {
            throw new RuntimeException('Employee fingerprint ID or company ID is required before creating leave D.T.R entries.');
        }
    }

    protected function ensureNoExistingDtrForLeaveDates(Leave $leave, PayrollPeriod $payrollPeriod): void
    {
        $employee = $leave->employee;
        $dates = collect(CarbonPeriod::create($leave->leave_from, $leave->leave_to ?: $leave->leave_from))
            ->map(fn (Carbon $date): string => $date->toDateString())
            ->all();

        $existingDates = Dtr::query()
            ->where('payroll_period_id', $payrollPeriod->id)
            ->where('branch_id', $employee->branch_id)
            ->where('fingerprint_id', $this->fingerprintId($employee))
            ->whereIn('date_in', $dates)
            ->pluck('date_in')
            ->map(fn ($date): string => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        if ($existingDates->isNotEmpty()) {
            throw new RuntimeException('D.T.R entries already exist for these leave date(s): '.$existingDates->implode(', '));
        }
    }

    protected function createPaidLeaveDtrRows(Leave $leave, PayrollPeriod $payrollPeriod): void
    {
        $employee = $leave->employee;
        $schedule = $this->leaveSchedule($employee);
        $leaveDayValue = $leave->is_half_day ? 0.5 : 1.0;
        $creditedMinutes = (int) round($this->payableScheduleMinutes(
            scheduleStart: $schedule['start'],
            scheduleEnd: $schedule['end'],
            deductNoonBreak: $schedule['deduct_noon_break'],
        ) * $leaveDayValue);

        foreach (CarbonPeriod::create($leave->leave_from, $leave->leave_to ?: $leave->leave_from) as $date) {
            $dateString = $date->toDateString();

            Dtr::query()->create([
                'leave_id' => $leave->id,
                'payroll_period_id' => $payrollPeriod->id,
                'branch_id' => $employee->branch_id,
                'fingerprint_id' => $this->fingerprintId($employee),
                'date_in' => $dateString,
                'time_in' => $schedule['start'],
                'date_out' => $dateString,
                'time_out' => $schedule['end'],
                'schedule_type' => 'Leave',
                'schedule_start' => $schedule['start'],
                'schedule_end' => $schedule['end'],
                'late' => 0,
                'undertime' => 0,
                'overtime' => 0,
                'early_clock_in' => 0,
                'credited_overtime' => 0,
                'work_hrs' => $creditedMinutes,
                'credited_work_hrs' => $creditedMinutes,
                'overtime_status' => 'n/a',
                'early_clock_in_approved' => false,
                'overtime_approved' => false,
                'is_holiday' => 0,
                'holiday_id' => null,
                'holiday_type' => null,
                'holiday_rate' => null,
                'daily_rate' => filled($employee->daily_rate) ? (float) $employee->daily_rate : null,
                'comment' => 'Approved leave: '.$leave->leave_type,
                'is_absent' => false,
                'is_imported' => false,
                'is_locked' => false,
            ]);
        }
    }

    protected function leaveSchedule(Employee $employee): array
    {
        $designation = Str::lower((string) $employee->designation?->title);

        if ($employee->rate_type === 'daily') {
            if (Str::contains($designation, ['cashier/forecourt attendant', 'forecourt attendant'])) {
                return [
                    'start' => '04:40:00',
                    'end' => '12:30:00',
                    'deduct_noon_break' => false,
                ];
            }

            return [
                'start' => '08:00:00',
                'end' => '18:00:00',
                'deduct_noon_break' => false,
            ];
        }

        $branch = $employee->branch ?: Branch::query()->find($employee->branch_id);

        return [
            'start' => $this->normalizeTime($branch?->reg_sched_start) ?: '08:00:00',
            'end' => $this->normalizeTime($branch?->reg_sched_end) ?: '18:00:00',
            'deduct_noon_break' => true,
        ];
    }

    protected function payableScheduleMinutes(string $scheduleStart, string $scheduleEnd, bool $deductNoonBreak): int
    {
        $start = Carbon::parse("2000-01-01 {$scheduleStart}");
        $end = Carbon::parse("2000-01-01 {$scheduleEnd}");

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $minutes = (int) $start->diffInMinutes($end);

        if (! $deductNoonBreak) {
            return max(0, $minutes);
        }

        $breakStart = $start->copy()->setTime(12, 0);
        $breakEnd = $start->copy()->setTime(13, 0);
        $overlapStart = $start->greaterThan($breakStart) ? $start : $breakStart;
        $overlapEnd = $end->lessThan($breakEnd) ? $end : $breakEnd;

        if ($overlapEnd->greaterThan($overlapStart)) {
            $minutes -= (int) $overlapStart->diffInMinutes($overlapEnd);
        }

        return max(0, $minutes);
    }

    protected function fingerprintId(Employee $employee): ?string
    {
        $fingerprintId = $employee->fingerprint_id ?: $employee->uid;

        return filled($fingerprintId) ? (string) $fingerprintId : null;
    }

    protected function normalizeTime(mixed $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        return Carbon::parse($time)->format('H:i:s');
    }
}
