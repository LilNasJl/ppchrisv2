<?php

namespace Tests\Unit;

use App\Models\PayrollPeriod;
use App\Services\PayrollPeriodLockService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use Tests\TestCase;

class PayrollPeriodLockValidationTest extends TestCase
{
    public function test_manual_lock_is_blocked_before_period_end_date(): void
    {
        $period = (new PayrollPeriod)->forceFill(['date_end' => '2026-08-25']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be locked before its end date');

        (new TestablePayrollPeriodLockService)->assertManualLock(
            $period,
            Carbon::parse('2026-08-24', 'Asia/Manila'),
        );
    }

    public function test_manual_lock_is_allowed_on_period_end_date(): void
    {
        $period = (new PayrollPeriod)->forceFill(['date_end' => '2026-08-25']);

        (new TestablePayrollPeriodLockService)->assertManualLock(
            $period,
            Carbon::parse('2026-08-25', 'Asia/Manila'),
        );

        $this->addToAssertionCount(1);
    }
}

class TestablePayrollPeriodLockService extends PayrollPeriodLockService
{
    public function assertManualLock(PayrollPeriod $period, CarbonInterface $date): void
    {
        $this->assertManualLockAllowed($period, $date);
    }
}
