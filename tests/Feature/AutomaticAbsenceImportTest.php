<?php

namespace Tests\Feature;

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
    public function test_missing_morning_and_afternoon_are_half_absences_with_actual_punches_processed_first(string $service): void
    {
        foreach (['morning', 'afternoon'] as $part) {
            $date = $part === 'morning' ? '2026-10-12' : '2026-10-13';
            $actual = $part === 'morning' ? $this->punch('13:00:00', '18:00:00', $date) : $this->punch('08:00:00', '12:30:00', $date);
            $result = app($service)->importRows([$this->absence($part, $date), $actual], 'Half absence');
            $this->assertSame(2, $result['successful'], json_encode($result));
            $absent = Dtr::query()->whereDate('date_in', $date)->where('is_absent', true)->sole();
            $this->assertSame($part, $absent->day_part);
            $this->assertSame(240, $absent->absence_minutes);
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
        $this->assertNotNull($absent, json_encode(Dtr::withTrashed()->get()->toArray()));
        $this->assertSame('afternoon', $absent->day_part);
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
        $bad['absence_split'] = '19:00:00';
        $result = app($service)->importRows([$bad], 'Bad split');
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
        $result = app($service)->importRows([$this->absence('afternoon')], 'Explicit afternoon absence');
        $this->assertSame(1, $result['successful'], json_encode($result));
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

    public function test_approved_leave_supersedes_only_automatic_absence_and_keeps_the_other_half(): void
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
        $this->assertSame('afternoon', Dtr::query()->where('is_absent', true)->sole()->day_part);
        $this->assertSame(240, Dtr::query()->where('is_absent', true)->sole()->absence_minutes);
        $this->assertSame(1, Dtr::query()->where('leave_id', $leave->id)->count());
        $this->assertSame('afternoon', EmployeeVisibleDtr::query()->where('is_absent', true)->sole()->day_part);
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

    private function absence(string $part, string $date = '2026-10-12'): array
    {
        return [...$this->punch('', '', $date), 'schedule_type' => 'Absent', 'entry_source' => 'auto_absence',
            'absence_candidate' => true, 'regular_schedule' => 'Regular Sched 1', 'absence_split' => '12:30:00',
            'day_part' => $part, 'source_session_id' => "ABS-123456-{$date}-{$part}"];
    }

    private function punch(string $in, string $out, string $date = '2026-10-12'): array
    {
        return ['batch_id' => 'ABSENCE-TEST', 'payroll_period_id' => $this->period->id, 'branch_id' => $this->branch->id,
            'fingerprint_id' => '123456', 'date_in' => $date, 'date_out' => $date, 'time_in' => $in, 'time_out' => $out,
            'schedule_type' => 'Regular', 'schedule_start' => '08:00:00', 'schedule_end' => '18:00:00'];
    }
}
