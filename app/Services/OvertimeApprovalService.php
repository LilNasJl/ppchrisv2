<?php

namespace App\Services;

use App\Models\Dtr;
use App\Models\PayrollPeriod;
use DomainException;
use Illuminate\Support\Facades\DB;

class OvertimeApprovalService
{
    public const MINIMUM_MINUTES = 30;

    public const STATUS_NOT_APPLICABLE = 'n/a';

    public const STATUS_PENDING = 'Pending';

    public const STATUS_APPROVED = 'Approved';

    public const STATUS_REJECTED = 'Rejected';

    public function approve(
        Dtr $record,
        ?int $creditedOvertimeMinutes = null,
        ?int $creditedEarlyMinutes = null,
    ): int {
        return $this->transition(
            $record,
            self::STATUS_APPROVED,
            $creditedOvertimeMinutes,
            $creditedEarlyMinutes,
        );
    }

    public function reject(Dtr $record): int
    {
        return $this->transition($record, self::STATUS_REJECTED);
    }

    public function undo(Dtr $record): int
    {
        return $this->transition($record, self::STATUS_PENDING);
    }

    public function isPending(Dtr $record): bool
    {
        $status = $this->normalizedStatus($record);

        return $this->hasApprovableOvertime($record)
            && ! $this->allEligibleComponentsApproved($record)
            && $status !== self::STATUS_REJECTED;
    }

    public function isApproved(Dtr $record): bool
    {
        return $this->hasApprovableOvertime($record)
            && $this->allEligibleComponentsApproved($record);
    }

    public function isRejected(Dtr $record): bool
    {
        return $this->hasApprovableOvertime($record)
            && ! $this->allEligibleComponentsApproved($record)
            && $this->normalizedStatus($record) === self::STATUS_REJECTED;
    }

    public function hasPendingForPeriod(PayrollPeriod $period): bool
    {
        return Dtr::query()
            ->where('payroll_period_id', $period->id)
            ->finalizedAttendance()
            ->where(function ($query): void {
                $query
                    ->where(function ($query): void {
                        $query->where('overtime', '>=', 30)
                            ->where(function ($query): void {
                                $query->where('overtime_approved', false)
                                    ->orWhereNull('overtime_approved');
                            });
                    })
                    ->orWhere(function ($query): void {
                        $query->where('early_clock_in', '>=', 30)
                            ->where(function ($query): void {
                                $query->where('early_clock_in_approved', false)
                                    ->orWhereNull('early_clock_in_approved');
                            });
                    });
            })
            ->where(function ($query): void {
                $query
                    ->whereNull('overtime_status')
                    ->orWhere('overtime_status', '!=', self::STATUS_REJECTED);
            })
            ->exists();
    }

    public function validateCreditedMinutes(
        Dtr $record,
        ?int $creditedOvertimeMinutes,
        ?int $creditedEarlyMinutes,
    ): void {
        if ($this->hasEligibleOvertime($record)) {
            $this->validateComponent(
                'Credited overtime',
                $creditedOvertimeMinutes,
                max(0, (int) $record->overtime),
            );
        }

        if ($this->hasEligibleEarlyOvertime($record)) {
            $this->validateComponent(
                'Credited early overtime',
                $creditedEarlyMinutes,
                max(0, (int) $record->early_clock_in),
            );
        }
    }

    public function hasEligibleOvertime(Dtr $record): bool
    {
        return (int) $record->overtime >= 30;
    }

    public function hasEligibleEarlyOvertime(Dtr $record): bool
    {
        return (int) $record->early_clock_in >= 30;
    }

    public function hasApprovableOvertime(Dtr $record): bool
    {
        return ! $record->requiresAttendanceApproval()
            && ($this->hasEligibleOvertime($record) || $this->hasEligibleEarlyOvertime($record));
    }

    public function defaultCreditedOvertime(Dtr $record): int
    {
        $calculated = max(0, (int) $record->overtime);
        $stored = max(
            0,
            (int) $record->credited_overtime - (int) $record->credited_early_clock_in,
        );

        return (bool) $record->overtime_approved && $stored >= 30
            ? min($calculated, $stored)
            : $calculated;
    }

    public function defaultCreditedEarlyOvertime(Dtr $record): int
    {
        $calculated = max(0, (int) $record->early_clock_in);
        $stored = max(0, (int) $record->credited_early_clock_in);

        return (bool) $record->early_clock_in_approved && $stored >= 30
            ? min($calculated, $stored)
            : $calculated;
    }

