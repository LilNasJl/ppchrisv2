<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\Leave;
use App\Models\PayrollPeriod;
use App\Services\DtrDayPartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalfDayDtrLeaveLateUndertimeTest extends TestCase
{
    use RefreshDatabase;

    protected DtrDayPartService $service;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DtrDayPartService::class);

        $branch = Branch::create([
            'branch_name' => 'Main Branch',
            'branch_address' => '123 Main St',
        ]);
        $this->employee = Employee::create([
            'branch_id' => $branch->id,
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'rate_type' => 'monthly',
            'monthly_rate' => 16500.00,
        ]);
    }

    public function test_afternoon_duty_without_leave_has_240m_late(): void
    {
        $dtr = new EmployeeVisibleDtr([
            'employee_id' => $this->employee->id,
            'date_in' => '2026-11-11',
            'time_in' => '12:54:53',
            'date_out' => '2026-11-11',
            'time_out' => '18:03:11',
            'schedule_start' => '13:00:00',
            'schedule_end' => '18:00:00',
            'day_part' => DtrDayPartService::AFTERNOON,
            'schedule_type' => 'Regular',
            'late' => 0,
            'undertime' => 0,
        ]);

        $this->assertSame(240, $this->service->effectiveLate($dtr));
        $this->assertSame(0, $this->service->effectiveUndertime($dtr));
        $this->assertSame('-', $this->service->leaveApprovalLabel($dtr));
    }

    public function test_afternoon_duty_with_approved_morning_leave_overrides_late(): void
    {
        $leave = Leave::create([
            'employee_id' => $this->employee->id,
            'leave_type' => 'Sick Leave',
            'reason' => 'Doctor appointment',
            'leave_from' => '2026-11-11',
            'leave_to' => '2026-11-11',
            'is_half_day' => true,
            'half_day_period' => 'morning',
            'status' => 'Approved',
        ]);

        $dtr = new EmployeeVisibleDtr([
            'employee_id' => $this->employee->id,
            'date_in' => '2026-11-11',
            'time_in' => '12:54:53',
            'date_out' => '2026-11-11',
            'time_out' => '18:03:11',
            'schedule_start' => '13:00:00',
            'schedule_end' => '18:00:00',
            'day_part' => DtrDayPartService::AFTERNOON,
            'schedule_type' => 'Regular',
            'late' => 0,
            'undertime' => 0,
        ]);

        $this->assertSame(0, $this->service->effectiveLate($dtr));
        $this->assertSame(0, $this->service->effectiveUndertime($dtr));
        $this->assertSame('Approved: Sick Leave (Morning)', $this->service->leaveApprovalLabel($dtr));
    }

    public function test_afternoon_duty_with_pending_morning_leave_does_not_override_late(): void
    {
        $leave = Leave::create([
            'employee_id' => $this->employee->id,
            'leave_type' => 'Sick Leave',
            'reason' => 'Doctor appointment',
            'leave_from' => '2026-11-11',
            'leave_to' => '2026-11-11',
            'is_half_day' => true,
            'half_day_period' => 'morning',
            'status' => 'Pending',
        ]);

        $dtr = new EmployeeVisibleDtr([
            'employee_id' => $this->employee->id,
            'date_in' => '2026-11-11',
            'time_in' => '12:54:53',
            'date_out' => '2026-11-11',
            'time_out' => '18:03:11',
            'schedule_start' => '13:00:00',
            'schedule_end' => '18:00:00',
            'day_part' => DtrDayPartService::AFTERNOON,
            'schedule_type' => 'Regular',
            'late' => 0,
            'undertime' => 0,
        ]);

        $this->assertSame(240, $this->service->effectiveLate($dtr));
        $this->assertSame(0, $this->service->effectiveUndertime($dtr));
        $this->assertSame('Pending: Sick Leave (Morning)', $this->service->leaveApprovalLabel($dtr));
    }

    public function test_morning_duty_without_leave_has_240m_undertime(): void
    {
        $dtr = new EmployeeVisibleDtr([
            'employee_id' => $this->employee->id,
            'date_in' => '2026-11-25',
            'time_in' => '08:00:00',
            'date_out' => '2026-11-25',
            'time_out' => '12:00:00',
            'schedule_start' => '08:00:00',
            'schedule_end' => '12:00:00',
            'day_part' => DtrDayPartService::MORNING,
            'schedule_type' => 'Regular',
            'late' => 0,
            'undertime' => 0,
        ]);

        $this->assertSame(0, $this->service->effectiveLate($dtr));
        $this->assertSame(240, $this->service->effectiveUndertime($dtr));
        $this->assertSame('-', $this->service->leaveApprovalLabel($dtr));
    }

    public function test_morning_duty_with_approved_afternoon_leave_overrides_undertime(): void
    {
        $leave = Leave::create([
            'employee_id' => $this->employee->id,
            'leave_type' => 'Vacation Leave',
            'reason' => 'Personal matter',
            'leave_from' => '2026-11-25',
            'leave_to' => '2026-11-25',
            'is_half_day' => true,
            'half_day_period' => 'afternoon',
            'status' => 'Approved',
        ]);

        $dtr = new EmployeeVisibleDtr([
            'employee_id' => $this->employee->id,
            'date_in' => '2026-11-25',
            'time_in' => '08:00:00',
            'date_out' => '2026-11-25',
            'time_out' => '12:00:00',
            'schedule_start' => '08:00:00',
            'schedule_end' => '12:00:00',
            'day_part' => DtrDayPartService::MORNING,
            'schedule_type' => 'Regular',
            'late' => 0,
            'undertime' => 0,
        ]);

        $this->assertSame(0, $this->service->effectiveLate($dtr));
        $this->assertSame(0, $this->service->effectiveUndertime($dtr));
        $this->assertSame('Approved: Vacation Leave (Afternoon)', $this->service->leaveApprovalLabel($dtr));
    }
}
