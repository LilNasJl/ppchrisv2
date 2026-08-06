<?php

namespace Tests\Unit;

use App\Services\DtrDayPartService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DtrDayPartServiceTest extends TestCase
{
    #[DataProvider('regularPunchProvider')]
    public function test_regular_punches_are_classified_by_work_window(
        string $timeIn,
        string $timeOut,
        string $expected,
    ): void {
        $actual = app(DtrDayPartService::class)->classifyRegularPunch(
            dateIn: '2026-07-21',
            timeIn: $timeIn,
            dateOut: '2026-07-21',
            timeOut: $timeOut,
            scheduleStart: '08:00:00',
            scheduleEnd: '18:00:00',
            scheduleType: 'Regular',
        );

        $this->assertSame($expected, $actual);
    }

    public static function regularPunchProvider(): array
    {
        return [
            'complete morning' => ['08:00:00', '12:00:00', DtrDayPartService::MORNING],
            'late morning' => ['08:15:00', '12:00:00', DtrDayPartService::MORNING],
            'complete afternoon' => ['13:00:00', '18:00:00', DtrDayPartService::AFTERNOON],
            'late afternoon' => ['13:15:00', '18:00:00', DtrDayPartService::AFTERNOON],
            'whole day' => ['08:00:00', '18:00:00', DtrDayPartService::WHOLE_DAY],
            'break only' => ['12:01:00', '12:59:00', DtrDayPartService::UNCLASSIFIED],
            '11:30 start may span both work periods' => ['11:30:00', '18:00:00', DtrDayPartService::WHOLE_DAY],
            '11:31 start is afternoon' => ['11:31:00', '18:00:00', DtrDayPartService::AFTERNOON],
            '11:31 start without afternoon work needs review' => ['11:31:00', '12:00:00', DtrDayPartService::UNCLASSIFIED],
        ];
    }

    public function test_saturday_regular_record_keeps_existing_whole_day_behavior(): void
    {
        $actual = app(DtrDayPartService::class)->classifyRegularPunch(
            dateIn: '2026-07-25',
            timeIn: '08:00:00',
            dateOut: '2026-07-25',
            timeOut: '11:00:00',
            scheduleStart: '08:00:00',
            scheduleEnd: '11:00:00',
            scheduleType: 'Regular',
        );

        $this->assertSame(DtrDayPartService::WHOLE_DAY, $actual);
    }

    public function test_shift_record_is_not_changed_by_regular_half_day_rules(): void
    {
        $actual = app(DtrDayPartService::class)->classifyRegularPunch(
            dateIn: '2026-07-21',
            timeIn: '08:00:00',
            dateOut: '2026-07-21',
            timeOut: '12:00:00',
            scheduleStart: '08:00:00',
            scheduleEnd: '12:00:00',
            scheduleType: 'Shift1',
        );

        $this->assertSame(DtrDayPartService::WHOLE_DAY, $actual);
    }
}
