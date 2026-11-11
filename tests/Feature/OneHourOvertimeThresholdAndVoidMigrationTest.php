<?php

namespace Tests\Feature;

use App\Filament\Employee\Pages\Dtr as EmployeeDtrPage;
use App\Filament\Employee\Pages\DtrChangeRequests as EmployeeDtrChangeRequestsPage;
use App\Filament\Employee\Pages\Station\ReviewDtrChangeRequests;
use App\Filament\Employee\Pages\Station\StationChangeRequestHistory;
use App\Filament\Pages\DtrChangeRequests as HrDtrChangeRequestsPage;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Dtr;
use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\LeaveApprovalLevel;
use App\Models\LeaveApprovalWorkflow;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\DtrChangeRequestService;
use App\Services\DtrDailyAggregationService;
use App\Services\OvertimeApprovalService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OneHourOvertimeThresholdAndVoidMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{
     *     branch: Branch,
     *     openPeriod: PayrollPeriod,
     *     lockedPeriod: PayrollPeriod,
     *     sicUser: User,
     *     sic: Employee,
     *     empUser: User,
     *     employee: Employee,
     *     hrUser: User,
     * }
     */
    private function seedContext(): array
    {
        $department = Department::query()->create([
            'name' => 'Operations',
            'description' => 'Station Operations',
        ]);

        $designation = Designation::query()->create([
            'title' => 'Station Crew',
            'description' => 'Station Crew',
            'rate_per_hour' => 65,
        ]);

        $branch = Branch::query()->create([
            'branch_name' => 'Tagum Main Station',
            'branch_address' => 'Tagum City',
            'mobile_no' => '09171234567',
            'employee_id' => 0,
            'no_of_shifts' => 1,
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '17:00:00',
            'is_24hrs' => false,
            'has_broken_time' => false,
        ]);

        $openPeriod = PayrollPeriod::query()->create([
            'title' => 'Current Open Period',
            'date_start' => now()->subDays(10)->toDateString(),
            'date_end' => now()->addDays(5)->toDateString(),
            'date_payout' => now()->addDays(10)->toDateString(),
            'description' => 'Open period',
            'is_locked' => false,
        ]);

        $lockedPeriod = PayrollPeriod::query()->create([
            'title' => 'Past Locked Period',
            'date_start' => now()->subDays(40)->toDateString(),
            'date_end' => now()->subDays(25)->toDateString(),
            'date_payout' => now()->subDays(20)->toDateString(),
            'description' => 'Locked period',
            'is_locked' => false,
        ]);

        $sicUser = User::factory()->create([
            'name' => 'Marco Santos',
            'role' => 'employee',
            'email' => 'marco.sic@example.com',
        ]);

        $sic = Employee::query()->create([
            'user_id' => $sicUser->id,
            'uid' => 'SIC-101',
            'company_id' => 'PF-0101',
            'fingerprint_id' => '9101',
            'lastname' => 'Santos',
            'firstname' => 'Marco',
            'middlename' => 'D',
            'gender' => 'Male',
            'civil_status' => 'Single',
            'birthdate' => '1990-01-15',
            'home_address' => 'Tagum City',
            'contact_no' => '09171111111',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'is_station_manager' => true,
            'managed_branches' => [$branch->id],
            'employment_status' => 'Regular',
            'employment_type' => 'Permanent',
            'date_hired' => '2022-01-10',
            'rate_type' => 'monthly',
            'monthly_rate' => 18000,
            'daily_rate' => 692.31,
            'schedule_type' => 'regular',
        ]);
        $sicUser->setRelation('employee', $sic);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => 'Tagum OT Workflow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'SIC TAGUM',
            'approver_employee_id' => $sic->id,
        ]);

        $empUser = User::factory()->create([
            'name' => 'Juan Dela Cruz',
            'role' => 'employee',
            'email' => 'juan.emp@example.com',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $empUser->id,
            'uid' => 'EMP-201',
            'company_id' => 'PF-0201',
            'fingerprint_id' => '2001',
            'lastname' => 'Dela Cruz',
            'firstname' => 'Juan',
            'middlename' => 'R',
            'gender' => 'Male',
            'civil_status' => 'Single',
            'birthdate' => '1995-06-15',
            'home_address' => 'Tagum City',
            'contact_no' => '09172222222',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employment_status' => 'Regular',
            'employment_type' => 'Permanent',
            'date_hired' => '2023-03-01',
            'rate_type' => 'daily',
            'daily_rate' => 500,
            'schedule_type' => 'regular',
        ]);
        $empUser->setRelation('employee', $employee);

        $hrUser = User::factory()->create([
            'name' => 'HR Reviewer',
            'role' => 'hr',
            'email' => 'hr.reviewer@example.com',
        ]);

        return compact('branch', 'openPeriod', 'lockedPeriod', 'sicUser', 'sic', 'empUser', 'employee', 'hrUser');
    }

    public function test_change_request_service_enforces_sixty_minute_minimum_across_early_and_after_overtime_boundaries(): void
    {
        $ctx = $this->seedContext();
        $service = app(DtrChangeRequestService::class);
        $this->assertSame(60, OvertimeApprovalService::MINIMUM_MINUTES);

        // 1. 59m early + 59m OT: completely blocked from OT request submission
        $bothBelowDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $ctx['employee']->id,
            'fingerprint_id' => '2001',
            'branch_id' => $ctx['branch']->id,
            'payroll_period_id' => $ctx['openPeriod']->id,
            'date_in' => now()->subDays(6)->toDateString(),
            'time_in' => '07:01:00',
            'date_out' => now()->subDays(6)->toDateString(),
            'time_out' => '17:59:00',
            'schedule_start' => '08:00:00',
            'schedule_end' => '17:00:00',
            'schedule_type' => 'Regular',
            'early_clock_in' => 59,
            'overtime' => 59,
            'overtime_status' => 'n/a',
        ]);

        try {
            $service->submitFromDtr($ctx['employee'], $bothBelowDtr, [
                'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
                'explanation' => 'Below 60m on both sides.',
            ]);
            $this->fail('59m early and 59m OT must be blocked by the 60-minute threshold.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('60 minutes', collect($e->errors())->flatten()->first());
        }

        // 2. 60m early + 59m OT: only early_only is eligible; both & overtime_only are rejected
        $earlyPassDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $ctx['employee']->id,
            'fingerprint_id' => '2001',
            'branch_id' => $ctx['branch']->id,
            'payroll_period_id' => $ctx['openPeriod']->id,
            'date_in' => now()->subDays(5)->toDateString(),
            'time_in' => '07:00:00',
            'date_out' => now()->subDays(5)->toDateString(),
            'time_out' => '17:59:00',
            'schedule_start' => '08:00:00',
            'schedule_end' => '17:00:00',
            'schedule_type' => 'Regular',
            'early_clock_in' => 60,
            'overtime' => 59,
            'overtime_status' => 'Pending',
        ]);

        foreach ([DtrChangeRequest::OT_SCOPE_BOTH, DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY] as $invalidScope) {
            try {
                $service->submitFromDtr($ctx['employee'], $earlyPassDtr, [
                    'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
                    'overtime_request_scope' => $invalidScope,
                    'explanation' => 'Should fail when regular OT is 59m.',
                ]);
                $this->fail("Scope {$invalidScope} must fail when after-shift OT is 59m.");
            } catch (ValidationException $e) {
                $this->assertStringContainsString('60', collect($e->errors())->flatten()->first());
            }
        }

        $earlyRequest = $service->submitFromDtr($ctx['employee'], $earlyPassDtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_EARLY_ONLY,
            'explanation' => 'Exact 60m early clock in.',
        ]);

        // Edit rejects 59m and 61m, accepts 60m
        try {
            $service->updateOvertimeRequest($earlyRequest, $ctx['sic'], [
                'credited_early_minutes' => 59,
            ]);
            $this->fail('Editing early OT to 59m must fail.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('at least 60 minutes', collect($e->errors())->flatten()->first());
        }

        try {
            $service->approve($earlyRequest, $ctx['sic'], 'Over-credit attempt', [
                'credited_early_minutes' => 61,
            ]);
            $this->fail('Approving early OT above actual 60m must fail.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('cannot exceed the actual 60 minutes', collect($e->errors())->flatten()->first());
        }

        $earlyRequest = $service->approve($earlyRequest, $ctx['sic'], 'Endorse 60m early.', [
            'credited_early_minutes' => 60,
        ]);
        $earlyRequest = $service->approve($earlyRequest, $ctx['hrUser'], 'HR finalize 60m early.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $earlyRequest->status);

        $earlyPassDtr->refresh();
        $this->assertTrue((bool) $earlyPassDtr->early_clock_in_approved);
        $this->assertFalse((bool) $earlyPassDtr->overtime_approved);
        $this->assertSame(60, (int) $earlyPassDtr->credited_early_clock_in);
        $this->assertSame(60, (int) $earlyPassDtr->credited_overtime);

        // 3. 59m early + 60m OT: only overtime_only is eligible; early_only & both are rejected
        $otPassDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $ctx['employee']->id,
            'fingerprint_id' => '2001',
            'branch_id' => $ctx['branch']->id,
            'payroll_period_id' => $ctx['openPeriod']->id,
            'date_in' => now()->subDays(4)->toDateString(),
            'time_in' => '07:01:00',
            'date_out' => now()->subDays(4)->toDateString(),
            'time_out' => '18:00:00',
            'schedule_start' => '08:00:00',
            'schedule_end' => '17:00:00',
            'schedule_type' => 'Regular',
            'early_clock_in' => 59,
            'overtime' => 60,
            'overtime_status' => 'Pending',
        ]);

        foreach ([DtrChangeRequest::OT_SCOPE_BOTH, DtrChangeRequest::OT_SCOPE_EARLY_ONLY] as $invalidScope) {
            try {
                $service->submitFromDtr($ctx['employee'], $otPassDtr, [
                    'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
                    'overtime_request_scope' => $invalidScope,
                    'explanation' => 'Should fail when early clock in is 59m.',
                ]);
                $this->fail("Scope {$invalidScope} must fail when early clock in is 59m.");
            } catch (ValidationException $e) {
                $this->assertStringContainsString('60', collect($e->errors())->flatten()->first());
            }
        }

        // 4. 60m early + 60m OT: both eligible and finalizes at exact 60/60 boundary
        $bothPassDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $ctx['employee']->id,
            'fingerprint_id' => '2001',
            'branch_id' => $ctx['branch']->id,
            'payroll_period_id' => $ctx['openPeriod']->id,
            'date_in' => now()->subDays(3)->toDateString(),
            'time_in' => '07:00:00',
            'date_out' => now()->subDays(3)->toDateString(),
            'time_out' => '18:00:00',
            'schedule_start' => '08:00:00',
            'schedule_end' => '17:00:00',
            'schedule_type' => 'Regular',
            'work_hrs' => 600,
            'credited_work_hrs' => 480,
            'early_clock_in' => 60,
            'overtime' => 60,
            'overtime_status' => 'Pending',
        ]);

        $bothRequest = $service->submitFromDtr($ctx['employee'], $bothPassDtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
            'explanation' => 'Exact 60m early + 60m regular OT.',
        ]);

        try {
            $service->updateOvertimeRequest($bothRequest, $ctx['sic'], [
                'approved_overtime_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
                'credited_early_minutes' => 59,
                'credited_overtime_minutes' => 60,
            ]);
            $this->fail('Editing early component below 60m must fail.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('at least 60 minutes', collect($e->errors())->flatten()->first());
        }

        $bothRequest = $service->approve($bothRequest, $ctx['sic'], 'Endorse exact 60/60.', [
            'approved_overtime_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
            'credited_early_minutes' => 60,
            'credited_overtime_minutes' => 60,
        ]);
        $bothRequest = $service->approve($bothRequest, $ctx['hrUser'], 'HR finalize 60/60.');

        $bothPassDtr->refresh();
        $this->assertSame('Approved', $bothPassDtr->overtime_status);
        $this->assertTrue((bool) $bothPassDtr->early_clock_in_approved);
        $this->assertTrue((bool) $bothPassDtr->overtime_approved);
        $this->assertSame(60, (int) $bothPassDtr->credited_early_clock_in);
        $this->assertSame(120, (int) $bothPassDtr->credited_overtime);
        $this->assertSame(600, (int) $bothPassDtr->credited_work_hrs);
    }

    public function test_void_migration_voids_all_pending_overtime_requests_syncs_open_sub_hour_dtrs_is_idempotent_and_preserves_ui_and_notifications(): void
    {
        $ctx = $this->seedContext();
        $service = app(DtrChangeRequestService::class);
        $dateBelow = now()->subDays(7)->toDateString();
        $dateAbove = now()->subDays(6)->toDateString();
        $dateForgot = now()->subDays(5)->toDateString();
        $dateApprovedLegacy = now()->subDays(4)->toDateString();
        $dateRejectedLegacy = now()->subDays(3)->toDateString();
        $dateEarlyEligibleOnly = now()->subDays(2)->toDateString();
        $dateLockedPeriod = now()->subDays(30)->toDateString();

        // Seed legacy pre-threshold DTR records without firing the 60m aggregation observer
        // so both official `dtrs` and `employee_visible_dtrs` genuinely start with legacy `Pending` status.
        [
            $openBelowOfficial,
            $openBelowVisible,
            $openAboveOfficial,
            $openAboveVisible,
            $openEarlyOnlyVisible,
            $openApprovedLegacy,
            $openRejectedLegacy,
            $individuallyLocked,
            $softDeletedBelow,
            $lockedPeriodOfficial,
            $lockedPeriodVisible,
        ] = app(DtrDailyAggregationService::class)->withoutAutomaticRecalculation(function () use (
            $ctx,
            $dateBelow,
            $dateAbove,
            $dateApprovedLegacy,
            $dateRejectedLegacy,
            $dateEarlyEligibleOnly,
            $dateLockedPeriod,
        ): array {
            // Open-period sub-threshold DTR & Visible DTR (45m OT, 14m early, Pending)
            $openBelowOfficial = Dtr::query()->create([
                'employee_id' => $ctx['employee']->id,
                'fingerprint_id' => '2001',
                'branch_id' => $ctx['branch']->id,
                'payroll_period_id' => $ctx['openPeriod']->id,
                'date_in' => $dateBelow,
                'time_in' => '07:46:00',
                'date_out' => $dateBelow,
                'time_out' => '17:45:00',
                'schedule_start' => '08:00:00',
                'schedule_end' => '17:00:00',
                'schedule_type' => 'Regular',
                'early_clock_in' => 14,
                'overtime' => 45,
                'overtime_status' => 'Pending',
            ]);
            $openBelowVisible = EmployeeVisibleDtr::query()->create([
                'employee_id' => $ctx['employee']->id,
                'fingerprint_id' => '2001',
                'branch_id' => $ctx['branch']->id,
                'payroll_period_id' => $ctx['openPeriod']->id,
                'date_in' => $dateBelow,
                'time_in' => '07:46:00',
                'date_out' => $dateBelow,
                'time_out' => '17:45:00',
                'schedule_start' => '08:00:00',
                'schedule_end' => '17:00:00',
                'schedule_type' => 'Regular',
                'early_clock_in' => 14,
                'overtime' => 45,
                'overtime_status' => 'Pending',
            ]);

            // Open-period >= 60m DTR & Visible DTR (75m early, 90m OT, Pending)
            $openAboveOfficial = Dtr::query()->create([
                'employee_id' => $ctx['employee']->id,
                'fingerprint_id' => '2001',
                'branch_id' => $ctx['branch']->id,
                'payroll_period_id' => $ctx['openPeriod']->id,
                'date_in' => $dateAbove,
                'time_in' => '06:45:00',
                'date_out' => $dateAbove,
                'time_out' => '18:30:00',
                'schedule_start' => '08:00:00',
                'schedule_end' => '17:00:00',
                'schedule_type' => 'Regular',
                'early_clock_in' => 75,
                'overtime' => 90,
                'overtime_status' => 'Pending',
            ]);
            $openAboveVisible = EmployeeVisibleDtr::query()->create([
                'employee_id' => $ctx['employee']->id,
                'fingerprint_id' => '2001',
                'branch_id' => $ctx['branch']->id,
                'payroll_period_id' => $ctx['openPeriod']->id,
                'date_in' => $dateAbove,
                'time_in' => '06:45:00',
                'date_out' => $dateAbove,
                'time_out' => '18:30:00',
                'schedule_start' => '08:00:00',
                'schedule_end' => '17:00:00',
                'schedule_type' => 'Regular',
                'early_clock_in' => 75,
                'overtime' => 90,
                'overtime_status' => 'Pending',
            ]);

            // Open-period early-only >= 60m DTR (60m early, 10m OT, Pending) -> stays Pending
            $openEarlyOnlyVisible = EmployeeVisibleDtr::query()->create([
                'employee_id' => $ctx['employee']->id,
                'fingerprint_id' => '2001',
                'branch_id' => $ctx['branch']->id,
                'payroll_period_id' => $ctx['openPeriod']->id,
                'date_in' => $dateEarlyEligibleOnly,
                'time_in' => '07:00:00',
                'date_out' => $dateEarlyEligibleOnly,
                'time_out' => '17:10:00',
                'schedule_start' => '08:00:00',
                'schedule_end' => '17:00:00',
                'schedule_type' => 'Regular',
                'early_clock_in' => 60,
                'overtime' => 10,
                'overtime_status' => 'Pending',
            ]);

            // Open-period already Approved (< 60m legacy), already Rejected (< 60m), individually locked (< 60m), and soft-deleted (< 60m)
            $openApprovedLegacy = EmployeeVisibleDtr::query()->create([
                'employee_id' => $ctx['employee']->id,
                'fingerprint_id' => '2001',
                'branch_id' => $ctx['branch']->id,
                'payroll_period_id' => $ctx['openPeriod']->id,
                'date_in' => $dateApprovedLegacy,
                'time_in' => '08:00:00',
                'date_out' => $dateApprovedLegacy,
                'time_out' => '17:45:00',
                'schedule_start' => '08:00:00',
                'schedule_end' => '17:00:00',
                'schedule_type' => 'Regular',
                'overtime' => 45,
                'credited_overtime' => 45,
                'overtime_approved' => true,
                'overtime_status' => 'Approved',
            ]);
            $openRejectedLegacy = EmployeeVisibleDtr::query()->create([
                'employee_id' => $ctx['employee']->id,
                'fingerprint_id' => '2001',
                'branch_id' => $ctx['branch']->id,
                'payroll_period_id' => $ctx['openPeriod']->id,
                'date_in' => $dateRejectedLegacy,
                'time_in' => '08:00:00',
                'date_out' => $dateRejectedLegacy,
                'time_out' => '17:45:00',
                'schedule_start' => '08:00:00',
                'schedule_end' => '17:00:00',
                'schedule_type' => 'Regular',
                'overtime' => 45,
                'overtime_status' => 'Rejected',
            ]);
            $individuallyLocked = EmployeeVisibleDtr::query()->create([
                'employee_id' => $ctx['employee']->id,
                'fingerprint_id' => '2001',
                'branch_id' => $ctx['branch']->id,
                'payroll_period_id' => $ctx['openPeriod']->id,
                'date_in' => now()->subDays(8)->toDateString(),
                'time_in' => '08:00:00',
                'date_out' => now()->subDays(8)->toDateString(),
                'time_out' => '17:45:00',
                'schedule_start' => '08:00:00',
                'schedule_end' => '17:00:00',
                'schedule_type' => 'Regular',
                'overtime' => 45,
                'overtime_status' => 'Pending',
            ]);
            DB::table('employee_visible_dtrs')->where('id', $individuallyLocked->id)->update(['is_locked' => true]);
            $softDeletedBelow = EmployeeVisibleDtr::query()->create([
                'employee_id' => $ctx['employee']->id,
                'fingerprint_id' => '2001',
                'branch_id' => $ctx['branch']->id,
                'payroll_period_id' => $ctx['openPeriod']->id,
                'date_in' => now()->subDays(9)->toDateString(),
                'time_in' => '08:00:00',
                'date_out' => now()->subDays(9)->toDateString(),
                'time_out' => '17:45:00',
                'schedule_start' => '08:00:00',
                'schedule_end' => '17:00:00',
                'schedule_type' => 'Regular',
                'overtime' => 45,
                'overtime_status' => 'Pending',
            ]);
            $softDeletedBelow->delete();

            // Create DTRs before locking $lockedPeriod -> must NOT be touched by migration
            $lockedPeriodOfficial = Dtr::query()->create([
                'employee_id' => $ctx['employee']->id,
                'fingerprint_id' => '2001',
                'branch_id' => $ctx['branch']->id,
                'payroll_period_id' => $ctx['lockedPeriod']->id,
                'date_in' => $dateLockedPeriod,
                'time_in' => '08:00:00',
                'date_out' => $dateLockedPeriod,
                'time_out' => '17:45:00',
                'schedule_start' => '08:00:00',
                'schedule_end' => '17:00:00',
                'schedule_type' => 'Regular',
                'overtime' => 45,
                'overtime_status' => 'Pending',
            ]);
            $lockedPeriodVisible = EmployeeVisibleDtr::query()->create([
                'employee_id' => $ctx['employee']->id,
                'fingerprint_id' => '2001',
                'branch_id' => $ctx['branch']->id,
                'payroll_period_id' => $ctx['lockedPeriod']->id,
                'date_in' => $dateLockedPeriod,
                'time_in' => '08:00:00',
                'date_out' => $dateLockedPeriod,
                'time_out' => '17:45:00',
                'schedule_start' => '08:00:00',
                'schedule_end' => '17:00:00',
                'schedule_type' => 'Regular',
                'overtime' => 45,
                'overtime_status' => 'Pending',
            ]);
            $ctx['lockedPeriod']->update(['is_locked' => true]);

            return [
                $openBelowOfficial,
                $openBelowVisible,
                $openAboveOfficial,
                $openAboveVisible,
                $openEarlyOnlyVisible,
                $openApprovedLegacy,
                $openRejectedLegacy,
                $individuallyLocked,
                $softDeletedBelow,
                $lockedPeriodOfficial,
                $lockedPeriodVisible,
            ];
        });

        // Seed Forgot To Punch DTR for non-OT preservation check
        $forgotDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $ctx['employee']->id,
            'fingerprint_id' => '2001',
            'branch_id' => $ctx['branch']->id,
            'payroll_period_id' => $ctx['openPeriod']->id,
            'date_in' => $dateForgot,
            'time_in' => '08:00:00',
            'date_out' => $dateForgot,
            'time_out' => null,
            'schedule_start' => '08:00:00',
            'schedule_end' => '17:00:00',
            'schedule_type' => 'Regular',
        ]);

        // Seed 5 DtrChangeRequests:
        // (1) Legacy Pending OT (< 60m) at Level 1 Station
        $legacyBelowOtRequest = DtrChangeRequest::query()->create([
            'employee_id' => $ctx['employee']->id,
            'branch_id' => $ctx['branch']->id,
            'payroll_period_id' => $ctx['openPeriod']->id,
            'employee_visible_dtr_id' => $openBelowVisible->id,
            'dtr_id' => $openBelowOfficial->id,
            'dtr_date' => $dateBelow,
            'date_from' => $dateBelow,
            'date_to' => $dateBelow,
            'assigned_employee_id' => $ctx['sic']->id,
            'employee_name_snapshot' => $ctx['employee']->full_name,
            'employee_company_id_snapshot' => $ctx['employee']->company_id,
            'branch_name_snapshot' => $ctx['branch']->branch_name,
            'payroll_period_title_snapshot' => $ctx['openPeriod']->title,
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'description' => 'Legacy 45m OT request.',
            'status' => DtrChangeRequest::STATUS_PENDING,
            'current_level' => 1,
            'total_levels' => 2,
            'current_step_label' => 'SIC TAGUM',
            'workflow_snapshot' => [
                ['level' => 1, 'label' => 'SIC TAGUM', 'approver_employee_id' => $ctx['sic']->id, 'approver_name' => $ctx['sic']->full_name, 'is_hr' => false],
                ['level' => 2, 'label' => 'HR Final Approval', 'approver_employee_id' => null, 'approver_name' => 'HR', 'is_hr' => true],
            ],
            'approvals_history' => [],
            'original_values' => [
                'early_clock_in' => 14,
                'overtime' => 45,
                'overtime_status' => 'Pending',
                'time_in' => '07:46:00',
                'time_out' => '17:45:00',
                'schedule_start' => '08:00:00',
                'schedule_end' => '17:00:00',
                'schedule_type' => 'Regular',
            ],
            'proposed_values' => [
                'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY,
            ],
        ]);

        // (2) Pending OT (>= 60m) already endorsed by SIC and waiting at HR Final Approval -> ALSO voided ("void all")
        $aboveOtRequest = $service->submitFromDtr($ctx['employee'], $openAboveVisible, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
            'explanation' => '75m early + 90m OT awaiting HR.',
        ]);
        $aboveOtRequest = $service->approve($aboveOtRequest, $ctx['sic'], 'SIC endorsed to HR.');
        $this->assertTrue($aboveOtRequest->isPendingHrApproval());

        // (3) Pending non-OT (Forgot To Punch) -> must stay Pending
        $pendingForgotRequest = $service->submitFromDtr($ctx['employee'], $forgotDtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Missed afternoon punch.',
        ]);

        // (4) Already Approved OT request -> must stay Approved
        $approvedOtRequest = DtrChangeRequest::query()->create([
            'employee_id' => $ctx['employee']->id,
            'branch_id' => $ctx['branch']->id,
            'payroll_period_id' => $ctx['openPeriod']->id,
            'employee_visible_dtr_id' => $openApprovedLegacy->id,
            'dtr_date' => $dateApprovedLegacy,
            'date_from' => $dateApprovedLegacy,
            'date_to' => $dateApprovedLegacy,
            'employee_name_snapshot' => $ctx['employee']->full_name,
            'employee_company_id_snapshot' => $ctx['employee']->company_id,
            'branch_name_snapshot' => $ctx['branch']->branch_name,
            'payroll_period_title_snapshot' => $ctx['openPeriod']->title,
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'description' => 'Already approved OT.',
            'status' => DtrChangeRequest::STATUS_APPROVED,
            'current_level' => 2,
            'total_levels' => 2,
            'reviewed_at' => now()->subDay(),
            'employee_seen_at' => now()->subDay(),
            'approvals_history' => [],
        ]);

        // (5) Already Rejected OT request -> must stay Rejected
        $rejectedOtRequest = DtrChangeRequest::query()->create([
            'employee_id' => $ctx['employee']->id,
            'branch_id' => $ctx['branch']->id,
            'payroll_period_id' => $ctx['openPeriod']->id,
            'employee_visible_dtr_id' => $openRejectedLegacy->id,
            'dtr_date' => $dateRejectedLegacy,
            'date_from' => $dateRejectedLegacy,
            'date_to' => $dateRejectedLegacy,
            'employee_name_snapshot' => $ctx['employee']->full_name,
            'employee_company_id_snapshot' => $ctx['employee']->company_id,
            'branch_name_snapshot' => $ctx['branch']->branch_name,
            'payroll_period_title_snapshot' => $ctx['openPeriod']->title,
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'description' => 'Already rejected OT.',
            'status' => DtrChangeRequest::STATUS_REJECTED,
            'rejection_reason' => 'Not authorized.',
            'current_level' => 1,
            'total_levels' => 2,
            'reviewed_at' => now()->subDay(),
            'employee_seen_at' => now()->subDay(),
            'approvals_history' => [],
        ]);

        // Verify genuine pre-migration `Pending` state before running the void migration
        $this->assertSame('Pending', $openBelowOfficial->fresh()->overtime_status);
        $this->assertSame('Pending', $openBelowVisible->fresh()->overtime_status);
        $this->assertSame('Pending', $individuallyLocked->fresh()->overtime_status);
        $this->assertSame('Pending', EmployeeVisibleDtr::withTrashed()->findOrFail($softDeletedBelow->id)->overtime_status);
        $this->assertSame('Pending', $lockedPeriodOfficial->fresh()->overtime_status);
        $this->assertSame('Pending', $lockedPeriodVisible->fresh()->overtime_status);

        // Run the non-destructive void migration
        $migration = require database_path('migrations/2026_11_11_000001_void_pending_overtime_requests_for_one_hour_threshold.php');
        $migration->up();

        // A. Both pending OT requests (< 60m AND >= 60m / HR stage) are now Voided
        foreach ([$legacyBelowOtRequest->fresh(), $aboveOtRequest->fresh()] as $voidedReq) {
            $this->assertSame(DtrChangeRequest::STATUS_VOIDED, $voidedReq->status);
            $this->assertSame('Voided', $voidedReq->display_status);
            $this->assertNull($voidedReq->assigned_employee_id);
            $this->assertSame('Voided (1-Hour Overtime Policy)', $voidedReq->current_step_label);
            $this->assertSame('Voided: Minimum overtime threshold updated to 1 hour (60 minutes).', $voidedReq->rejection_reason);
            $this->assertNotNull($voidedReq->reviewed_at);
            $this->assertNull($voidedReq->employee_seen_at);
            $this->assertSame('System (1-Hour Overtime Policy)', $voidedReq->reviewer_name);
            $lastHistory = collect($voidedReq->approvals_history)->last();
            $this->assertSame('Voided', $lastHistory['action']);
            $this->assertSame('System (1-Hour Overtime Policy)', $lastHistory['actor_name']);
        }
        // Prior SIC approval entry is preserved before System Voided entry on $aboveOtRequest
        $this->assertCount(2, $aboveOtRequest->fresh()->approvals_history);

        // B. Pending Forgot To Punch, Approved OT, and Rejected OT are untouched
        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $pendingForgotRequest->fresh()->status);
        $this->assertSame($ctx['sic']->id, $pendingForgotRequest->fresh()->assigned_employee_id);
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $approvedOtRequest->fresh()->status);
        $this->assertSame(DtrChangeRequest::STATUS_REJECTED, $rejectedOtRequest->fresh()->status);

        // C. Open-period unlocked non-deleted < 60m Pending DTRs -> 'n/a'; >= 60m / Approved / Rejected / Locked / Deleted untouched
        $this->assertSame('n/a', $openBelowOfficial->fresh()->overtime_status);
        $this->assertSame('n/a', $openBelowVisible->fresh()->overtime_status);
        $this->assertSame('Pending', $openAboveOfficial->fresh()->overtime_status);
        $this->assertSame('Pending', $openAboveVisible->fresh()->overtime_status);
        $this->assertSame('Pending', $openEarlyOnlyVisible->fresh()->overtime_status);
        $this->assertSame('Approved', $openApprovedLegacy->fresh()->overtime_status);
        $this->assertSame('Rejected', $openRejectedLegacy->fresh()->overtime_status);
        $this->assertSame('Pending', $individuallyLocked->fresh()->overtime_status);
        $this->assertSame('Pending', EmployeeVisibleDtr::withTrashed()->findOrFail($softDeletedBelow->id)->overtime_status);
        $this->assertSame('Pending', $lockedPeriodOfficial->fresh()->overtime_status);
        $this->assertSame('Pending', $lockedPeriodVisible->fresh()->overtime_status);

        // D. Idempotency: running up() again does not duplicate history entries or modify already voided requests
        $firstHistoryCount = count($aboveOtRequest->fresh()->approvals_history);
        $firstReviewedAt = $aboveOtRequest->fresh()->reviewed_at?->toDateTimeString();
        $migration->up();
        $this->assertCount($firstHistoryCount, $aboveOtRequest->fresh()->approvals_history);
        $this->assertSame($firstReviewedAt, $aboveOtRequest->fresh()->reviewed_at?->toDateTimeString());

        // E. Pending counts & badges for SIC and HR no longer include the voided requests
        $this->assertSame(0, DtrChangeRequest::query()->pendingForHr()->count());
        Filament::setCurrentPanel(Filament::getPanel('employee'));
        $this->actingAs($ctx['sicUser']);
        $this->assertSame('1', ReviewDtrChangeRequests::getNavigationBadge()); // Only $pendingForgotRequest

        // F. Employee DTR unseen notification count sees the 2 voided requests, and opening DtrChangeRequests clears it
        $this->actingAs($ctx['empUser']);
        $empDtrPage = new EmployeeDtrPage;
        $this->assertSame(2, $empDtrPage->getUnseenReviewedRequestCount());
        $this->assertSame('2', $empDtrPage->unseenReviewedRequestBadge());

        $empCrPage = Livewire::actingAs($ctx['empUser'])
            ->test(EmployeeDtrChangeRequestsPage::class, ['periodId' => (string) $ctx['openPeriod']->id])
            ->assertSuccessful()
            ->set('activeTab', 'voided')
            ->assertCanSeeTableRecords([$legacyBelowOtRequest, $aboveOtRequest])
            ->assertCanNotSeeTableRecords([$pendingForgotRequest, $approvedOtRequest, $rejectedOtRequest]);

        // Unseen badge is now cleared after viewing DtrChangeRequests
        $this->assertSame(0, $empDtrPage->getUnseenReviewedRequestCount());

        // Details modal renders Void Reason, Voided step pill, and live Current OT Status cleanly
        $empCrPage
            ->mountTableAction('view', $legacyBelowOtRequest->fresh())
            ->assertMountedActionModalSee([
                'Void Reason:',
                'Minimum overtime threshold updated to 1 hour (60 minutes)',
                'Voided',
                'Current OT Status:',
                'n/a',
            ])
            ->unmountTableAction();

        // Station history & HR D.T.R Change Requests 'voided' tabs display voided requests cleanly
        Livewire::actingAs($ctx['sicUser'])
            ->test(StationChangeRequestHistory::class)
            ->assertSuccessful()
            ->set('activeTab', 'voided')
            ->assertCanSeeTableRecords([$legacyBelowOtRequest, $aboveOtRequest]);

        Filament::setCurrentPanel(Filament::getPanel('hr'));
        Livewire::actingAs($ctx['hrUser'])
            ->test(HrDtrChangeRequestsPage::class)
            ->assertSuccessful()
            ->set('activeTab', 'voided')
            ->assertCanSeeTableRecords([$legacyBelowOtRequest, $aboveOtRequest]);

        // G. Re-submission after voiding: blocked for < 60m DTR, allowed for >= 60m DTR
        try {
            $service->submitFromDtr($ctx['employee'], $openBelowVisible->fresh(), [
                'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
                'explanation' => 'Trying to re-submit sub-hour DTR.',
            ]);
            $this->fail('Re-submission on 45m OT DTR must be blocked.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('60 minutes', collect($e->errors())->flatten()->first());
        }

        $resubmitted = $service->submitFromDtr($ctx['employee'], $openAboveVisible->fresh(), [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
            'explanation' => 'Re-submitting valid >= 60m OT after void migration.',
        ]);
        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $resubmitted->status);
        $this->assertNotSame($aboveOtRequest->id, $resubmitted->id);
        $this->assertSame(2, DB::table('dtr_change_requests')->where('status', 'Voided')->count());
    }
}
