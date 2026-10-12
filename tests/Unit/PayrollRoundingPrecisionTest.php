<?php

namespace Tests\Unit;

use App\Models\Dtr;
use App\Models\Employee;
use App\Models\PayrollCalculationSetting;
use App\Models\PayrollPeriod;
use App\Services\PayrollCalculator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class PayrollRoundingPrecisionTest extends TestCase
{
    private function calculate(string $method, mixed ...$arguments): mixed
    {
        return (new ReflectionMethod(PayrollCalculator::class, $method))->invoke(new PayrollCalculator, ...$arguments);
    }

    public function test_monthly_base_pay_does_not_rebuild_salary_from_rounded_dtr_rates(): void
    {
        foreach ([25000, 17500, 14500, 15500, 18500] as $salary) {
            $employee = new Employee(['rate_type' => 'monthly', 'monthly_rate' => $salary, 'daily_rate' => round($salary / 26, 2)]);
            $dtrs = new Collection([new Dtr(['daily_rate' => round($salary / 26, 2)])]);
            $rate = $this->calculate('ratePerDay', $employee, $dtrs);
            $this->assertEqualsWithDelta($salary / 26, $rate, 0.00000001);
            $this->assertSame((float) ($salary / 2), $this->calculate('basePay', false, $rate, 13, (float) $salary, 26, 13));
        }
    }

    public function test_period_divisors_are_used_without_rounding_rates(): void
    {
        $employee = new Employee(['rate_type' => 'monthly', 'monthly_rate' => 25000]);
        $settings = new PayrollCalculationSetting(['regular_work_days_per_month' => 25]);
        $this->assertSame(1000.0, $this->calculate('ratePerDay', $employee, null, $settings));
        $this->assertSame(12000.0, $this->calculate('basePay', false, 1000, 12, 25000, 25, 12));
    }

    public function test_minute_pay_rounds_the_final_amount_not_the_hours(): void
    {
        $hourly = 25000 / 26 / 8;
        $this->assertSame(2.0, $this->calculate('minutePay', 1, $hourly));
        $this->assertSame(12.02, $this->calculate('minutePay', 6, $hourly));
        $this->assertSame(2.5, $this->calculate('minutePay', 1, $hourly, 1.25));
        $this->assertSame(0.0, $this->calculate('minutePay', 0, $hourly));
    }

    public function test_daily_pay_keeps_daily_rate_and_payable_units(): void
    {
        $employee = new Employee(['rate_type' => 'daily', 'daily_rate' => 600]);
        $dtrs = new Collection([new Dtr(['daily_rate' => 575])]);
        $this->assertSame(575.0, $this->calculate('ratePerDay', $employee, $dtrs));
        $this->assertSame(7475.0, $this->calculate('basePay', true, 575, 13, null, 26, 13));
    }

    public function test_locked_period_never_falls_back_to_live_calculation(): void
    {
        $calculator = new class extends PayrollCalculator
        {
            protected function snapshotRows(PayrollPeriod $period, ?int $branchId = null): Collection
            {
                return new Collection([['net_pay' => 12500.02]]);
            }

            protected function liveRows(PayrollPeriod $period, ?int $branchId = null): Collection
            {
                throw new \LogicException('Locked payroll must not be recalculated.');
            }
        };
        $this->assertSame(12500.02, $calculator->rows(new PayrollPeriod(['is_locked' => true]))->first()['net_pay']);
    }
}
