<?php

namespace Tests\Unit;

use App\Models\Dtr;
use App\Services\DtrDailyAggregationService;
use App\Services\DtrDayPartService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DtrDailyAggregationServiceTest extends TestCase
{
    public function test_rizzel_split_sessions_are_calculated_once(): void
    {
        $result = $this->calculate([
            ['08:01:57', '15:03:55'],
            ['17:01:30', '18:05:11'],
        ]);

        $this->assertSame(420, $result['worked_minutes']);
        $this->assertSame(540, $result['required_minutes']);
        $this->assertSame(1, $result['late']);
        $this->assertSame(117, $result['undertime']);
        $this->assertSame(5, $result['overtime']);
        $this->assertSame(1.0, $result['day_count']);
    }

    #[DataProvider('dailyScenarios')]
    public function test_daily_regular_scenarios(
        array $sessions,
        int $worked,
        int $required,
        int $undertime,
        float $dayCount,
    ): void {
        $result = $this->calculate($sessions);

        $this->assertSame($worked, $result['worked_minutes']);
        $this->assertSame($required, $result['required_minutes']);
        $this->assertSame($undertime, $result['undertime']);
        $this->assertSame($dayCount, $result['day_count']);
    }

    public static function dailyScenarios(): array
    {
        return [
            'emergency exit and return' => [[['08:00:00', '10:00:00'], ['10:30:00', '18:00:00']], 510, 540, 30, 1.0],
            'one-hour split-day shortage' => [[['08:00:00', '09:00:00'], ['11:00:00', '18:00:00']], 420, 540, 120, 1.0],
            'normal morning and afternoon' => [[['08:00:00', '12:00:00'], ['13:00:00', '18:00:00']], 540, 540, 0, 1.0],
            'morning half-day' => [[['08:00:00', '12:00:00']], 240, 240, 0, 0.5],
            'overlapping intervals merge once' => [[['08:00:00', '12:00:00'], ['11:00:00', '15:00:00']], 360, 540, 180, 1.0],
        ];
    }

    public function test_forgot_to_punch_is_not_used_when_completed_sessions_exist(): void
    {
        $records = collect([
            $this->record('08:00:00', '15:00:00'),
            $this->record('17:00:00', '18:00:00'),
            new Dtr([
                'date_in' => '2026-08-03',
                'time_in' => '18:30:00',
                'schedule_type' => 'Forgot to Punch',
                'is_absent' => true,
            ]),
        ]);

        $result = $this->service()->calculate($records, 8, 2);

        $this->assertSame(420, $result['worked_minutes']);
        $this->assertSame(120, $result['undertime']);
        $this->assertSame(1.0, $result['day_count']);
    }

    public function test_split_shift_charges_only_the_unworked_gap(): void
    {
        $result = $this->service()->calculateScheduled(collect([
            $this->scheduledRecord('Shift1', '04:30:00', '12:30:00', '04:30:00', '06:00:00'),
            $this->scheduledRecord('Shift1', '04:30:00', '12:30:00', '06:30:00', '12:30:00'),
        ]));

        $this->assertSame(480, $result['required_minutes']);
        $this->assertSame(450, $result['worked_minutes']);
        $this->assertSame(0, $result['late']);
        $this->assertSame(30, $result['undertime']);
        $this->assertSame(0, $result['overtime']);
    }

    public function test_return_scan_does_not_create_second_late_penalty(): void
    {
        $result = $this->service()->calculateScheduled(collect([
            $this->scheduledRecord('Shift1', '04:30:00', '12:30:00', '04:40:00', '06:00:00'),
            $this->scheduledRecord('Shift1', '04:30:00', '12:30:00', '06:30:00', '12:30:00'),
        ]));

        $this->assertSame(10, $result['late']);
        $this->assertSame(30, $result['undertime']);
        $this->assertSame(440, $result['worked_minutes']);
    }

    public function test_split_broken_segment_is_credited_once(): void
    {
        $result = $this->service()->calculateScheduled(collect([
            $this->scheduledRecord('Brkn1', '06:00:00', '10:00:00', '06:00:00', '07:00:00'),
            $this->scheduledRecord('Brkn1', '06:00:00', '10:00:00', '07:15:00', '10:00:00'),
        ]));

        $this->assertSame(240, $result['required_minutes']);
        $this->assertSame(225, $result['worked_minutes']);
        $this->assertSame(15, $result['undertime']);
    }

    public function test_overnight_shift_uses_the_original_duty_date(): void
    {
        $first = $this->scheduledRecord('Shift3', '20:30:00', '04:30:00', '20:30:00', '23:00:00');
        $second = $this->scheduledRecord('Shift3', '20:30:00', '04:30:00', '00:15:00', '04:30:00');
        $second->forceFill(['date_in' => '2026-08-04', 'date_out' => '2026-08-04']);

        $result = $this->service()->calculateScheduled(collect([$first, $second]));

        $this->assertSame('2026-08-03', $result['date']);
        $this->assertSame(405, $result['worked_minutes']);
        $this->assertSame(75, $result['undertime']);
    }

    public function test_unresolved_forgot_to_punch_is_for_approval_not_absent(): void
    {
        $record = (new InMemoryDtr)->forceFill([
            'date_in' => '2026-08-24',
            'time_in' => '08:05:00',
            'schedule_type' => 'Forgot to Punch',
            'is_absent' => true,
            'absence_minutes' => 480,
            'late' => 5,
            'undertime' => 475,
            'work_hrs' => 5,
        ]);

        (new TestableDtrDailyAggregationService(new DtrDayPartService))
            ->applyRecords(collect([$record]), 8, 2);

        $this->assertFalse($record->is_absent);
        $this->assertSame(0, $record->absence_minutes);
        $this->assertSame(0, $record->late);
        $this->assertSame(0, $record->undertime);
        $this->assertSame(0, $record->work_hrs);
        $this->assertTrue($record->requiresAttendanceApproval());
    }

    protected function calculate(array $sessions): array
    {
        return $this->service()->calculate(
            collect(array_map(fn (array $session): Dtr => $this->record(...$session), $sessions)),
            8,
            2,
        );
    }

    protected function service(): DtrDailyAggregationService
    {
        return new DtrDailyAggregationService(new DtrDayPartService);
    }

    protected function record(string $timeIn, string $timeOut): Dtr
    {
        return new Dtr([
            'date_in' => '2026-08-03',
            'time_in' => $timeIn,
            'date_out' => '2026-08-03',
            'time_out' => $timeOut,
            'schedule_type' => 'Regular',
            'schedule_start' => '08:00:00',
            'schedule_end' => '18:00:00',
        ]);
    }

    protected function scheduledRecord(string $type, string $start, string $end, string $timeIn, string $timeOut): Dtr
    {
        return new Dtr([
            'date_in' => '2026-08-03',
            'time_in' => $timeIn,
            'date_out' => '2026-08-03',
            'time_out' => $timeOut,
            'schedule_type' => $type,
            'schedule_start' => $start,
            'schedule_end' => $end,
        ]);
    }
}

class TestableDtrDailyAggregationService extends DtrDailyAggregationService
{
    /** @param Collection<int, Dtr> $records */
    public function applyRecords(Collection $records, float $workHoursPerDay, int $lateGraceMinutes): void
    {
        $this->apply($records, $workHoursPerDay, $lateGraceMinutes);
    }
}

class InMemoryDtr extends Dtr
{
    public function saveQuietly(array $options = [])
    {
        return true;
    }
}
