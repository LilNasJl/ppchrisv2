<?php

namespace App\Services;

use App\Models\ActionHistory;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\PayrollPeriod;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/** HRIS is the authority for Saturday punch requirements; device absences are candidates only. */
class SaturdayWfhPolicyService
{
    public function effectiveFrom(): ?string
    {
        if (! Schema::hasTable('saturday_wfh_policy_periods')) {
            return null;
        }

        return DB::table('saturday_wfh_policy_periods')->whereNull('stopped_at')
            ->orderByDesc('id')->value('effective_from');
    }

    public function isEnabled(): bool
    {
        return $this->effectiveFrom() !== null;
    }

    public function enable(?User $actor = null): void
    {
        $start = today('Asia/Manila');
        if ($start->isSaturday() && now('Asia/Manila')->format('H:i:s') > '11:00:00') {
            $start->addDay();
        }

        $this->activate($start->toDateString(), $actor);
    }

    public function isStopped(): bool
    {
        return Schema::hasTable('saturday_wfh_policy_periods')
            && DB::table('saturday_wfh_policy_periods')->whereNotNull('stopped_at')->exists()
            && $this->effectiveFrom() === null;
    }

    public function requiresPunch(Employee $employee, string $date): bool
    {
        if (! $this->isWfhEligible($employee, $date)) {
            return true;
        }

        return DB::table('saturday_on_site_exemptions')
            ->where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))
            ->exists();
    }

    public function isApprovedWfh(Employee $employee, string $date): bool
    {
        return $this->isWfhEligible($employee, $date) && ! $this->requiresPunch($employee, $date);
    }

    public function exemptions(Employee $employee): array
    {
        if (! Schema::hasTable('saturday_on_site_exemptions')) {
            return [];
        }

        return DB::table('saturday_on_site_exemptions')
            ->where('employee_id', $employee->id)
            ->orderByDesc('effective_from')
            ->get()
            ->all();
    }

    /** Approved no-punch Saturdays are HRIS attendance credit, not biometric DTR rows. */
    public function completedWfhDates(Employee $employee, PayrollPeriod $period, Collection $records): array
    {
        $start = Schema::hasTable('saturday_wfh_policy_periods')
            ? DB::table('saturday_wfh_policy_periods')->orderBy('effective_from')->value('effective_from')
            : null;
        if ($start === null || $employee->schedule_type !== 'regular') {
            return [];
        }

        $from = Carbon::parse($period->date_start)->max(Carbon::parse($start));
        $through = Carbon::parse($period->date_end)->min(today('Asia/Manila'));
        if ($from->isAfter($through)) {
            return [];
        }

        $leaves = Leave::query()->where('employee_id', $employee->id)
            ->whereIn('status', ['Pending', 'Approved'])
            ->whereDate('leave_from', '<=', $through->toDateString())
            ->where(fn ($query) => $query->whereNull('leave_to')
                ->orWhereDate('leave_to', '>=', $from->toDateString()))
            ->get();
        $holidays = Holiday::query()
            ->where(fn ($query) => $query->whereNull('branch_id')->orWhere('branch_id', $employee->branch_id))
            ->where(fn ($query) => $query->whereBetween('date', [$from->toDateString(), $through->toDateString()])
                ->orWhere('is_recurring', true))
            ->get();
        $recordDates = $records->filter(fn (Dtr $record): bool => filled($record->date_in)
            && strcasecmp(trim((string) $record->schedule_type), 'Overtime') !== 0)
            ->pluck('date_in')->map(fn ($date): string => Carbon::parse($date)->toDateString())->all();

        $dates = [];
        for ($day = $from->copy(); $day->lessThanOrEqualTo($through); $day->addDay()) {
            $date = $day->toDateString();
            if (! $day->isSaturday() || ($day->isToday() && now('Asia/Manila')->format('H:i:s') <= '11:00:00')
                || ($employee->hired_date && $day->isBefore($employee->hired_date))
                || ! $this->isApprovedWfh($employee, $date)
                || in_array($date, $recordDates, true)) {
                continue;
            }
            if ($leaves->contains(fn (Leave $leave): bool => $leave->leave_from->toDateString() <= $date
                && ($leave->leave_to === null || $leave->leave_to->toDateString() >= $date))) {
                continue;
            }
            if ($holidays->contains(fn (Holiday $holiday): bool => ($holiday->date?->toDateString() === $date)
                || ($holiday->is_recurring && $holiday->month_day === $day->format('m-d')))) {
                continue;
            }

            $dates[] = $date;
        }

        return $dates;
    }

    public function activate(string $date, ?User $actor = null): void
    {
        $start = $this->date($date);
        $this->ensureProspectiveStart($start);

        DB::transaction(function () use ($start, $actor): void {
            $current = DB::table('saturday_wfh_policy_periods')->whereNull('stopped_at')
                ->orderByDesc('id')->lockForUpdate()->first();
            $before = $current ? (array) $current : null;

            if ($current && Carbon::parse($current->effective_from, 'Asia/Manila')->isAfter(today('Asia/Manila'))) {
                DB::table('saturday_wfh_policy_periods')->where('id', $current->id)->update([
                    'effective_from' => $start->toDateString(), 'changed_by' => $actor?->id, 'updated_at' => now(),
                ]);
                $id = $current->id;
            } else {
                if ($current) {
                    DB::table('saturday_wfh_policy_periods')->where('id', $current->id)->update([
                        'effective_to' => $this->lastCoveredDate(),
                        'stopped_at' => now(), 'changed_by' => $actor?->id, 'updated_at' => now(),
                    ]);
                }
                $id = DB::table('saturday_wfh_policy_periods')->insertGetId([
                    'effective_from' => $start->toDateString(), 'effective_to' => null,
                    'stopped_at' => null, 'changed_by' => $actor?->id,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $this->audit('saturday_wfh_policy_set', $id, $actor, $before, ['effective_from' => $start->toDateString()]);
        });
    }

    public function stop(?User $actor = null): void
    {
        DB::transaction(function () use ($actor): void {
            $current = DB::table('saturday_wfh_policy_periods')->whereNull('stopped_at')
                ->orderByDesc('id')->lockForUpdate()->first();
            if (! $current) {
                throw new InvalidArgumentException('Saturday WFH is already stopped.');
            }
            $end = $this->lastCoveredDate();
            DB::table('saturday_wfh_policy_periods')->where('id', $current->id)->update([
                'effective_to' => $end, 'stopped_at' => now(),
                'changed_by' => $actor?->id, 'updated_at' => now(),
            ]);
            $this->audit('saturday_wfh_policy_stopped', $current->id, $actor, (array) $current,
                ['effective_to' => $end, 'stopped_at' => now()->toDateTimeString()]);
        });
    }

    public function addOnSiteExemption(Employee $employee, string $from, ?string $to, ?string $reason, ?User $actor = null): int
    {
        if (! $this->isEnabled()) {
            throw new InvalidArgumentException('Saturday WFH is disabled. On-site assignments are unavailable.');
        }
        if ($employee->schedule_type !== 'regular') {
            throw new InvalidArgumentException('Saturday WFH exemptions apply to regular-schedule employees only.');
        }
        $start = $this->date($from);
        $end = filled($to) ? $this->date($to) : null;
        $this->ensureProspectiveStart($start);
        if ($end && $end->isBefore($start)) {
            throw new InvalidArgumentException('The exemption end must not precede its start.');
        }

        return DB::transaction(function () use ($employee, $start, $end, $reason, $actor): int {
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $overlaps = DB::table('saturday_on_site_exemptions')
                ->where('employee_id', $employee->id)
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $start->toDateString()))
                ->when($end, fn ($query) => $query->whereDate('effective_from', '<=', $end->toDateString()))
                ->exists();
            if ($overlaps) {
                throw new InvalidArgumentException('This employee already has an on-site exemption covering part of the selected period.');
            }

            $attributes = [
                'employee_id' => $employee->id,
                'effective_from' => $start->toDateString(),
                'effective_to' => $end?->toDateString(), // null means Permanent
                'reason' => filled($reason) ? trim($reason) : null,
                'changed_by' => $actor?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $id = DB::table('saturday_on_site_exemptions')->insertGetId($attributes);
            $this->audit('saturday_on_site_exemption_added', $id, $actor, null, $attributes);

            return $id;
        });
    }

    public function endOnSiteExemption(Employee $employee, int $id, string $returnToWfhFrom, ?User $actor = null): void
    {
        if (! $this->isEnabled()) {
            throw new InvalidArgumentException('Saturday WFH is disabled. On-site assignments are unavailable.');
        }
        $return = $this->date($returnToWfhFrom);
        if ($return->isBefore(today('Asia/Manila'))) {
            throw new InvalidArgumentException('Past attendance requires HR review.');
        }
        if ($return->isToday() && $return->isSaturday() && now('Asia/Manila')->format('H:i:s') > '11:00:00') {
            throw new InvalidArgumentException('Saturday attendance has already closed. Return to WFH requires HR review.');
        }

        DB::transaction(function () use ($employee, $id, $return, $actor): void {
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $assignment = DB::table('saturday_on_site_exemptions')
                ->where('id', $id)->where('employee_id', $employee->id)->lockForUpdate()->first();
            if (! $assignment || ($assignment->effective_to && $return->greaterThan(Carbon::parse($assignment->effective_to)))) {
                throw new InvalidArgumentException('Select an active on-site assignment.');
            }
            if ($return->lessThanOrEqualTo(Carbon::parse($assignment->effective_from))) {
                DB::table('saturday_on_site_exemptions')->where('id', $id)->delete();
                $this->audit('saturday_on_site_exemption_cancelled', $id, $actor, (array) $assignment, ['cancelled_from' => $return->toDateString()]);

                return;
            }
            $end = $return->copy()->subDay()->toDateString();
            DB::table('saturday_on_site_exemptions')->where('id', $id)->update([
                'effective_to' => $end, 'changed_by' => $actor?->id, 'updated_at' => now(),
            ]);
            $this->audit('saturday_on_site_exemption_ended', $id, $actor, (array) $assignment, ['effective_to' => $end]);
        });
    }

    protected function isWfhEligible(Employee $employee, string $date): bool
    {
        return $employee->schedule_type === 'regular'
            && Carbon::parse($date)->isSaturday()
            && Schema::hasTable('saturday_wfh_policy_periods')
            && DB::table('saturday_wfh_policy_periods')
                ->whereDate('effective_from', '<=', $date)
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))
                ->exists();
    }

    protected function lastCoveredDate(): string
    {
        $today = today('Asia/Manila');

        return $today->isSaturday() && now('Asia/Manila')->format('H:i:s') > '11:00:00'
            ? $today->toDateString()
            : $today->copy()->subDay()->toDateString();
    }

    protected function date(string $value): Carbon
    {
        $date = Carbon::createFromFormat('!Y-m-d', $value, 'Asia/Manila');
        if (! $date || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Enter a valid date.');
        }

        return $date;
    }

    protected function ensureProspectiveStart(Carbon $start): void
    {
        $today = today('Asia/Manila');
        if ($start->isBefore($today)) {
            throw new InvalidArgumentException('Choose today or a future date. Historical attendance requires HR review.');
        }
        if ($start->isSameDay($today) && $today->isSaturday()
            && now('Asia/Manila')->format('H:i:s') > '11:00:00') {
            throw new InvalidArgumentException('Saturday attendance has already closed. Choose a future effective date.');
        }
    }

    protected function audit(string $action, int $id, ?User $actor, ?array $before, array $after): void
    {
        ActionHistory::query()->create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name ?: 'HR',
            'action' => $action,
            'model_type' => 'Saturday attendance policy',
            'model_id' => $id,
            'summary' => $action,
            'before_data' => $before,
            'after_data' => $after,
        ]);
    }
}
