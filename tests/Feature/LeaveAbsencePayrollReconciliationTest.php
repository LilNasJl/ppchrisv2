<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\Leave;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Imports\DtrImportService;
use App\Services\Imports\EmployeeVisibleDtrImportService;
use App\Services\LeaveApprovalService;
use App\Services\LeaveDtrService;
use App\Services\PayrollCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LeaveAbsencePayrollReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    private Branch $branch;

    private PayrollPeriod $period;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-13 20:00:00', 'Asia/Manila'));
        $this->branch = Branch::create(['branch_name' => 'Reconciliation test', 'branch_address' => 'Test',
            'mobile_no' => '09123456789', 'employee_id' => 0, 'no_of_shifts' => 1,
            'reg_sched_start' => '08:00:00', 'reg_sched_end' => '18:00:00', 'is_24hrs' => false, 'has_broken_time' => false]);
        $this->employee = Employee::create(['uid' => '0014', 'firstname' => 'Employee', 'lastname' => 'One',
            'branch_id' => $this->branch->id, 'fingerprint_id' => '123456', 'hired_date' => '2026-09-01',
            'employment_type' => 'Permanent', 'rate_type' => 'monthly', 'monthly_rate' => 20800,
            'leave_credits' => 10, 'leave_credits_year' => 2026]);
        $this->period = PayrollPeriod::create(['title' => 'October review', 'date_start' => '2026-10-01',
            'date_end' => '2026-10-15', 'date_payout' => '2026-10-20', 'is_locked' => false]);
        $this->hr = User::factory()->create(['role' => 'hr', 'is_disabled' => false]);
        Gate::define('Review:Leave', fn (User $user): bool => $user->id === $this->hr->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function orders(): array
    {
        $cases = [];
        foreach ([DtrImportService::class, EmployeeVisibleDtrImportService::class] as $service) {
            foreach ([false, true] as $leaveFirst) {
                foreach (['morning', 'afternoon'] as $part) {
                    foreach ([Leave::PAID, Leave::UNPAID] as $pay) {
                        $cases[] = [$service, $leaveFirst, $part, $pay];
                    }
                }
            }
        }

        return $cases;
    }

    #[DataProvider('orders')]
    public function test_both_processing_orders_preserve_the_other_half_and_calculate_pay(string $service, bool $leaveFirst, string $part, string $pay): void
    {
        $actual = $part === 'morning' ? $this->punch('13:00:00', '18:00:00') : $this->punch('08:00:00', '12:00:00');
        $before = app(PayrollCalculator::class)->row($this->employee, $this->period);
        if ($leaveFirst) {
            $leave = $this->approve($part, $pay);
        }
        $result = app($service)->importRows([$this->absence($part), $actual], 'Station DTR');
        $this->assertSame(1, $result['successful'], json_encode($result));
        $this->assertSame(1, $result['skipped']);
        $attendance = Dtr::where('schedule_type', 'Regular')->sole();
        $original = $attendance->getAttributes();
        $visibleAttendance = EmployeeVisibleDtr::where('schedule_type', 'Regular')->sole();
        $visibleOriginal = $visibleAttendance->getAttributes();
        if (! $leaveFirst) {
            $leave = $this->approve($part, $pay);
        }
        $this->assertSame($original, $attendance->fresh()->getAttributes());
        $this->assertSame($visibleOriginal, $visibleAttendance->fresh()->getAttributes());
        foreach ([Dtr::class, EmployeeVisibleDtr::class] as $model) {
            $this->assertSame(2, $model::count());
            $this->assertSame(0, $model::where('is_absent', true)->count());
            $this->assertSame($part, $model::where('leave_id', $leave->id)->sole()->day_part);
            $this->assertSame(0, $model::onlyTrashed()->where('is_absent', true)->count());
        }
        $after = app(PayrollCalculator::class)->row($this->employee->fresh(), $this->period);
        $this->assertEquals(0, $after['absent']);
        $this->assertEquals(0, $after['halfday']);
        $this->assertEquals(0, $after['late']);
        $this->assertEquals(0, $after['undertime_minutes']);
        $this->assertEquals($pay === Leave::PAID ? 0 : 400, $after['unpaid_leave']);
        $this->assertEqualsWithDelta($before['net_pay'] - ($pay === Leave::PAID ? 0 : 400), $after['net_pay'], .01);
        $this->assertEquals($pay === Leave::PAID ? 9.5 : 10, $this->employee->fresh()->leave_credits);
        $again = app($service)->importRows([$this->absence($part), $actual], 'Duplicate station DTR');
        $this->assertSame(0, $again['successful']);
        $this->assertSame(2, Dtr::count());
    }

    public static function wholeOrders(): array
    {
        return [
            [DtrImportService::class, false], [DtrImportService::class, true],
            [EmployeeVisibleDtrImportService::class, false], [EmployeeVisibleDtrImportService::class, true],
        ];
    }

    #[DataProvider('wholeOrders')]
    public function test_whole_day_candidate_keeps_the_uncovered_half_in_both_orders(string $service, bool $leaveFirst): void
    {
        if ($leaveFirst) {
            $this->approve('morning', Leave::PAID);
        }
        $result = app($service)->importRows([$this->absence('whole_day')], 'No punches');
        $this->assertSame(1, $result['successful'], json_encode($result));
        if (! $leaveFirst) {
            $this->approve('morning', Leave::PAID);
        }
        foreach ([Dtr::class, EmployeeVisibleDtr::class] as $model) {
            $this->assertSame('whole_day', $model::where('is_absent', true)->sole()->day_part);
        }
        $row = app(PayrollCalculator::class)->row($this->employee->fresh(), $this->period);
        $this->assertEquals(400, $row['halfday']);
        $this->assertEquals(0, $row['absent']);
    }

    public static function treatments(): array
    {
        return [[Leave::PAID, false], [Leave::UNPAID, false], [Leave::PAID, true], [Leave::UNPAID, true]];
    }

    #[DataProvider('treatments')]
    public function test_daily_rate_paid_leave_is_credited_and_unpaid_leave_is_not(string $pay, bool $whole): void
    {
        $this->employee->update(['rate_type' => 'daily', 'daily_rate' => 800]);
        if (! $whole) {
            app(DtrImportService::class)->importRows([$this->punch('13:00:00', '18:00:00')], 'Actual afternoon');
        }
        $this->approve($whole ? 'whole_day' : 'morning', $pay);
        $row = app(PayrollCalculator::class)->row($this->employee->fresh(), $this->period);
        $this->assertEquals($pay === Leave::PAID ? 800 : ($whole ? 0 : 400), $row['gross_pay']);
        $this->assertEquals(0, $row['halfday']);
        $this->assertEquals(0, $row['absent']);
        $this->assertEquals(0, $row['unpaid_leave']); // daily pay already excludes the unpaid units
    }

    public function test_pending_half_leave_does_not_hide_an_absence_in_the_other_half(): void
    {
        $this->pending('morning', Leave::PAID);
        $result = app(DtrImportService::class)->importRows([$this->punch('08:00:00', '12:00:00'), $this->absence('afternoon')], 'Pending morning only');
        $this->assertSame(1, $result['successful'], json_encode($result));
        $this->assertSame(0, Dtr::where('is_absent', true)->count());
        $this->assertEquals(400, app(PayrollCalculator::class)->row($this->employee, $this->period)['halfday']);
    }

    public function test_manual_absence_can_be_reconciled_with_final_leave_without_losing_audit(): void
    {
        app(DtrImportService::class)->importRows([$this->punch('13:00:00', '18:00:00')], 'Before HR');
        Dtr::create(['payroll_period_id' => $this->period->id, 'branch_id' => $this->branch->id,
            'fingerprint_id' => '123456', 'date_in' => '2026-10-13', 'date_out' => '2026-10-13',
            'schedule_type' => 'Absent', 'entry_source' => 'absence', 'day_part' => 'morning',
            'is_absent' => true, 'absence_minutes' => 240, 'work_hrs' => 0]);
        $this->approve('morning', Leave::PAID);
        $this->assertSame(2, Dtr::count());
        $this->assertSame(1, Dtr::onlyTrashed()->where('entry_source', 'absence')->count());
        $this->assertDatabaseHas('action_histories', ['action' => 'absence_reconciled_with_leave']);
    }

    public function test_actual_attendance_in_the_leave_half_blocks_approval_and_credit_deduction(): void
    {
        app(DtrImportService::class)->importRows([$this->punch('08:00:00', '12:00:00')], 'Actual morning');
        $leave = $this->pending('morning', Leave::PAID);
        try {
            app(LeaveApprovalService::class)->decideHr($leave, $this->hr, true, $this->period, 'Conflict test');
            $this->fail('Overlapping actual attendance was approved.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Conflicting', $exception->getMessage());
        }
        $this->assertSame('Pending', $leave->fresh()->status);
        $this->assertEquals(10, $this->employee->fresh()->leave_credits);
        $this->assertSame(1, Dtr::count());
    }

    public function test_locked_period_rejects_approval_and_preserves_the_snapshot(): void
    {
        $snapshot = ['employee_id' => $this->employee->id, 'branch_id' => $this->branch->id, 'net_pay' => 1234.56];
        DB::table('payroll_snapshots')->insert(['payroll_period_id' => $this->period->id, 'employee_id' => $this->employee->id,
            'branch_id' => $this->branch->id, 'row_number' => 1, 'data' => json_encode($snapshot), 'created_at' => now(), 'updated_at' => now()]);
        $this->period->update(['is_locked' => true]);
        $leave = $this->pending('morning', Leave::PAID);
        try {
            app(LeaveApprovalService::class)->decideHr($leave, $this->hr, true, $this->period, 'Locked');
            $this->fail('Locked payroll was changed.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('locked', $exception->getMessage());
        }
        $this->assertEquals(1234.56, app(PayrollCalculator::class)->employeeRow($this->employee, $this->period)['net_pay']);
        $this->assertSame('Pending', $leave->fresh()->status);
        $this->assertSame(0, Dtr::count());
    }

    public function test_unpaid_leave_does_not_require_leave_credits_and_paid_leave_does(): void
    {
        $this->employee->update(['leave_credits' => 0]);
        Leave::validateCanCreateRequest($this->employee, 'Vacation Leave', '2026-10-13', '2026-10-13', true, Leave::UNPAID);
        $leave = $this->approve('morning', Leave::UNPAID);
        $this->assertSame('Approved', $leave->status);
        $this->assertEquals(0, $this->employee->fresh()->leave_credits);
        $this->expectExceptionMessage('enough leave credits');
        Leave::validateCanCreateRequest($this->employee, 'Vacation Leave', '2026-10-12', '2026-10-12', true, Leave::PAID);
    }

    public function test_leave_range_conflict_check_uses_two_queries_instead_of_one_per_date(): void
    {
        $leave = $this->pending('whole_day', Leave::PAID);
        $leave->leave_from = '2026-10-01';
        $leave->leave_to = '2026-10-13';
        $leave->setRelation('employee', $this->employee);
        $method = new \ReflectionMethod(LeaveDtrService::class, 'ensureNoExistingDtrForLeaveDates');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $method->invoke(app(LeaveDtrService::class), $leave, $this->period);
        $selects = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_starts_with($query['query'], 'select'));
        DB::disableQueryLog();
        $this->assertCount(2, $selects);
        $this->assertTrue(Schema::hasIndex('leaves', 'leaves_employee_status_dates_idx'));
        $this->assertTrue(Schema::hasIndex('dtrs', 'dtrs_scope_date_reconcile_idx'));
    }

    #[DataProvider('treatments')]
    public function test_monthly_whole_day_leave_has_exactly_one_pay_treatment(string $pay, bool $leaveFirst): void
    {
        $before = app(PayrollCalculator::class)->row($this->employee, $this->period);
        if ($leaveFirst) {
            $this->approve('whole_day', $pay);
        }
        app(EmployeeVisibleDtrImportService::class)->importRows([$this->absence('whole_day')], 'Whole absent day');
        if (! $leaveFirst) {
            $this->approve('whole_day', $pay);
        }
        $this->assertSame(1, Dtr::count());
        $this->assertSame(0, Dtr::where('is_absent', true)->count());
        $row = app(PayrollCalculator::class)->row($this->employee->fresh(), $this->period);
        $this->assertEquals(0, $row['absent']);
        $this->assertEquals(0, $row['halfday']);
        $this->assertEquals($pay === Leave::PAID ? 0 : 800, $row['unpaid_leave']);
        $this->assertEqualsWithDelta($before['net_pay'] - ($pay === Leave::PAID ? 0 : 800), $row['net_pay'], .01);
    }

    public static function inactiveStatuses(): array
    {
        return [['Rejected'], ['Cancelled']];
    }

    #[DataProvider('inactiveStatuses')]
    public function test_rejected_or_cancelled_leave_does_not_cover_absence_or_change_pay(string $status): void
    {
        $requester = User::factory()->create(['role' => 'employee', 'is_disabled' => false]);
        $this->employee->update(['user_id' => $requester->id]);
        $leave = $this->pending('morning', Leave::PAID);
        if ($status === 'Rejected') {
            app(LeaveApprovalService::class)->decideHr($leave, $this->hr, false, null, 'Rejected');
        } else {
            app(LeaveApprovalService::class)->cancel($leave, $requester);
        }
        $result = app(EmployeeVisibleDtrImportService::class)->importRows([
            $this->punch('13:00:00', '18:00:00'), $this->absence('morning'),
        ], 'After decision');
        $this->assertSame(1, $result['successful'], json_encode($result));
        $row = app(PayrollCalculator::class)->row($this->employee->fresh(), $this->period);
        $this->assertEquals(400, $row['halfday']);
        $this->assertEquals(0, $row['paid_leave_days']);
        $this->assertEquals(0, $row['unpaid_leave']);
        $this->assertEquals(10, $this->employee->fresh()->leave_credits);
    }

    public function test_two_half_day_leaves_do_not_double_pay_or_absence(): void
    {
        app(DtrImportService::class)->importRows([$this->absence('whole_day')], 'Original absence');
        $this->approve('morning', Leave::PAID);
        $this->approve('afternoon', Leave::UNPAID);
        $this->assertSame(3, Dtr::count()); // source absence retained as audit data
        $this->assertSame('whole_day', Dtr::where('is_absent', true)->sole()->day_part);
        $row = app(PayrollCalculator::class)->row($this->employee->fresh(), $this->period);
        $this->assertEquals(.5, $row['paid_leave_days']);
        $this->assertEquals(.5, $row['unpaid_leave_days']);
        $this->assertEquals(400, $row['unpaid_leave']);
        $this->assertEquals(0, $row['halfday']);
    }

    public function test_approved_leave_cannot_be_silently_repriced_or_deleted(): void
    {
        $leave = $this->approve('morning', Leave::PAID);
        foreach (['reprice', 'delete'] as $action) {
            try {
                if ($action === 'reprice') {
                    $leave->update(['pay_treatment' => Leave::UNPAID]);
                } else {
                    $leave->fresh()->delete();
                }
                $this->fail('Approved leave was altered.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('controlled HR correction', $exception->getMessage());
            }
        }
        $this->assertSame(Leave::PAID, $leave->fresh()->pay_treatment);
    }

    public function test_half_day_leave_uses_the_configured_regular_schedule(): void
    {
        $this->branch->update(['reg_sched_start' => '09:00:00', 'reg_sched_end' => '17:00:00']);
        $leave = $this->approve('morning', Leave::PAID);
        $row = Dtr::where('leave_id', $leave->id)->sole();
        $this->assertSame('09:00:00', $row->schedule_start);
        $this->assertSame('12:00:00', $row->schedule_end);
    }

    private function approve(string $part, string $pay): Leave
    {
        $leave = $this->pending($part, $pay);
        app(LeaveApprovalService::class)->decideHr($leave, $this->hr, true, $this->period, 'HR approved', $pay);

        return $leave->fresh();
    }

    private function pending(string $part, string $pay): Leave
    {
        return Leave::create(['employee_id' => $this->employee->id, 'leave_type' => 'Vacation Leave',
            'leave_from' => '2026-10-13', 'leave_to' => '2026-10-13', 'reason' => 'Test leave',
            'status' => 'Pending', 'is_half_day' => $part !== 'whole_day',
            'half_day_period' => $part === 'whole_day' ? null : $part, 'pay_treatment' => $pay]);
    }

    private function punch(string $in, string $out): array
    {
        return ['batch_id' => 'LEAVE-TEST', 'payroll_period_id' => $this->period->id, 'branch_id' => $this->branch->id,
            'fingerprint_id' => '123456', 'date_in' => '2026-10-13', 'date_out' => '2026-10-13',
            'time_in' => $in, 'time_out' => $out, 'schedule_type' => 'Regular', 'schedule_start' => '08:00:00', 'schedule_end' => '18:00:00'];
    }

    private function absence(string $part): array
    {
        return [...$this->punch('', ''), 'schedule_type' => 'Absent', 'entry_source' => 'auto_absence',
            'absence_candidate' => true, 'regular_schedule' => 'Regular Sched 1', 'absence_split' => '12:30:00',
            'day_part' => $part, 'source_session_id' => 'ABS-123456-20261013-'.$part];
    }
}
