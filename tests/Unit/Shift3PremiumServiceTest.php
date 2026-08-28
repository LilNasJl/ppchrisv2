<?php

namespace Tests\Unit;

use App\Models\Dtr;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollSnapshot;
use App\Services\DtrDayPartService;
use App\Services\PayrollCalculator;
use App\Services\Shift3PremiumService;
use Tests\TestCase;

class Shift3PremiumServiceTest extends TestCase
{
    public function test_full_shift_uses_ten_percent_of_regular_daily_gross_only(): void
    {
        $record = $this->shift3Record([
            'daily_rate' => 800,
            'credited_work_hrs' => 570,
            'credited_overtime' => 90,
        ]);

        $service = app(Shift3PremiumService::class);

        $this->assertTrue($service->qualifies($record));
        $this->assertSame(480, $service->regularWorkMinutes($record));
        $this->assertSame(80.0, $service->amountForRecord($record, 700, 8));
    }

    public function test_partial_shift_uses_only_the_regular_worked_minutes(): void
    {
        $record = $this->shift3Record([
            'daily_rate' => 800,
            'credited_work_hrs' => 240,
        ]);

        $this->assertSame(40.0, app(Shift3PremiumService::class)->amountForRecord($record, 700, 8));
    }

    public function test_total_sums_each_eligible_shift_without_adding_overtime(): void
    {
        $records = collect([
            $this->shift3Record(['credited_work_hrs' => 480]),
            $this->shift3Record(['credited_work_hrs' => 540, 'credited_overtime' => 60]),
            $this->shift3Record(['credited_work_hrs' => 600, 'credited_overtime' => 120]),
        ]);

        $this->assertSame(240.0, app(Shift3PremiumService::class)->total($records, 800, 8));
    }

    public function test_non_hr_imported_or_invalid_rows_do_not_qualify(): void
    {
        $manual = $this->shift3Record([
            'is_imported' => false,
            'entry_source' => DtrDayPartService::SOURCE_MANUAL,
        ]);
        $shift2 = $this->shift3Record(['schedule_type' => 'Shift2']);
        $incomplete = $this->shift3Record(['date_out' => null, 'time_out' => null]);
        $sicRcPreview = (new EmployeeVisibleDtr)->forceFill($this->shift3Attributes());
        $service = app(Shift3PremiumService::class);

        $this->assertFalse($service->qualifies($manual));
        $this->assertFalse($service->qualifies($shift2));
        $this->assertFalse($service->qualifies($incomplete));
        $this->assertFalse($service->qualifies($sicRcPreview));
    }

    public function test_old_payroll_snapshots_default_the_new_premium_to_zero(): void
    {
        $snapshot = new PayrollSnapshot;
        $snapshot->data = [
            'gross_pay' => 1000.0,
        ];

        $data = app(PayrollCalculator::class)->snapshotData($snapshot);

        $this->assertSame(0.0, $data['shift3_premium']);
        $this->assertEquals(1000.0, $data['gross_pay']);
    }

    protected function shift3Record(array $overrides = []): Dtr
    {
        return (new Dtr)->forceFill([
            ...$this->shift3Attributes(),
            ...$overrides,
        ]);
    }

    protected function shift3Attributes(): array
    {
        return [
            'schedule_type' => 'Shift 3',
            'entry_source' => DtrDayPartService::SOURCE_IMPORTED,
            'is_imported' => true,
            'is_absent' => false,
            'date_in' => '2026-08-20',
            'time_in' => '20:00:00',
            'date_out' => '2026-08-21',
            'time_out' => '05:00:00',
            'daily_rate' => 800,
            'credited_work_hrs' => 480,
            'credited_overtime' => 0,
        ];
    }
}
