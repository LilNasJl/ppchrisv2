<?php

namespace Tests\Unit;

use App\Models\Dtr;
use App\Services\PayrollCalculator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DtrForApprovalStatusTest extends TestCase
{
    public function test_forgot_to_punch_requires_approval_even_for_legacy_absent_rows(): void
    {
        $record = (new Dtr)->forceFill([
            'schedule_type' => 'Forgot to Punch',
            'is_imported' => true,
            'is_absent' => true,
            'date_in' => '2026-08-24',
            'time_in' => '08:05:00',
        ]);

        $this->assertTrue($record->requiresAttendanceApproval());
    }

    public function test_other_incomplete_imported_punch_combinations_require_approval(): void
    {
        $record = (new Dtr)->forceFill([
            'schedule_type' => 'Regular',
            'is_imported' => true,
            'date_in' => '2026-08-24',
            'time_in' => '08:05:00',
            'date_out' => '2026-08-24',
            'time_out' => null,
        ]);

        $this->assertTrue($record->requiresAttendanceApproval());
    }

    public function test_actual_absence_and_corrected_attendance_are_finalized(): void
    {
        $absence = (new Dtr)->forceFill([
            'schedule_type' => 'Absent',
            'entry_source' => 'absence',
            'is_absent' => true,
        ]);
        $corrected = (new Dtr)->forceFill([
            'schedule_type' => 'Regular',
            'is_imported' => true,
            'is_absent' => false,
            'date_in' => '2026-08-24',
            'time_in' => '08:05:00',
            'date_out' => '2026-08-24',
            'time_out' => '17:05:00',
        ]);

        $this->assertFalse($absence->requiresAttendanceApproval());
        $this->assertFalse($corrected->requiresAttendanceApproval());
    }

    public function test_payroll_filters_pending_records_before_any_dtr_totals_are_calculated(): void
    {
        $pending = (new Dtr)->forceFill([
            'schedule_type' => 'Forgot to Punch',
            'is_imported' => true,
            'is_absent' => true,
            'late' => 60,
            'undertime' => 120,
            'credited_overtime' => 90,
            'credited_work_hrs' => 480,
        ]);
        $present = (new Dtr)->forceFill([
            'schedule_type' => 'Regular',
            'is_imported' => true,
            'date_in' => '2026-08-24',
            'time_in' => '08:00:00',
            'date_out' => '2026-08-24',
            'time_out' => '17:00:00',
            'credited_work_hrs' => 480,
        ]);

        $records = (new TestablePayrollCalculator)->finalized(collect([$pending, $present]));

        $this->assertCount(1, $records);
        $this->assertSame($present, $records->first());
        $this->assertSame(480, $records->sum('credited_work_hrs'));
    }
}

class TestablePayrollCalculator extends PayrollCalculator
{
    /**
     * @param  Collection<int, Dtr>  $records
     * @return Collection<int, Dtr>
     */
    public function finalized(Collection $records): Collection
    {
        return $this->finalizedDtrs($records);
    }
}
