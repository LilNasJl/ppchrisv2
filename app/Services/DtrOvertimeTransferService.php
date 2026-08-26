<?php

namespace App\Services;

use App\Models\Dtr;
use DomainException;

class DtrOvertimeTransferService
{
    public const FORMAT = 'ppchris-sicrc-dtr';

    public const VERSION = 1;

    /**
     * @return array<string, int|string>
     */
    public function exportPayload(Dtr $record): array
    {
        $earlyMinutes = max(0, (int) $record->early_clock_in);
        $overtimeMinutes = max(0, (int) $record->overtime);
        $earlyStatus = $this->componentStatus(
            $earlyMinutes,
            (bool) $record->early_clock_in_approved,
            $record->overtime_status,
        );
        $overtimeStatus = $this->componentStatus(
            $overtimeMinutes,
            (bool) $record->overtime_approved,
            $record->overtime_status,
        );
        $creditedEarly = $earlyStatus === OvertimeApprovalService::STATUS_APPROVED
            ? min($earlyMinutes, max(0, (int) $record->credited_early_clock_in))
            : 0;
        $creditedOvertime = $overtimeStatus === OvertimeApprovalService::STATUS_APPROVED
            ? min(
                $overtimeMinutes,
                max(0, (int) $record->credited_overtime - $creditedEarly),
            )
            : 0;

        return [
            'hris_transfer_format' => self::FORMAT,
            'hris_transfer_version' => self::VERSION,
            'early_overtime_minutes' => $earlyMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'early_overtime_status' => $earlyStatus,
            'after_overtime_status' => $overtimeStatus,
            'credited_early_overtime_minutes' => $creditedEarly,
            'credited_overtime_minutes' => $creditedOvertime,
        ];
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $calculation
     * @return array<string, mixed>
     */
    public function applyImportedPayload(array $source, array $calculation): array
    {
        if (blank($source['hris_transfer_format'] ?? null)) {
            return $calculation;
        }

        if (
            $source['hris_transfer_format'] !== self::FORMAT
            || (int) ($source['hris_transfer_version'] ?? 0) !== self::VERSION
        ) {
            throw new DomainException('The SIC/RC overtime transfer format or version is not supported.');
        }

        $early = $this->validateComponent(
            label: 'Early overtime',
            calculatedMinutes: max(0, (int) ($calculation['early_clock_in'] ?? 0)),
            transferredMinutes: $source['early_overtime_minutes'] ?? null,
            status: $source['early_overtime_status'] ?? null,
            creditedMinutes: $source['credited_early_overtime_minutes'] ?? null,
        );
        $overtime = $this->validateComponent(
            label: 'Overtime',
            calculatedMinutes: max(0, (int) ($calculation['overtime'] ?? 0)),
            transferredMinutes: $source['overtime_minutes'] ?? null,
            status: $source['after_overtime_status'] ?? null,
            creditedMinutes: $source['credited_overtime_minutes'] ?? null,
        );
        $creditedTotal = $early['credited'] + $overtime['credited'];
        $eligibleStatuses = collect([$early, $overtime])
            ->filter(fn (array $component): bool => $component['eligible'])
            ->pluck('status');

        $sharedStatus = match (true) {
            $eligibleStatuses->isEmpty() => OvertimeApprovalService::STATUS_NOT_APPLICABLE,
            $eligibleStatuses->every(fn (string $status): bool => $status === OvertimeApprovalService::STATUS_APPROVED) => OvertimeApprovalService::STATUS_APPROVED,
            $eligibleStatuses->every(fn (string $status): bool => $status === OvertimeApprovalService::STATUS_REJECTED) => OvertimeApprovalService::STATUS_REJECTED,
            default => OvertimeApprovalService::STATUS_PENDING,
        };

        return [
            ...$calculation,
            'credited_early_clock_in' => $early['credited'],
            'credited_overtime' => $creditedTotal,
            'credited_work_hrs' => max(0, (int) ($calculation['credited_work_hrs'] ?? 0)) + $creditedTotal,
            'early_clock_in_approved' => $early['status'] === OvertimeApprovalService::STATUS_APPROVED,
            'overtime_approved' => $overtime['status'] === OvertimeApprovalService::STATUS_APPROVED,
            'overtime_status' => $sharedStatus,
        ];
    }

    protected function componentStatus(int $minutes, bool $approved, mixed $sharedStatus): string
    {
        if ($minutes < OvertimeApprovalService::MINIMUM_MINUTES) {
            return OvertimeApprovalService::STATUS_NOT_APPLICABLE;
        }

        if ($approved) {
            return OvertimeApprovalService::STATUS_APPROVED;
        }

        return strcasecmp((string) $sharedStatus, OvertimeApprovalService::STATUS_REJECTED) === 0
            ? OvertimeApprovalService::STATUS_REJECTED
            : OvertimeApprovalService::STATUS_PENDING;
    }

    /**
     * @return array{eligible: bool, status: string, credited: int}
     */
    protected function validateComponent(
        string $label,
        int $calculatedMinutes,
        mixed $transferredMinutes,
        mixed $status,
        mixed $creditedMinutes,
    ): array {
        if (! is_numeric($transferredMinutes) || (int) $transferredMinutes !== $calculatedMinutes) {
            throw new DomainException("{$label} minutes do not match the D.T.R calculation.");
        }

        $status = match (strtolower(trim((string) $status))) {
            OvertimeApprovalService::STATUS_NOT_APPLICABLE => OvertimeApprovalService::STATUS_NOT_APPLICABLE,
            'pending' => OvertimeApprovalService::STATUS_PENDING,
            'approved' => OvertimeApprovalService::STATUS_APPROVED,
            'rejected' => OvertimeApprovalService::STATUS_REJECTED,
            default => trim((string) $status),
        };
        $allowedStatuses = [
            OvertimeApprovalService::STATUS_NOT_APPLICABLE,
            OvertimeApprovalService::STATUS_PENDING,
            OvertimeApprovalService::STATUS_APPROVED,
            OvertimeApprovalService::STATUS_REJECTED,
        ];

        if (! in_array($status, $allowedStatuses, true)) {
            throw new DomainException("{$label} approval status is invalid.");
        }

        $eligible = $calculatedMinutes >= OvertimeApprovalService::MINIMUM_MINUTES;

        if (! $eligible && $status !== OvertimeApprovalService::STATUS_NOT_APPLICABLE) {
            throw new DomainException("{$label} is below the approval threshold and must be marked n/a.");
        }

        if ($eligible && $status === OvertimeApprovalService::STATUS_NOT_APPLICABLE) {
            throw new DomainException("{$label} is eligible and must include a valid approval status.");
        }

        if (! is_numeric($creditedMinutes)) {
            throw new DomainException("{$label} credited minutes are invalid.");
        }

        $creditedMinutes = (int) $creditedMinutes;

        if ($creditedMinutes < 0 || $creditedMinutes > $calculatedMinutes) {
            throw new DomainException("{$label} credited minutes exceed the calculated amount.");
        }

        if ($status !== OvertimeApprovalService::STATUS_APPROVED && $creditedMinutes !== 0) {
            throw new DomainException("{$label} can only be credited when approved.");
        }

        if (
            $status === OvertimeApprovalService::STATUS_APPROVED
            && $creditedMinutes > 0
            && $creditedMinutes < OvertimeApprovalService::MINIMUM_MINUTES
        ) {
            throw new DomainException("{$label} credited minutes must be zero or at least 30 minutes.");
        }

        return [
            'eligible' => $eligible,
            'status' => $status,
            'credited' => $status === OvertimeApprovalService::STATUS_APPROVED ? $creditedMinutes : 0,
        ];
    }
}
