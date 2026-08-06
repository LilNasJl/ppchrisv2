<?php

namespace Tests\Unit;

use App\Models\Dtr;
use App\Services\PayrollCalculator;
use ReflectionMethod;
use Tests\TestCase;

class PayrollHalfDayInferenceTest extends TestCase
{
    public function test_monthly_payroll_infers_one_half_day_when_only_morning_is_present(): void
    {
        $minutes = $this->inferredMinutes(collect([
            $this->regularRecord('08:00:00', '12:00:00'),
        ]));

        $this->assertSame(240.0, $minutes);
    }

    public function test_monthly_payroll_does_not_infer_an_absence_when_both_halves_are_present(): void
    {
        $minutes = $this->inferredMinutes(collect([
            $this->regularRecord('08:00:00', '12:00:00'),
            $this->regularRecord('13:00:00', '18:00:00'),
        ]));

        $this->assertSame(0.0, $minutes);
    }

    public function test_approved_leave_covers_the_missing_half(): void
    {
        $leave = (new Dtr)->forceFill([
            'date_in' => '2026-07-21',
            'schedule_type' => 'Leave',
            'day_part' => 'afternoon',
            'entry_source' => 'leave',
            'is_absent' => false,
        ]);

        $minutes = $this->inferredMinutes(collect([
            $this->regularRecord('08:00:00', '12:00:00'),
            $leave,
        ]));

        $this->assertSame(0.0, $minutes);
    }

    private function inferredMinutes($records): float
    {
        $method = new ReflectionMethod(PayrollCalculator::class, 'inferredRegularHalfDayAbsenceMinutes');
        $method->setAccessible(true);

        return $method->invoke(new PayrollCalculator, $records, 8.0, 0.5);
    }

    private function regularRecord(string $timeIn, string $timeOut): Dtr
    {
        return (new Dtr)->forceFill([
            'date_in' => '2026-07-21',
            'time_in' => $timeIn,
            'date_out' => '2026-07-21',
            'time_out' => $timeOut,
            'schedule_type' => 'Regular',
            'day_part' => 'whole_day',
            'schedule_start' => '08:00:00',
            'schedule_end' => '18:00:00',
            'is_absent' => false,
        ]);
    }
}