    protected function transition(
        Dtr $record,
        string $targetStatus,
        ?int $creditedOvertimeMinutes = null,
        ?int $creditedEarlyMinutes = null,
    ): int {
        $recordId = (int) $record->getKey();

        if ($recordId < 1) {
            throw new DomainException('The selected overtime record does not exist.');
        }

        $result = DB::transaction(function () use (
            $record,
            $recordId,
            $targetStatus,
            $creditedOvertimeMinutes,
            $creditedEarlyMinutes,
        ): int {
            $record = $record->newQuery()
                ->with('payrollPeriod')
                ->whereKey($recordId)
                ->lockForUpdate()
                ->first();

            if (! $record) {
                throw new DomainException('The selected overtime record no longer exists.');
            }

            $this->assertTransitionAllowed($record, $targetStatus);

            $baseCreditedMinutes = max(
                0,
                (int) $record->credited_work_hrs - (int) $record->credited_overtime,
            );
            $approved = $targetStatus === self::STATUS_APPROVED;
            $approvedOvertimeMinutes = $approved && $this->hasEligibleOvertime($record)
                ? (int) $creditedOvertimeMinutes
                : 0;
            $approvedEarlyMinutes = $approved && $this->hasEligibleEarlyOvertime($record)
                ? (int) $creditedEarlyMinutes
                : 0;

            if ($approved) {
                $this->validateCreditedMinutes(
                    $record,
                    $creditedOvertimeMinutes,
                    $creditedEarlyMinutes,
                );
            }

            $totalCreditedOvertime = $approvedOvertimeMinutes + $approvedEarlyMinutes;

            $record->forceFill([
                'early_clock_in_approved' => $approved && $this->hasEligibleEarlyOvertime($record),
                'overtime_approved' => $approved && $this->hasEligibleOvertime($record),
                'overtime_status' => $targetStatus,
                'credited_early_clock_in' => $approvedEarlyMinutes,
                'credited_overtime' => $totalCreditedOvertime,
                'credited_work_hrs' => $baseCreditedMinutes + $totalCreditedOvertime,
            ])->save();

            return 1;
        });

        if (
            $targetStatus !== self::STATUS_PENDING
            && $record->getTable() === (new Dtr)->getTable()
            && filled($record->payroll_period_id)
        ) {
            $period = PayrollPeriod::query()->find($record->payroll_period_id);

            if ($period) {
                app(PayrollPeriodLockService::class)->retryDuePeriod($period);
            }
        }

        return $result;
    }

    protected function assertTransitionAllowed(Dtr $record, string $targetStatus): void
    {
        if ((bool) $record->is_locked || (bool) $record->payrollPeriod?->is_locked) {
            throw new DomainException('Overtime cannot be changed because the payroll period is locked.');
        }

        if (! $this->hasApprovableOvertime($record)) {
            throw new DomainException('Only early or after-schedule overtime of at least 30 minutes can be approved or rejected.');
        }

        if ($targetStatus === self::STATUS_PENDING) {
            if (! $this->isApproved($record) && ! $this->isRejected($record)) {
                throw new DomainException('Only an approved or rejected overtime record can be undone.');
            }

            return;
        }

        if (! $this->isPending($record)) {
            throw new DomainException('Only pending overtime records can be approved or rejected.');
        }
    }

    protected function normalizedStatus(Dtr $record): string
    {
        return match (strtolower(trim((string) $record->overtime_status))) {
            'approved' => self::STATUS_APPROVED,
            'rejected' => self::STATUS_REJECTED,
            default => self::STATUS_PENDING,
        };
    }

    protected function allEligibleComponentsApproved(Dtr $record): bool
    {
        return (! $this->hasEligibleOvertime($record) || (bool) $record->overtime_approved)
            && (! $this->hasEligibleEarlyOvertime($record) || (bool) $record->early_clock_in_approved);
    }

    protected function validateComponent(string $label, ?int $creditedMinutes, int $calculatedMinutes): void
    {
        if ($creditedMinutes === null || $creditedMinutes < 0 || ($creditedMinutes > 0 && $creditedMinutes < 30)) {
            throw new DomainException("{$label} must be 0 or at least 30 minutes.");
        }

        if ($creditedMinutes > $calculatedMinutes) {
            throw new DomainException("{$label} cannot exceed the calculated {$calculatedMinutes} minutes.");
        }
    }
}
