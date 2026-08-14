<?php

namespace Tests\Unit;

use App\Models\Dtr;
use App\Services\OvertimeApprovalService;
use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OvertimeApprovalServiceTest extends TestCase
{
    #[DataProvider('statusScenarios')]
    public function test_it_identifies_overtime_approval_states(
        int $minutes,
        int $earlyMinutes,
        string $status,
        bool $approved,
        bool $earlyApproved,
        bool $isPending,
        bool $isApproved,
        bool $isRejected,
    ): void {
        $record = new Dtr([
            'overtime' => $minutes,
            'early_clock_in' => $earlyMinutes,
            'overtime_status' => $status,
            'overtime_approved' => $approved,
            'early_clock_in_approved' => $earlyApproved,
        ]);
        $service = app(OvertimeApprovalService::class);

        $this->assertSame($isPending, $service->isPending($record));
        $this->assertSame($isApproved, $service->isApproved($record));
        $this->assertSame($isRejected, $service->isRejected($record));
    }

    public static function statusScenarios(): array
    {
        return [
            'pending after threshold' => [30, 0, 'Pending', false, false, true, false, false],
            'pending early threshold' => [0, 30, 'Pending', false, false, true, false, false],
            'pending both thresholds' => [45, 35, 'n/a', false, false, true, false, false],
            'approved after overtime' => [60, 0, 'Approved', true, false, false, true, false],
            'approved early overtime' => [0, 60, 'Approved', false, true, false, true, false],
            'approved both overtime types' => [60, 45, 'Approved', true, true, false, true, false],
            'previous after overtime approval with early still outstanding' => [60, 45, 'Approved', true, false, true, false, false],
            'rejected' => [90, 45, 'Rejected', false, false, false, false, true],
            'below threshold' => [29, 29, 'Pending', false, false, false, false, false],
        ];
    }

    public function test_it_accepts_a_partial_credit_within_the_calculated_overtime(): void
    {
        $record = new Dtr(['overtime' => 60]);

        app(OvertimeApprovalService::class)->validateCreditedMinutes($record, 30, null);

        $this->addToAssertionCount(1);
    }

    public function test_it_accepts_zero_for_no_credited_early_or_after_overtime(): void
    {
        $record = new Dtr([
            'overtime' => 60,
            'early_clock_in' => 45,
        ]);

        app(OvertimeApprovalService::class)->validateCreditedMinutes($record, 0, 0);

        $this->addToAssertionCount(1);
    }

    #[DataProvider('invalidCreditedMinutes')]
    public function test_it_rejects_invalid_partial_overtime_values(int $minutes): void
    {
        $record = new Dtr(['overtime' => 60]);

        $this->expectException(DomainException::class);

        app(OvertimeApprovalService::class)->validateCreditedMinutes($record, $minutes, null);
    }

    public static function invalidCreditedMinutes(): array
    {
        return [
            'negative' => [-1],
            'one minute' => [1],
            'below minimum' => [29],
            'above calculated overtime' => [61],
        ];
    }

    public function test_it_accepts_separate_partial_credits_for_early_and_after_overtime(): void
    {
        $record = new Dtr([
            'overtime' => 60,
            'early_clock_in' => 45,
        ]);

        app(OvertimeApprovalService::class)->validateCreditedMinutes($record, 30, 35);

        $this->addToAssertionCount(1);
    }

    public function test_it_requires_each_eligible_overtime_component_to_be_at_least_thirty_minutes(): void
    {
        $record = new Dtr([
            'overtime' => 60,
            'early_clock_in' => 45,
        ]);

        $this->expectException(DomainException::class);

        app(OvertimeApprovalService::class)->validateCreditedMinutes($record, 30, 29);
    }
}
