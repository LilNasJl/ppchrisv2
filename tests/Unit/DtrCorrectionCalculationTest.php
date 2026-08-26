<?php

namespace Tests\Unit;

use App\Filament\SicRc\Widgets\SicRcDtrManageTable;
use App\Filament\Widgets\DtrManageTable;
use App\Services\DtrCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class DtrCorrectionCalculationTest extends TestCase
{
    #[DataProvider('scheduleScenarios')]
    public function test_corrected_punches_recalculate_supported_schedule_types(
        array $input,
        array $expected,
    ): void {
        $result = app(DtrCalculator::class)->calculate(...$input);

        foreach ($expected as $field => $value) {
            $this->assertSame($value, $result[$field], $field);
        }
    }

    public static function scheduleScenarios(): array
    {
        return [
            'normal monthly or daily schedule' => [[
                'dateIn' => '2026-08-20',
                'timeIn' => '08:02:00',
                'dateOut' => '2026-08-20',
                'timeOut' => '17:05:00',
                'scheduleStart' => '08:00:00',
                'scheduleEnd' => '18:00:00',
                'scheduleStartColumn' => 'reg_sched_start',
                'scheduleType' => 'Regular',
            ], ['late' => 2, 'undertime' => 55, 'work_hrs' => 483]],
            'saturday monthly schedule' => [[
                'dateIn' => '2026-08-22',
                'timeIn' => '08:00:00',
                'dateOut' => '2026-08-22',
                'timeOut' => '11:00:00',
                'scheduleStart' => '08:00:00',
                'scheduleEnd' => '11:00:00',
                'scheduleStartColumn' => 'saturday_schedule_start',
                'scheduleType' => 'Saturday',
            ], ['late' => 0, 'undertime' => 0, 'work_hrs' => 180]],
            'shift 1' => [[
                'dateIn' => '2026-08-20',
                'timeIn' => '06:00:00',
                'dateOut' => '2026-08-20',
                'timeOut' => '14:00:00',
                'scheduleStart' => '06:00:00',
                'scheduleEnd' => '14:00:00',
                'scheduleStartColumn' => 'shift1_sched_start',
                'scheduleType' => 'Shift1',
            ], ['late' => 0, 'undertime' => 0, 'work_hrs' => 480]],
            'shift 2' => [[
                'dateIn' => '2026-08-20',
                'timeIn' => '12:30:00',
                'dateOut' => '2026-08-20',
                'timeOut' => '20:30:00',
                'scheduleStart' => '12:30:00',
                'scheduleEnd' => '20:30:00',
                'scheduleStartColumn' => 'shift2_sched_start',
                'scheduleType' => 'Shift2',
            ], ['late' => 0, 'undertime' => 0, 'work_hrs' => 480]],
            'shift 3 overnight' => [[
                'dateIn' => '2026-08-20',
                'timeIn' => '20:00:00',
                'dateOut' => '2026-08-21',
                'timeOut' => '04:00:00',
                'scheduleStart' => '20:00:00',
                'scheduleEnd' => '04:00:00',
                'scheduleStartColumn' => 'shift3_sched_start',
                'scheduleType' => 'Shift3',
            ], ['late' => 0, 'undertime' => 0, 'work_hrs' => 480]],
            'late entry' => [[
                'dateIn' => '2026-08-20', 'timeIn' => '08:30:00',
                'dateOut' => '2026-08-20', 'timeOut' => '18:00:00',
                'scheduleStart' => '08:00:00', 'scheduleEnd' => '18:00:00',
                'scheduleStartColumn' => 'reg_sched_start', 'scheduleType' => 'Regular',
            ], ['late' => 30, 'undertime' => 0]],
            'undertime entry' => [[
                'dateIn' => '2026-08-20', 'timeIn' => '08:00:00',
                'dateOut' => '2026-08-20', 'timeOut' => '17:30:00',
                'scheduleStart' => '08:00:00', 'scheduleEnd' => '18:00:00',
                'scheduleStartColumn' => 'reg_sched_start', 'scheduleType' => 'Regular',
            ], ['late' => 0, 'undertime' => 30]],
            'early and after-schedule overtime' => [[
                'dateIn' => '2026-08-20', 'timeIn' => '07:30:00',
                'dateOut' => '2026-08-20', 'timeOut' => '18:30:00',
                'scheduleStart' => '08:00:00', 'scheduleEnd' => '18:00:00',
                'scheduleStartColumn' => 'reg_sched_start', 'scheduleType' => 'Regular',
            ], ['early_clock_in' => 30, 'overtime' => 30, 'overtime_status' => 'Pending']],
            'rest day overtime-only entry' => [[
                'dateIn' => '2026-08-23', 'timeIn' => '09:00:00',
                'dateOut' => '2026-08-23', 'timeOut' => '12:00:00',
                'scheduleStart' => null, 'scheduleEnd' => null,
                'scheduleStartColumn' => null, 'scheduleType' => 'Overtime',
                'overtimeOnly' => true,
            ], ['late' => 0, 'undertime' => 0, 'overtime' => 180, 'work_hrs' => 180]],
        ];
    }

    public function test_hr_and_sicrc_use_a_success_aware_repeatable_edit_contract(): void
    {
        $hrMethod = new ReflectionMethod(DtrManageTable::class, 'updateDtr');
        $sicRcMethod = new ReflectionMethod(SicRcDtrManageTable::class, 'updateDtr');

        $this->assertSame('bool', (string) $hrMethod->getReturnType());
        $this->assertSame('bool', (string) $sicRcMethod->getReturnType());
    }
}
