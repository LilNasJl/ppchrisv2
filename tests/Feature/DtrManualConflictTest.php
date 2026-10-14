<?php

namespace Tests\Feature;

use App\Filament\Widgets\DtrManageTable;
use App\Models\Dtr;
use App\Services\DtrDayPartService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

class TestableDtrManageTable extends DtrManageTable
{
    /** @param array<string, mixed> $data */
    public function conflicts(array $data, ?int $exceptRecordId = null): bool
    {
        return $this->hasConflictingDtrEntry($data, $exceptRecordId);
    }

    protected function getScopedDtrQuery(): Builder
    {
        return Dtr::query()->where('fingerprint_id', '999999');
    }
}

#[RequiresPhpExtension('pdo_sqlite')]
class DtrManualConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_add_and_edit_non_overlapping_sessions_in_one_day_part(): void
    {
        $first = $this->record('2026-07-21', '04:30:00', '2026-07-21', '06:00:00');
        $this->record('2026-07-21', '06:30:00', '2026-07-21', '12:30:00');
        $widget = new TestableDtrManageTable;

        $this->assertFalse($widget->conflicts($this->candidate('2026-07-21', '13:00:00', '2026-07-21', '14:00:00')));
        $this->assertFalse($widget->conflicts($this->candidate('2026-07-21', '04:30:00', '2026-07-21', '06:15:00'), $first->id));
        $this->assertTrue($widget->conflicts($this->candidate('2026-07-21', '04:30:00', '2026-07-21', '06:45:00'), $first->id));
    }

    public function test_hr_rejects_overlapping_or_duplicate_sessions(): void
    {
        $this->record('2026-07-21', '08:00:00', '2026-07-21', '10:00:00');
        $widget = new TestableDtrManageTable;

        $this->assertTrue($widget->conflicts($this->candidate('2026-07-21', '09:30:00', '2026-07-21', '11:00:00')));
        $this->assertTrue($widget->conflicts($this->candidate('2026-07-21', '08:00:00', '2026-07-21', '10:00:00')));
        $this->assertFalse($widget->conflicts($this->candidate('2026-07-21', '10:00:00', '2026-07-21', '18:00:00')));
    }

    public function test_hr_rejects_a_session_after_an_unresolved_punch(): void
    {
        $this->record('2026-07-21', '08:00:00', null, null, 'Forgot to Punch');
        $widget = new TestableDtrManageTable;

        $this->assertTrue($widget->conflicts($this->candidate('2026-07-21', '10:30:00', '2026-07-21', '18:00:00')));
    }

    public function test_hr_still_respects_leave_and_absence_day_parts(): void
    {
        $this->record('2026-07-21', null, null, null, 'Leave', DtrDayPartService::MORNING, DtrDayPartService::SOURCE_LEAVE);
        $widget = new TestableDtrManageTable;

        $this->assertTrue($widget->conflicts($this->candidate('2026-07-21', '08:00:00', '2026-07-21', '12:00:00', DtrDayPartService::MORNING)));
        $this->assertFalse($widget->conflicts($this->candidate('2026-07-21', '13:00:00', '2026-07-21', '18:00:00', DtrDayPartService::AFTERNOON)));
    }

    public function test_hr_checks_sessions_that_cross_midnight(): void
    {
        $this->record('2026-07-21', '22:00:00', '2026-07-22', '02:00:00');
        $widget = new TestableDtrManageTable;

        $this->assertTrue($widget->conflicts($this->candidate('2026-07-22', '01:00:00', '2026-07-22', '03:00:00')));
        $this->assertFalse($widget->conflicts($this->candidate('2026-07-22', '02:00:00', '2026-07-22', '04:00:00')));
    }

    /** @return array<string, mixed> */
    private function candidate(string $dateIn, string $timeIn, string $dateOut, string $timeOut, string $dayPart = DtrDayPartService::WHOLE_DAY): array
    {
        return [
            'date_in' => $dateIn,
            'time_in' => $timeIn,
            'date_out' => $dateOut,
            'time_out' => $timeOut,
            'schedule_type' => 'Shift1',
            'day_part' => $dayPart,
        ];
    }

    private function record(
        string $dateIn,
        ?string $timeIn,
        ?string $dateOut,
        ?string $timeOut,
        string $scheduleType = 'Shift1',
        string $dayPart = DtrDayPartService::WHOLE_DAY,
        string $source = DtrDayPartService::SOURCE_MANUAL,
    ): Dtr {
        return Dtr::withoutEvents(fn (): Dtr => Dtr::create([
            'fingerprint_id' => '999999',
            'date_in' => $dateIn,
            'time_in' => $timeIn,
            'date_out' => $dateOut,
            'time_out' => $timeOut,
            'schedule_type' => $scheduleType,
            'day_part' => $dayPart,
            'entry_source' => $source,
        ]));
    }
}
