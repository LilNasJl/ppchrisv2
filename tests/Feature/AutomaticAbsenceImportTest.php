<?php

namespace Tests\Feature;

use App\Filament\Pages\DtrManage;
use App\Models\Branch;
use App\Models\Dtr;
use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\Holiday;
use App\Models\HolidayType;
use App\Models\Leave;
use App\Models\PayrollPeriod;
use App\Services\AutomaticAbsenceService;
use App\Services\Imports\DtrImportService;
use App\Services\Imports\EmployeeVisibleDtrImportService;
use App\Services\LeaveDtrService;
use App\Services\PayrollCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AutomaticAbsenceImportTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Employee $employee;

    private PayrollPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-13 20:00:00', 'Asia/Manila'));
        $this->branch = Branch::query()->create(['branch_name' => 'Absence test', 'branch_address' => 'Test',
            'mobile_no' => '09123456789', 'employee_id' => 0, 'no_of_shifts' => 1,
            'reg_sched_start' => '08:00:00', 'reg_sched_end' => '18:00:00', 'is_24hrs' => false, 'has_broken_time' => false]);
        $this->employee = Employee::query()->create(['uid' => '0014', 'firstname' => 'Regular', 'lastname' => 'Employee',
            'branch_id' => $this->branch->id, 'fingerprint_id' => '123456', 'hired_date' => '2026-09-01',
            'employment_type' => 'Permanent', 'rate_type' => 'monthly', 'monthly_rate' => 20000, 'daily_rate' => 769.23]);
        $this->period = PayrollPeriod::query()->create(['title' => 'October test', 'date_start' => '2026-10-01',
            'date_end' => '2026-10-15', 'date_payout' => '2026-10-20', 'is_locked' => false]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function services(): array
    {
        return ['official' => [DtrImportService::class], 'sic_rc' => [EmployeeVisibleDtrImportService::class]];
    }

    #[DataProvider('services')]
    public function test_whole_absence_is_not_forgot_to_punch_and_duplicate_import_is_idempotent(string $service): void
    {
        $result = app($service)->importRows([$this->absence('whole_day')], 'Automatic absence');
        $this->assertSame(1, $result['successful'], json_encode($result));
        foreach ([Dtr::class, EmployeeVisibleDtr::class] as $model) {
            $row = $model::query()->sole();
            $this->assertTrue($row->is_absent);
            $this->assertSame('Absent', $row->schedule_type);
            $this->assertNull($row->time_in);
            $this->assertNull($row->time_out);
            $this->assertSame(480, $row->absence_minutes);
            $this->assertEquals(0, $row->work_hrs);
        }
        $again = app($service)->importRows([$this->absence('whole_day')], 'Reimport');
        $this->assertSame(0, $again['successful']);
        $this->assertSame(1, $again['skipped']);
        $this->assertSame(1, Dtr::query()->count());
    }

    #[DataProvider('services')]
    public function test_half_day_attendance_stays_one_row_and_legacy_candidates_are_skipped(string $service): void
    {
        foreach (['morning', 'afternoon'] as $part) {
            $date = $part === 'morning' ? '2026-10-12' : '2026-10-13';
            $actual = $part === 'morning' ? $this->punch('13:00:00', '18:00:00', $date) : $this->punch('08:00:00', '12:30:00', $date);
            $result = app($service)->importRows([$this->absence($part, $date), $actual], 'Half absence');
            $this->assertSame(1, $result['successful'], json_encode($result));
            $this->assertSame(1, $result['skipped']);
            $this->assertSame(0, Dtr::query()->whereDate('date_in', $date)->where('is_absent', true)->count());
            $this->assertSame(1, EmployeeVisibleDtr::query()->whereDate('date_in', $date)->count());
            $this->assertSame(1, Dtr::query()->whereDate('date_in', $date)->where('is_absent', false)->count());
        }
    }

    #[DataProvider('services')]
    public function test_existing_absence_is_superseded_by_later_real_attendance_without_deleting_history(string $service): void
    {
        app($service)->importRows([$this->absence('whole_day')], 'Absent');
        $result = app($service)->importRows([$this->punch('08:00:00', '12:30:00')], 'Correct morning');
        $this->assertSame(1, $result['successful'], json_encode($result));
        $absent = Dtr::query()->where('is_absent', true)->first();
        $this->assertNull($absent);
        $this->assertSame(1, Dtr::query()->count());
        $result = app($service)->importRows([$this->punch('13:00:00', '18:00:00')], 'Correct afternoon');
        $this->assertSame(1, $result['successful'], json_encode($result));
        $this->assertSame(0, Dtr::query()->where('is_absent', true)->count());
        $this->assertSame(1, Dtr::onlyTrashed()->where('entry_source', AutomaticAbsenceService::SOURCE)->count());
        $this->assertSame(0, EmployeeVisibleDtr::query()->where('is_absent', true)->count());
        $this->assertDatabaseHas('action_histories', ['action' => 'automatic_absence_superseded']);
    }

    #[DataProvider('services')]
    public function test_actual_whole_day_and_incomplete_punch_block_absence(string $service): void
    {
        $result = app($service)->importRows([$this->absence('whole_day'), $this->punch('08:00:00', '18:00:00')], 'Present');
        $this->assertSame(1, $result['successful'], json_encode($result));
        $this->assertSame(1, $result['skipped']);
        $incomplete = $this->punch('08:00:00', '', '2026-10-13');
        $incomplete['date_out'] = '';
        $result = app($service)->importRows([$this->absence('whole_day', '2026-10-13'), $incomplete], 'Incomplete');
        $this->assertSame(1, $result['successful'], json_encode($result));
        $this->assertSame('Forgot to Punch', Dtr::query()->whereDate('date_in', '2026-10-13')->sole()->schedule_type);
        $this->assertSame(0, Dtr::query()->where('is_absent', true)->count());
    }

    #[DataProvider('services')]
    public function test_leave_holiday_rest_days_employment_and_in_progress_days_are_excluded(string $service): void
    {
        Leave::query()->create(['employee_id' => $this->employee->id, 'leave_type' => 'Vacation Leave',
            'leave_from' => '2026-10-12', 'leave_to' => '2026-10-12', 'status' => 'Approved', 'reason' => 'Approved test leave']);
        $result = app($service)->importRows([$this->absence('whole_day')], 'Leave');
        $this->assertSame(1, $result['skipped'], json_encode($result));
        $this->assertNotEmpty($result['notices']);
        $result = app($service)->importRows([$this->absence('whole_day', '2026-10-11')], 'Rest');
        $this->assertSame(1, $result['skipped'], json_encode($result));
        $result = app($service)->importRows([$this->absence('whole_day', '2026-10-14')], 'Future');
        $this->assertSame(1, $result['skipped'], json_encode($result));
        Carbon::setTestNow(Carbon::parse('2026-10-13 17:59:00', 'Asia/Manila'));
        $result = app($service)->importRows([$this->absence('whole_day', '2026-10-13')], 'Not finished');
        $this->assertSame(1, $result['skipped'], json_encode($result));
        Carbon::setTestNow(Carbon::parse('2026-10-13 20:00:00', 'Asia/Manila'));
        $this->employee->update(['hired_date' => '2026-10-14']);
        $result = app($service)->importRows([$this->absence('whole_day', '2026-10-13')], 'Not employed');
        $this->assertSame(1, $result['skipped'], json_encode($result));
        $this->assertSame(0, Dtr::query()->count());
    }

    #[DataProvider('services')]
    public function test_invalid_candidate_markers_and_locked_periods_are_rejected(string $service): void
    {
        $bad = $this->absence('whole_day');
        unset($bad['absence_candidate']);
        $result = app($service)->importRows([$bad], 'Unmarked');
        $this->assertSame(1, $result['failed']);
        $bad = $this->absence('whole_day');
        $bad['schedule_end'] = '07:00:00';
        $result = app($service)->importRows([$bad], 'Invalid workday');
        $this->assertSame(1, $result['failed']);
        $this->period->update(['is_locked' => true]);
        $result = app($service)->importRows([$this->absence('whole_day')], 'Locked');
        $this->assertSame(1, $result['failed']);
        $this->assertSame(0, Dtr::query()->count());
    }

    #[DataProvider('services')]
    public function test_holidays_pending_requests_and_non_regular_profiles_are_not_automatically_absent(string $service): void
    {
        $type = HolidayType::query()->create(['title' => 'Regular Holiday', 'type' => 'regular', 'rate' => 2]);
        Holiday::query()->create(['branch_id' => $this->branch->id, 'holiday_type_id' => $type->id,
            'date' => '2026-10-12', 'title' => 'Test holiday', 'is_recurring' => false]);
        $result = app($service)->importRows([$this->absence('whole_day')], 'Holiday');
        $this->assertSame(1, $result['skipped'], json_encode($result));
        $request = DtrChangeRequest::query()->create(['employee_id' => $this->employee->id, 'branch_id' => $this->branch->id,
            'payroll_period_id' => $this->period->id, 'dtr_date' => '2026-10-13',
            'employee_name_snapshot' => 'Regular Employee', 'branch_name_snapshot' => 'Absence test',
            'payroll_period_title_snapshot' => 'October test',
            'date_from' => '2026-10-13', 'date_to' => '2026-10-13',
            'request_type' => 'forgot_to_punch', 'status' => 'Pending', 'description' => 'Review missing attendance']);
        $result = app($service)->importRows([$this->absence('whole_day', '2026-10-13')], 'Pending correction');
        $this->assertSame(1, $result['skipped'], json_encode($result));
        $request->update(['status' => 'Rejected']);
        $this->employee->update(['rate_type' => 'daily']);
        $result = app($service)->importRows([$this->absence('whole_day', '2026-10-13')], 'Other schedule');
        $this->assertSame(1, $result['skipped'], json_encode($result));
        $this->assertSame(0, Dtr::query()->count());
    }

    #[DataProvider('services')]
    public function test_explicit_half_absence_does_not_double_existing_payroll_half_day_deduction(string $service): void
    {
        app($service)->importRows([$this->punch('08:00:00', '12:30:00')], 'Morning');
        $before = app(PayrollCalculator::class)->row($this->employee, $this->period);
        $result = app($service)->importRows([$this->absence('afternoon')], 'Obsolete afternoon absence');
        $this->assertSame(1, $result['skipped'], json_encode($result));
        $this->assertSame(1, Dtr::query()->count());
        $after = app(PayrollCalculator::class)->row($this->employee, $this->period);
        foreach (['halfday', 'absent', 'days_worked', 'late', 'undertime_minutes', 'overtime_minutes', 'gross_pay', 'net_pay'] as $key) {
            $this->assertEquals($before[$key], $after[$key], $key);
        }
        $this->assertGreaterThan(0, $after['halfday']);
    }

    #[DataProvider('services')]
    public function test_whole_absence_uses_existing_zero_hours_and_payroll_absence_calculation(string $service): void
    {
        $before = app(PayrollCalculator::class)->row($this->employee, $this->period);
        app($service)->importRows([$this->absence('whole_day')], 'Whole absence');
        $after = app(PayrollCalculator::class)->row($this->employee, $this->period);
        $this->assertEquals($before['days_worked'] - 1, $after['days_worked']);
        $this->assertEqualsWithDelta($after['rate_per_day'], $after['absent'], .01);
        $this->assertEquals(0, $after['halfday']);
        $this->assertEquals($before['gross_pay'], $after['gross_pay']);
        $this->assertEqualsWithDelta($before['net_pay'] - $after['absent'], $after['net_pay'], .01);
    }

    public function test_half_leave_overlays_the_original_whole_absence_without_creating_half_rows(): void
    {
        app(DtrImportService::class)->importRows([$this->absence('whole_day')], 'Before leave');
        $leave = Leave::query()->create(['employee_id' => $this->employee->id, 'leave_type' => 'Vacation Leave',
            'leave_from' => '2026-10-12', 'leave_to' => '2026-10-12', 'status' => 'Approved', 'reason' => 'Approved',
            'is_half_day' => true, 'half_day_period' => 'morning']);
        $service = new class extends LeaveDtrService
        {
            public function generateApprovedRows(Leave $leave, PayrollPeriod $period): void
            {
                DB::transaction(function () use ($leave, $period): void {
                    $this->ensureNoExistingDtrForLeaveDates($leave, $period);
                    $this->createPaidLeaveDtrRows($leave, $period);
                });
            }
        };
        $service->generateApprovedRows($leave, $this->period);
        $this->assertSame('whole_day', Dtr::query()->where('is_absent', true)->sole()->day_part);
        $this->assertSame(480, Dtr::query()->where('is_absent', true)->sole()->absence_minutes);
        $this->assertSame(1, Dtr::query()->where('leave_id', $leave->id)->count());
        $this->assertSame('whole_day', EmployeeVisibleDtr::query()->where('is_absent', true)->sole()->day_part);
    }

    #[DataProvider('services')]
    public function test_manual_absence_and_invalid_time_out_only_rows_remain_protected(string $service): void
    {
        app($service)->importRows([$this->absence('whole_day')], 'Auto');
        Dtr::query()->sole()->update(['entry_source' => 'absence']);
        EmployeeVisibleDtr::query()->sole()->update(['entry_source' => 'absence']);
        $result = app($service)->importRows([$this->punch('08:00:00', '18:00:00')], 'Do not override manual absence');
        $this->assertSame(0, $result['successful'], json_encode($result));
        $this->assertSame(1, Dtr::query()->where('is_absent', true)->count());
        $result = app($service)->importRows([$this->punch('', '18:00:00', '2026-10-13')], 'Missing in');
        $this->assertSame(1, $result['failed'], json_encode($result));
        $this->assertSame(1, Dtr::query()->count());
    }

    #[DataProvider('services')]
    public function test_saturday_absence_is_one_three_hour_record_only_after_eleven(string $service): void
    {
        $this->employee->update(['monthly_rate' => 20800]);
        $row = [...$this->absence('whole_day', '2026-10-10'),
            'schedule_start' => '08:00:00', 'schedule_end' => '11:00:00'];
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:00:00', 'Asia/Manila'));
        $this->assertSame(1, app($service)->importRows([$row], 'Still at cutoff')['skipped']);
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));
        $result = app($service)->importRows([$row], 'Saturday complete');
        $this->assertSame(1, $result['successful'], json_encode($result));
        foreach ([Dtr::class, EmployeeVisibleDtr::class] as $model) {
            $saved = $model::query()->sole();
            $this->assertSame('whole_day', $saved->day_part);
            $this->assertSame(180, $saved->absence_minutes);
            $this->assertNull($saved->time_in);
            $this->assertNull($saved->time_out);
        }
        $pay = app(PayrollCalculator::class)->row($this->employee->fresh(), $this->period);
        $this->assertEquals(300, $pay['absent']); // existing 3-hour Saturday payroll
        $this->assertEquals(0, $pay['halfday']);
        $page = new DtrManage;
        $page->employeeId = $this->employee->id;
        $page->branchId = $this->branch->id;
        $page->periodId = $this->period->id;
        $summary = $page->getSummaryMetrics();
        $this->assertSame(0.0, $summary['absent_days']);
        $this->assertSame(3, $summary['saturday_absent_hours']);
        $this->assertSame(0.0, $summary['total_days_work']);
        $this->assertSame(1, app($service)->importRows([$row], 'Repeated')['skipped']);
    }

    public function test_legacy_saturday_absence_with_missing_minutes_deducts_only_three_hours(): void
    {
        $this->employee->update(['monthly_rate' => 20800]);
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));
        $row = [...$this->absence('whole_day', '2026-10-10'),
            'schedule_start' => '08:00:00', 'schedule_end' => '11:00:00'];
        $this->assertSame(1, app(DtrImportService::class)->importRows([$row], 'Saturday')['successful']);
        Dtr::query()->update(['absence_minutes' => 0]);

        $pay = app(PayrollCalculator::class)->row($this->employee->fresh(), $this->period);
        $this->assertEquals(300, $pay['absent']);
        $this->assertEquals(0, $pay['halfday']);

        Dtr::query()->update(['absence_minutes' => 480]);
        $pay = app(PayrollCalculator::class)->row($this->employee->fresh(), $this->period);
        $this->assertEquals(300, $pay['absent']);
    }

    #[DataProvider('services')]
    public function test_saturday_invalid_window_and_actual_duty_block_absence(string $service): void
    {
        $bad = $this->absence('whole_day', '2026-10-10');
        $this->assertSame(1, app($service)->importRows([$bad], 'Invalid Saturday window')['skipped']);
        $candidate = [...$bad, 'schedule_end' => '11:00:00'];
        $actual = [...$this->punch('08:00:00', '11:00:00', '2026-10-10'),
            'schedule_type' => 'Saturday', 'schedule_end' => '11:00:00'];
        $result = app($service)->importRows([$candidate, $actual], 'Saturday duty');
        $this->assertSame(1, $result['successful'], json_encode($result));
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(0, Dtr::where('is_absent', true)->count());
    }

    #[DataProvider('services')]
    public function test_half_day_only_needs_one_actual_record_for_payroll(string $service): void
    {
        $this->employee->update(['monthly_rate' => 20800]);
        $actual = $this->punch('12:31:00', '18:00:00');
        $result = app($service)->importRows([$actual], 'Afternoon only');
        $this->assertSame(1, $result['successful'], json_encode($result));
        $this->assertSame('afternoon', Dtr::query()->sole()->day_part);
        $pay = app(PayrollCalculator::class)->row($this->employee->fresh(), $this->period);
        $this->assertEquals(400, $pay['halfday']);
        $this->assertEquals(0, $pay['absent']);
        $this->assertEquals(0, $pay['late']);
        $this->assertEquals(0, $pay['undertime_minutes']);
        $this->assertSame(1, Dtr::count());
        $this->assertSame(1, EmployeeVisibleDtr::count());
        $this->assertSame(1, app($service)->importRows([$actual], 'Duplicate duty')['skipped']);
        $this->assertSame(1, app($service)->importRows([$this->absence('whole_day')], 'Stale whole absence')['skipped']);
    }

    #[DataProvider('services')]
    public function test_legacy_half_candidates_without_attendance_are_skipped_not_promoted_to_whole_absence(string $service): void
    {
        $result = app($service)->importRows([$this->absence('morning'), $this->absence('afternoon')], 'Legacy export');
        $this->assertSame(2, $result['skipped'], json_encode($result));
        $this->assertSame(0, Dtr::count());
        $this->assertSame(0, EmployeeVisibleDtr::count());
    }

    #[DataProvider('services')]
    public function test_historical_extra_absence_is_not_double_deducted_and_not_destroyed_by_calculation(string $service): void
    {
        $this->employee->update(['monthly_rate' => 20800]);
        app($service)->importRows([$this->punch('08:00:00', '12:30:00')], 'Morning');
        $legacy = Dtr::create(['payroll_period_id' => $this->period->id, 'branch_id' => $this->branch->id,
            'fingerprint_id' => '123456', 'date_in' => '2026-10-12', 'date_out' => '2026-10-12',
            'entry_source' => 'auto_absence', 'schedule_type' => 'Absent', 'is_absent' => true,
            'day_part' => 'whole_day', 'absence_minutes' => 480, 'work_hrs' => 0]);
        $before = $legacy->fresh()->getAttributes();
        $pay = app(PayrollCalculator::class)->row($this->employee->fresh(), $this->period);
        $this->assertEquals(400, $pay['halfday']);
        $this->assertEquals(0, $pay['absent']);
        $this->assertSame($before, $legacy->fresh()->getAttributes());
    }

    private function absence(string $part, string $date = '2026-10-12'): array
    {
        return [...$this->punch('', '', $date), 'schedule_type' => 'Absent', 'entry_source' => 'auto_absence',
            'absence_candidate' => true, 'regular_schedule' => 'Regular Sched 1',
            'day_part' => $part, 'source_session_id' => "ABS-123456-{$date}-{$part}"];
    }

    private function punch(string $in, string $out, string $date = '2026-10-12'): array
    {
        return ['batch_id' => 'ABSENCE-TEST', 'payroll_period_id' => $this->period->id, 'branch_id' => $this->branch->id,
            'fingerprint_id' => '123456', 'date_in' => $date, 'date_out' => $date, 'time_in' => $in, 'time_out' => $out,
            'schedule_type' => 'Regular', 'schedule_start' => '08:00:00', 'schedule_end' => '18:00:00'];
    }
}
