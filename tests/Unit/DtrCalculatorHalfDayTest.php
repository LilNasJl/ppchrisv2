<?php

namespace Tests\Unit;

use App\Services\DtrCalculator;
use App\Services\DtrDayPartService;
use Tests\TestCase;

class DtrCalculatorHalfDayTest extends TestCase
{
    public function test_wfh_and_regular_saturday_keep_saturday_work_minutes(): void
    {
        foreach (['WFH', 'Regular Saturday'] as $scheduleType) {
            $result = app(DtrCalculator::class)->calculate(
                dateIn: '2026-10-10',
                timeIn: '08:00:00',
                dateOut: '2026-10-10',
                timeOut: '11:00:00',
                scheduleStart: '08:00:00',
                scheduleEnd: '11:00:00',
                scheduleType: $scheduleType,
            );

            $this->assertSame(180, $result['work_hrs']);
        }
    }

    public function test_morning_half_day_only_deducts_time_inside_the_morning_window(): void
    {
        $result = app(DtrCalculator::class)->calculate(
            dateIn: '2026-07-21',
            timeIn: '08:15:00',
            dateOut: '2026-07-21',
            timeOut: '11:45:00',
            scheduleStart: '08:00:00',
            scheduleEnd: '12:00:00',
            scheduleStartColumn: 'reg_sched_start',
            scheduleType: 'Regular',
            dayPart: DtrDayPartService::MORNING,
        );

        $this->assertSame(15, $result['late']);
        $this->assertSame(15, $result['undertime']);
    }

    public function test_lunch_time_is_not_morning_overtime(): void
    {
        $result = app(DtrCalculator::class)->calculate(
            dateIn: '2026-07-21',
            timeIn: '08:00:00',
            dateOut: '2026-07-21',
            timeOut: '12:30:00',
            scheduleStart: '08:00:00',
            scheduleEnd: '12:00:00',
            scheduleStartColumn: 'reg_sched_start',
            scheduleType: 'Regular',
            dayPart: DtrDayPartService::MORNING,
        );

        $this->assertSame(0, $result['overtime']);
    }

    public function test_lunch_time_is_not_afternoon_early_clock_in(): void
    {
        $result = app(DtrCalculator::class)->calculate(
            dateIn: '2026-07-21',
            timeIn: '12:30:00',
            dateOut: '2026-07-21',
            timeOut: '18:00:00',
            scheduleStart: '13:00:00',
            scheduleEnd: '18:00:00',
            scheduleStartColumn: 'reg_sched_start',
            scheduleType: 'Regular',
            dayPart: DtrDayPartService::AFTERNOON,
        );

        $this->assertSame(0, $result['early_clock_in']);
    }

    public function test_early_clock_in_of_sixty_minutes_requires_overtime_approval(): void
    {
        $result = app(DtrCalculator::class)->calculate(
            dateIn: '2026-07-21',
            timeIn: '07:00:00',
            dateOut: '2026-07-21',
            timeOut: '12:00:00',
            scheduleStart: '08:00:00',
            scheduleEnd: '12:00:00',
            scheduleStartColumn: 'reg_sched_start',
            scheduleType: 'Regular',
            dayPart: DtrDayPartService::MORNING,
        );

        $this->assertSame(60, $result['early_clock_in']);
        $this->assertSame(0, $result['overtime']);
        $this->assertSame('Pending', $result['overtime_status']);
    }

    public function test_actual_overtime_of_sixty_minutes_requires_approval(): void
    {
        $result = app(DtrCalculator::class)->calculate(
            dateIn: '2026-07-21',
            timeIn: '13:00:00',
            dateOut: '2026-07-21',
            timeOut: '19:00:00',
            scheduleStart: '13:00:00',
            scheduleEnd: '18:00:00',
            scheduleStartColumn: 'reg_sched_start',
            scheduleType: 'Regular',
            dayPart: DtrDayPartService::AFTERNOON,
        );

        $this->assertSame(60, $result['overtime']);
        $this->assertSame('Pending', $result['overtime_status']);
    }

    public function test_early_clock_in_and_overtime_below_sixty_minutes_do_not_require_approval(): void
    {
        $earlyBelow = app(DtrCalculator::class)->calculate(
            dateIn: '2026-07-21',
            timeIn: '07:01:00',
            dateOut: '2026-07-21',
            timeOut: '12:00:00',
            scheduleStart: '08:00:00',
            scheduleEnd: '12:00:00',
            scheduleStartColumn: 'reg_sched_start',
            scheduleType: 'Regular',
            dayPart: DtrDayPartService::MORNING,
        );

        $this->assertSame(59, $earlyBelow['early_clock_in']);
        $this->assertSame('n/a', $earlyBelow['overtime_status']);

        $overtimeBelow = app(DtrCalculator::class)->calculate(
            dateIn: '2026-07-21',
            timeIn: '13:00:00',
            dateOut: '2026-07-21',
            timeOut: '18:59:00',
            scheduleStart: '13:00:00',
            scheduleEnd: '18:00:00',
            scheduleStartColumn: 'reg_sched_start',
            scheduleType: 'Regular',
            dayPart: DtrDayPartService::AFTERNOON,
        );

        $this->assertSame(59, $overtimeBelow['overtime']);
        $this->assertSame('n/a', $overtimeBelow['overtime_status']);
    }
}
