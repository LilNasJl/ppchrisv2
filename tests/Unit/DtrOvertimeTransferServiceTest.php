<?php

namespace Tests\Unit;

use App\Models\Dtr;
use App\Services\DtrOvertimeTransferService;
use App\Services\OvertimeApprovalService;
use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DtrOvertimeTransferServiceTest extends TestCase
{
    #[DataProvider('transferScenarios')]
    public function test_it_preserves_valid_component_approval_states(
        int $earlyMinutes,
        int $overtimeMinutes,
        string $earlyStatus,
        string $overtimeStatus,
        int $creditedEarly,
        int $creditedOvertime,
        string $expectedSharedStatus,
        bool $expectedEarlyApproved,
        bool $expectedOvertimeApproved,
    ): void {
        $service = app(DtrOvertimeTransferService::class);
        $calculation = $this->calculation($earlyMinutes, $overtimeMinutes);
        $result = $service->applyImportedPayload([
            ...$this->payload($earlyMinutes, $overtimeMinutes),
            'early_overtime_status' => $earlyStatus,
            'after_overtime_status' => $overtimeStatus,
            'credited_early_overtime_minutes' => $creditedEarly,
            'credited_overtime_minutes' => $creditedOvertime,
        ], $calculation);

        $this->assertSame($expectedSharedStatus, $result['overtime_status']);
        $this->assertSame($expectedEarlyApproved, $result['early_clock_in_approved']);
        $this->assertSame($expectedOvertimeApproved, $result['overtime_approved']);
        $this->assertSame($creditedEarly, $result['credited_early_clock_in']);
        $this->assertSame($creditedEarly + $creditedOvertime, $result['credited_overtime']);
        $this->assertSame(480 + $creditedEarly + $creditedOvertime, $result['credited_work_hrs']);
    }

    public static function transferScenarios(): array
    {
        return [
            'no overtime' => [0, 0, 'n/a', 'n/a', 0, 0, 'n/a', false, false],
            'early pending' => [30, 0, 'Pending', 'n/a', 0, 0, 'Pending', false, false],
            'early approved' => [45, 0, 'Approved', 'n/a', 30, 0, 'Approved', true, false],
            'early rejected' => [45, 0, 'Rejected', 'n/a', 0, 0, 'Rejected', false, false],
            'overtime pending' => [0, 60, 'n/a', 'Pending', 0, 0, 'Pending', false, false],
            'overtime approved' => [0, 60, 'n/a', 'Approved', 0, 45, 'Approved', false, true],
            'overtime rejected' => [0, 60, 'n/a', 'Rejected', 0, 0, 'Rejected', false, false],
            'both approved' => [45, 60, 'Approved', 'Approved', 30, 45, 'Approved', true, true],
            'early approved and overtime pending' => [45, 60, 'Approved', 'Pending', 30, 0, 'Pending', true, false],
        ];
    }

    public function test_export_contains_independent_approval_and_credit_fields(): void
    {
        $record = new Dtr([
            'early_clock_in' => 45,
            'overtime' => 60,
            'early_clock_in_approved' => true,
            'overtime_approved' => true,
            'overtime_status' => OvertimeApprovalService::STATUS_APPROVED,
            'credited_early_clock_in' => 30,
            'credited_overtime' => 75,
        ]);

        $payload = app(DtrOvertimeTransferService::class)->exportPayload($record);

        $this->assertSame('ppchris-sicrc-dtr', $payload['hris_transfer_format']);
        $this->assertSame(1, $payload['hris_transfer_version']);
        $this->assertSame('Approved', $payload['early_overtime_status']);
        $this->assertSame('Approved', $payload['after_overtime_status']);
        $this->assertSame(30, $payload['credited_early_overtime_minutes']);
        $this->assertSame(45, $payload['credited_overtime_minutes']);
    }

    public function test_plain_biometric_payload_keeps_normal_calculation_behavior(): void
    {
        $calculation = $this->calculation(45, 60);

        $result = app(DtrOvertimeTransferService::class)
            ->applyImportedPayload([], $calculation);

        $this->assertSame($calculation, $result);
    }

    #[DataProvider('invalidPayloads')]
    public function test_it_rejects_invalid_or_untrusted_approval_metadata(array $overrides): void
    {
        $this->expectException(DomainException::class);

        app(DtrOvertimeTransferService::class)->applyImportedPayload([
            ...$this->payload(45, 60),
            'early_overtime_status' => 'Approved',
            'after_overtime_status' => 'Approved',
            'credited_early_overtime_minutes' => 30,
            'credited_overtime_minutes' => 45,
            ...$overrides,
        ], $this->calculation(45, 60));
    }

    public static function invalidPayloads(): array
    {
        return [
            'unknown transfer version' => [['hris_transfer_version' => 99]],
            'invalid status' => [['after_overtime_status' => 'Confirmed']],
            'mismatched calculated minutes' => [['overtime_minutes' => 61]],
            'pending overtime cannot carry credit' => [[
                'after_overtime_status' => 'Pending',
                'credited_overtime_minutes' => 30,
            ]],
            'credit cannot exceed overtime' => [['credited_overtime_minutes' => 61]],
            'positive credit below threshold' => [['credited_overtime_minutes' => 29]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(int $earlyMinutes, int $overtimeMinutes): array
    {
        return [
            'hris_transfer_format' => DtrOvertimeTransferService::FORMAT,
            'hris_transfer_version' => DtrOvertimeTransferService::VERSION,
            'early_overtime_minutes' => $earlyMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'early_overtime_status' => $earlyMinutes >= 30 ? 'Pending' : 'n/a',
            'after_overtime_status' => $overtimeMinutes >= 30 ? 'Pending' : 'n/a',
            'credited_early_overtime_minutes' => 0,
            'credited_overtime_minutes' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculation(int $earlyMinutes, int $overtimeMinutes): array
    {
        return [
            'early_clock_in' => $earlyMinutes,
            'overtime' => $overtimeMinutes,
            'credited_early_clock_in' => 0,
            'credited_overtime' => 0,
            'credited_work_hrs' => 480,
            'overtime_status' => ($earlyMinutes >= 30 || $overtimeMinutes >= 30) ? 'Pending' : 'n/a',
            'early_clock_in_approved' => false,
            'overtime_approved' => false,
        ];
    }
}
