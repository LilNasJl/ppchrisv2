<?php

namespace Tests\Unit;

use App\Models\Dtr;
use App\Services\DtrAttendanceUnitService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DtrAttendanceUnitServiceTest extends TestCase
{
    public function test_one_broken_segment_is_half_a_day(): void
    {
        $records = collect([$this->record('Brkn1')]);

        $this->assertSame(0.5, $this->service()->attendanceDays($records));
        $this->assertSame(0.5, $this->service()->dailyRatePayUnits($records));
    }

    public function test_two_unique_broken_segments_are_one_day(): void
    {
        $records = collect([
            $this->record('Brkn1'),
            $this->record('Broken Shift 2'),
        ]);

        $this->assertSame(1.0, $this->service()->attendanceDays($records));
        $this->assertSame(1.0, $this->service()->dailyRatePayUnits($records));
    }

    public function test_duplicate_broken_segment_is_not_counted_twice(): void
    {
        $records = collect([
            $this->record('Brkn1'),
            $this->record('Broken Shift 1'),
        ]);

        $this->assertSame(0.5, $this->service()->attendanceDays($records));
        $this->assertSame(0.5, $this->service()->dailyRatePayUnits($records));
    }

    public function test_overtime_does_not_add_an_attendance_day(): void
    {
        $records = collect([
            $this->record('Brkn1'),
            $this->record('Overtime'),
        ]);

        $this->assertSame(0.5, $this->service()->attendanceDays($records));
        $this->assertSame(0.5, $this->service()->dailyRatePayUnits($records));
    }

    public function test_non_broken_daily_rate_entries_keep_existing_entry_based_behavior(): void
    {
        $records = collect([
            $this->record('Shift1'),
            $this->record('Shift2'),
        ]);

        $this->assertSame(1.0, $this->service()->attendanceDays($records));
        $this->assertSame(2.0, $this->service()->dailyRatePayUnits($records));
    }

    private function service(): DtrAttendanceUnitService
    {
        return new DtrAttendanceUnitService;
    }

    private function record(string $scheduleType): Dtr
    {
        return (new Dtr)->forceFill([
            'date_in' => '2026-07-21',
            'schedule_type' => $scheduleType,
            'is_absent' => false,
        ]);
    }
}
