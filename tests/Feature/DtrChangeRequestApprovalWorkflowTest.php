<?php

namespace Tests\Feature;

use App\Filament\Employee\Pages\Station\ReviewDtrChangeRequests;
use App\Filament\Employee\Widgets\StationDtrManageTable;
use App\Filament\Pages\DtrChangeRequests as HrDtrChangeRequests;
use App\Models\Branch;
use App\Models\Dtr;
use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\LeaveApprovalLevel;
use App\Models\LeaveApprovalWorkflow;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\DtrChangeRequestService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class TestStationDtrManageTable extends StationDtrManageTable
{
    public function testCanEditDtrRecord(Dtr $record): bool
    {
        return $this->canEditDtrRecord($record);
    }

    public function testUpdateDtr(Dtr $record, array $data): bool
    {
        return $this->updateDtr($record, $data);
    }
}

class DtrChangeRequestApprovalWorkflowTest extends TestCase
{
    private function hrReviewer(): User
    {
        $hr = User::query()->where('email', 'dtr-final-hr@example.test')->first();
        if (! $hr) {
            $hr = User::query()->findOrFail(DB::table('users')->insertGetId([
                'email' => 'dtr-final-hr@example.test',
                'name' => 'DTR Final HR',
                'role' => 'hr',
                'password' => 'secret',
            ]));
        }

        return $hr;
    }

    private function approveAndCompleteHrWhenPending(
        DtrChangeRequestService $service,
        DtrChangeRequest $request,
        Employee|User $reviewer,
        ?string $remarks = null,
        array $approvalData = [],
    ): DtrChangeRequest {
        $request = $service->approve($request, $reviewer, $remarks, $approvalData);
        if ($request->isPendingHrApproval()) {
            $this->assertSame(DtrChangeRequest::STATUS_PENDING, $request->status);
            $this->assertNull($request->assigned_employee_id);
            $request = $service->approve($request, $this->hrReviewer(), 'Approved by HR.');
        }

        return $request;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTables();
        Filament::setCurrentPanel(Filament::getPanel('employee'));
    }

    protected function createTestTables(): void
    {
        Schema::dropAllTables();

        Schema::create('counters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('uid')->default(0);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('action_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_role')->nullable();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('model_label')->nullable();
            $table->string('record_label')->nullable();
            $table->string('summary')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->json('changed_data')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Test User');
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->default('secret');
            $table->string('remember_token', 100)->nullable();
            $table->string('role', 30)->default('employee');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->boolean('is_disabled')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('branch_name');
            $table->string('branch_address')->nullable();
            $table->string('mobile_no')->nullable();
            $table->unsignedBigInteger('employee_id')->default(0);
            $table->unsignedInteger('no_of_shifts')->default(1);
            $table->time('reg_sched_start')->nullable();
            $table->time('reg_sched_end')->nullable();
            $table->boolean('is_24hrs')->default(false);
            $table->boolean('has_broken_time')->default(false);
            $table->time('shift1_start')->nullable();
            $table->time('shift1_end')->nullable();
            $table->time('shift2_start')->nullable();
            $table->time('shift2_end')->nullable();
            $table->time('shift3_start')->nullable();
            $table->time('shift3_end')->nullable();
            $table->time('broken_shift1_start')->nullable();
            $table->time('broken_shift1_end')->nullable();
            $table->time('broken_shift2_start')->nullable();
            $table->time('broken_shift2_end')->nullable();
            $table->time('broken_shift3_start')->nullable();
            $table->time('broken_shift3_end')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('designations', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->date('date_start');
            $table->date('date_end');
            $table->date('date_payout');
            $table->text('description')->nullable();
            $table->string('attachment_path', 2048)->nullable();
            $table->string('attachment_original_name', 255)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('auto_lock_blocked_at')->nullable();
            $table->text('auto_lock_blocked_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('holiday_types', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->decimal('rate', 8, 2)->default(100);
            $table->timestamps();
        });

        Schema::create('holidays', function (Blueprint $table): void {
            $table->id();
            $table->date('date')->nullable();
            $table->unsignedBigInteger('holiday_type_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('title')->default('Holiday');
            $table->boolean('is_recurring')->default(false);
            $table->string('month_day', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('holiday_employee_exclusions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('holiday_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->date('date')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_calculation_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id')->nullable();
            $table->decimal('regular_work_days_per_month', 8, 2)->default(26);
            $table->decimal('regular_half_month_days', 8, 2)->default(13);
            $table->decimal('work_hours_per_day', 8, 2)->default(8);
            $table->decimal('half_day_work_day_value', 8, 2)->default(0.5);
            $table->decimal('overtime_rate_multiplier', 8, 2)->default(1);
            $table->decimal('regular_holiday_rate', 8, 2)->default(200);
            $table->decimal('special_holiday_rate', 8, 2)->default(30);
            $table->integer('late_grace_minutes')->default(0);
            $table->decimal('holiday_overtime_premium_rate', 8, 2)->default(0);
            $table->boolean('unworked_regular_holiday_pay_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('uid', 20)->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('company_id', 20)->nullable();
            $table->string('fingerprint_id', 50)->nullable();
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('lastname');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->boolean('is_station_manager')->default(false);
            $table->json('managed_branches')->nullable();
            $table->string('employment_type')->default('Permanent');
            $table->string('employment_status')->default('Regular');
            $table->string('schedule_type')->default('regular');
            $table->string('rate_type')->default('daily');
            $table->decimal('daily_rate', 10, 2)->nullable()->default(0);
            $table->decimal('monthly_rate', 10, 2)->nullable()->default(0);
            $table->double('allowance')->default(0);
            $table->double('salary_adjustment')->default(0);
            $table->unsignedInteger('kids')->default(0);
            $table->double('leave_credits')->default(10);
            $table->double('birthday_leave_credits')->default(1);
            $table->unsignedInteger('leave_credits_year')->nullable();
            $table->date('end_status_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('dtrs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('fingerprint_id')->nullable();
            $table->date('date_in')->nullable();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();
            $table->string('schedule_type')->default('Regular');
            $table->string('schedule_start')->nullable();
            $table->string('schedule_end')->nullable();
            $table->string('day_part')->nullable();
            $table->integer('credited_overtime')->default(0);
            $table->integer('credited_early_clock_in')->default(0);
            $table->integer('undertime')->default(0);
            $table->integer('late')->default(0);
            $table->integer('overtime')->default(0);
            $table->integer('early_clock_in')->default(0);
            $table->integer('work_hrs')->default(0);
            $table->integer('credited_work_hrs')->default(0);
            $table->boolean('overtime_approved')->default(false);
            $table->boolean('early_clock_in_approved')->default(false);
            $table->string('overtime_status')->nullable();
            $table->boolean('is_absent')->default(false);
            $table->integer('absence_minutes')->default(0);
            $table->string('comment')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_manually_edited')->default(false);
            $table->unsignedBigInteger('leave_id')->nullable();
            $table->unsignedBigInteger('on_field_dtr_submission_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('employee_visible_dtrs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('fingerprint_id')->nullable();
            $table->date('date_in')->nullable();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();
            $table->string('schedule_type')->default('Regular');
            $table->string('schedule_start')->nullable();
            $table->string('schedule_end')->nullable();
            $table->string('day_part')->nullable();
            $table->integer('credited_overtime')->default(0);
            $table->integer('credited_early_clock_in')->default(0);
            $table->integer('undertime')->default(0);
            $table->integer('late')->default(0);
            $table->integer('overtime')->default(0);
            $table->integer('early_clock_in')->default(0);
            $table->integer('work_hrs')->default(0);
            $table->integer('credited_work_hrs')->default(0);
            $table->boolean('overtime_approved')->default(false);
            $table->boolean('early_clock_in_approved')->default(false);
            $table->string('overtime_status')->nullable();
            $table->boolean('is_absent')->default(false);
            $table->integer('absence_minutes')->default(0);
            $table->string('comment')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_manually_edited')->default(false);
            $table->unsignedBigInteger('manual_edited_by_employee_id')->nullable();
            $table->timestamp('manual_edited_at')->nullable();
            $table->boolean('needs_review')->default(false);
            $table->text('review_reason')->nullable();
            $table->string('dtr_source')->nullable();
            $table->unsignedBigInteger('dtr_source_id')->nullable();
            $table->unsignedBigInteger('leave_id')->nullable();
            $table->unsignedBigInteger('on_field_dtr_submission_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('leave_approval_workflows', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->string('active_scope_key')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_approval_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_id')->constrained('leave_approval_workflows')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('label', 100);
            $table->unsignedBigInteger('approver_employee_id');
            $table->unsignedBigInteger('alternate_employee_id')->nullable();
        });

        Schema::create('dtr_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id', 36)->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('assigned_employee_id')->nullable();
            $table->unsignedBigInteger('reviewed_by_employee_id')->nullable();
            $table->date('date_from');
            $table->date('date_to');
            $table->string('request_type');
            $table->text('description')->nullable();
            $table->string('attachment_path', 2048)->nullable();
            $table->string('attachment_original_name', 255)->nullable();
            $table->string('status')->default('Pending');
            $table->text('reviewer_remarks')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('employee_seen_at')->nullable();
            $table->string('employee_name_snapshot')->nullable();
            $table->string('employee_company_id_snapshot')->nullable();
            $table->string('branch_name_snapshot')->nullable();
            $table->string('payroll_period_title_snapshot')->nullable();
            $table->unsignedBigInteger('dtr_id')->nullable();
            $table->unsignedBigInteger('employee_visible_dtr_id')->nullable();
            $table->date('dtr_date')->nullable();
            $table->unsignedInteger('current_level')->default(1);
            $table->unsignedInteger('total_levels')->default(1);
            $table->string('current_step_label', 100)->nullable();
            $table->json('workflow_snapshot')->nullable();
            $table->json('approvals_history')->nullable();
            $table->json('original_values')->nullable();
            $table->json('proposed_values')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('first_level_editor_id')->nullable();
            $table->timestamp('first_level_edited_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    protected function setupEmployeeAndBranch(): array
    {
        $branch = Branch::query()->create([
            'branch_name' => 'Metro Station',
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '17:00:00',
        ]);

        $employeeUser = User::query()->create([
            'name' => 'John Employee',
            'email' => 'john@example.com',
            'role' => 'employee',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $employeeUser->id,
            'firstname' => 'John',
            'lastname' => 'Doe',
            'company_id' => 'EMP-001',
            'branch_id' => $branch->id,
            'fingerprint_id' => '1001',
        ]);

        $sicUser = User::query()->create([
            'name' => 'Jane SIC',
            'email' => 'jane.sic@example.com',
            'role' => 'employee',
        ]);

        $sic = Employee::query()->create([
            'user_id' => $sicUser->id,
            'firstname' => 'Jane',
            'lastname' => 'SIC',
            'company_id' => 'SIC-001',
            'branch_id' => $branch->id,
            'is_station_manager' => true,
            'managed_branches' => [$branch->id],
        ]);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => 'Default Branch Approval Flow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'Station In-Charge',
            'approver_employee_id' => $sic->id,
        ]);

        $period = PayrollPeriod::query()->create([
            'title' => 'September 1-15 2026',
            'date_start' => '2026-09-01',
            'date_end' => '2026-09-15',
            'date_payout' => '2026-09-20',
            'is_locked' => false,
        ]);

        return [$employee, $branch, $period, $sic, $sicUser];
    }

    /**
     * Scenario 1: Single-Level Forgot To Punch Approval
     * Workflow: Employee -> SIC
     * SIC edits missing Time Out and approves.
     * Expected: Immediate finalization, no second approval required, DTR updated and marked Approved.
     */
    public function test_scenario_1_single_level_forgot_to_punch(): void
    {
        [$employee, $branch, $period, $sic, $sicUser] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-02',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-02',
            'time_out' => null,
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'is_absent' => true,
        ]);

        $service = app(DtrChangeRequestService::class);

        // Employee submits Forgot To Punch request
        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Forgot to punch out at 5pm due to emergency meeting.',
        ]);

        $this->assertSame(2, $request->total_levels);
        $this->assertSame(1, $request->current_level);
        $this->assertSame($sic->id, $request->assigned_employee_id);
        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $request->status);
        $this->assertTrue($request->is_first_level);
        $this->assertFalse($request->is_final_level);

        // SIC (First-level) edits DTR
        $this->assertTrue($request->canBeEditedBy($sic));
        $request = $service->saveCorrections($request, $sic, [
            'date_in' => '2026-09-02',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-02',
            'time_out' => '05:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
        ]);

        $this->assertSame('05:00 PM', $request->proposed_values['time_out']);
        $this->assertSame($sic->id, $request->first_level_editor_id);

        // SIC approves the request
        $this->assertTrue($request->canBeReviewedBy($sic));
        $request = $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Verified with CCTV log.');

        // Request becomes Approved immediately
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);
        $this->assertCount(2, $request->approvals_history);
        $this->assertSame('Approved', $request->approvals_history[0]['action']);

        // DTR is finalized
        $dtr->refresh();
        $this->assertSame('05:00 PM', $dtr->time_out);
        $this->assertFalse((bool) $dtr->is_absent);
        $this->assertGreaterThan(0, $dtr->work_hrs);
    }

    /**
     * Scenario 2: Two-Level Forgot To Punch Approval
     * Workflow: Employee -> SIC -> RC
     * SIC edits DTR and approves. RC can only review and approve/reject.
     * When RC approves, request becomes Approved and DTR is finalized.
     */
    public function test_scenario_2_two_level_forgot_to_punch(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        // Create RC user and employee
        $rcUser = User::query()->create(['name' => 'Robert RC', 'role' => 'employee']);
        $rc = Employee::query()->create([
            'user_id' => $rcUser->id,
            'firstname' => 'Robert',
            'lastname' => 'RC',
            'company_id' => 'RC-001',
            'branch_id' => $branch->id,
        ]);

        // Configure 2-level workflow: SIC -> RC
        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => 'Station 2-Level Workflow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'Station In-Charge',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'Regional Coordinator',
            'approver_employee_id' => $rc->id,
        ]);

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-03',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-03',
            'time_out' => null,
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'is_absent' => true,
        ]);

        $service = app(DtrChangeRequestService::class);

        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'System punch malfunction at 5pm.',
        ]);

        $this->assertSame(3, $request->total_levels);
        $this->assertSame(1, $request->current_level);
        $this->assertSame($sic->id, $request->assigned_employee_id);

        // SIC edits DTR
        $request = $service->saveCorrections($request, $sic, [
            'date_in' => '2026-09-03',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-03',
            'time_out' => '05:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
        ]);

        // SIC approves -> moves to Level 2
        $request = $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Endorsed to RC.');

        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $request->status);
        $this->assertSame(2, $request->current_level);
        $this->assertSame($rc->id, $request->assigned_employee_id);
        $this->assertFalse($request->is_first_level);
        $this->assertFalse($request->is_final_level);

        // DTR not finalized yet
        $dtr->refresh();
        $this->assertNull($dtr->time_out);

        // RC cannot edit DTR
        $this->assertFalse($request->canBeEditedBy($rc));
        $this->expectException(AuthorizationException::class);
        $service->saveCorrections($request, $rc, [
            'date_in' => '2026-09-03',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-03',
            'time_out' => '06:00 PM',
        ]);
    }

    public function test_scenario_2_part_b_rc_approves_and_finalizes(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $rcUser = User::query()->create(['name' => 'Robert RC', 'role' => 'employee']);
        $rc = Employee::query()->create([
            'user_id' => $rcUser->id,
            'firstname' => 'Robert',
            'lastname' => 'RC',
            'company_id' => 'RC-001',
            'branch_id' => $branch->id,
        ]);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => 'Station 2-Level Workflow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'Station In-Charge',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'Regional Coordinator',
            'approver_employee_id' => $rc->id,
        ]);

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-03',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-03',
            'time_out' => null,
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'is_absent' => true,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'System punch malfunction at 5pm.',
        ]);

        $request = $service->saveCorrections($request, $sic, [
            'date_in' => '2026-09-03',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-03',
            'time_out' => '05:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
        ]);

        $request = $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Endorsed to RC.');

        // RC can review and approve
        $this->assertTrue($request->canBeReviewedBy($rc));
        $request = $this->approveAndCompleteHrWhenPending($service, $request, $rc, 'Approved by RC.');

        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);
        $this->assertCount(3, $request->approvals_history);

        // DTR is now officially finalized
        $dtr->refresh();
        $this->assertSame('05:00 PM', $dtr->time_out);
        $this->assertFalse((bool) $dtr->is_absent);
        $this->assertGreaterThan(0, $dtr->work_hrs);
    }

    /**
     * Scenario 3: Three-Level Time Entries Discrepancy
     * Workflow: Employee -> SIC -> RC -> Area Manager
     */
    public function test_scenario_3_three_level_time_discrepancy(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $rc = Employee::query()->create([
            'firstname' => 'Robert',
            'lastname' => 'RC',
            'branch_id' => $branch->id,
        ]);
        $am = Employee::query()->create([
            'firstname' => 'Alice',
            'lastname' => 'AreaMgr',
            'branch_id' => $branch->id,
        ]);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => '3-Level Workflow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'SIC',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'RC',
            'approver_employee_id' => $rc->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 3,
            'label' => 'Area Manager',
            'approver_employee_id' => $am->id,
        ]);

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-04',
            'time_in' => '09:30 AM',
            'date_out' => '2026-09-04',
            'time_out' => '05:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'late' => 90,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_TIME_DISCREPANCY,
            'explanation' => 'Biometric registered 09:30 AM due to network lag. Actual time in was 08:00 AM.',
        ]);

        $this->assertSame(4, $request->total_levels);
        $this->assertSame(1, $request->current_level);

        // Level 1 SIC edits and approves
        $request = $service->saveCorrections($request, $sic, [
            'date_in' => '2026-09-04',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-04',
            'time_out' => '05:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
        ]);
        $request = $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Endorsed by SIC.');

        $this->assertSame(2, $request->current_level);
        $this->assertSame($rc->id, $request->assigned_employee_id);
        $dtr->refresh();
        $this->assertSame('09:30 AM', $dtr->time_in); // Not yet finalized

        // Level 2 RC approves
        $this->assertFalse($request->canBeEditedBy($rc));
        $request = $this->approveAndCompleteHrWhenPending($service, $request, $rc, 'Reviewed and passed to AM.');

        $this->assertSame(3, $request->current_level);
        $this->assertSame($am->id, $request->assigned_employee_id);
        $dtr->refresh();
        $this->assertSame('09:30 AM', $dtr->time_in); // Still not finalized

        // Level 3 Area Manager approves -> Finalizes
        $this->assertFalse($request->canBeEditedBy($am));
        $request = $this->approveAndCompleteHrWhenPending($service, $request, $am, 'Approved by Area Manager.');

        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);
        $this->assertCount(4, $request->approvals_history);

        $dtr->refresh();
        $this->assertSame('08:00 AM', $dtr->time_in);
        $this->assertSame(0, (int) $dtr->late);
    }

    /**
     * Scenario 4: Valid Overtime Request (>= 60 mins)
     * Early OT: 0, Overtime: 75.
     * Approver can approve/reject OT (within capped threshold). DTR punch editing is not allowed.
     */
    public function test_scenario_4_valid_overtime_request(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-05',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-05',
            'time_out' => '06:15 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'overtime' => 75,
            'early_clock_in' => 0,
            'overtime_approved' => false,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'explanation' => 'Rendered 75 minutes OT assisting closing operations.',
        ]);

        $this->assertSame(DtrChangeRequest::TYPE_OVERTIME_APPROVAL, $request->request_type);
        $this->assertFalse($request->is_editable_type);
        $this->assertFalse($request->canBeEditedBy($sic));

        // Approver approves OT
        $request = $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Overtime approved.');

        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);
        $dtr->refresh();
        $this->assertTrue((bool) $dtr->overtime_approved);
        $this->assertSame(75, (int) $dtr->credited_overtime);
    }

    /**
     * Scenario 5: Invalid Overtime Request (< 60 mins)
     * Early OT: 45, Overtime: 59 -> Blocked by 1-hour (60-min) validation.
     */
    public function test_scenario_5_invalid_overtime_request_blocked(): void
    {
        [$employee, $branch, $period] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-06',
            'time_in' => '07:15 AM',
            'date_out' => '2026-09-06',
            'time_out' => '05:59 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'early_clock_in' => 45,
            'overtime' => 59,
        ]);

        $service = app(DtrChangeRequestService::class);

        $this->expectException(ValidationException::class);
        $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'explanation' => 'Requesting OT approval.',
        ]);
    }

    /**
     * Scenario 6: Duplicate Request Prevention
     */
    public function test_scenario_6_duplicate_request_prevention(): void
    {
        [$employee, $branch, $period] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-07',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-07',
            'time_out' => '06:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'overtime' => 60,
        ]);

        $service = app(DtrChangeRequestService::class);
        $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'explanation' => 'First OT request.',
        ]);

        $this->expectException(ValidationException::class);
        $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'explanation' => 'Second duplicate OT request.',
        ]);
    }

    /**
     * Scenario 7: Unauthorized Direct Editing Blocked
     * SIC attempts to edit an employee DTR in StationDtrManageTable without an active Change Request.
     */
    public function test_scenario_7_unauthorized_direct_editing_blocked(): void
    {
        [$employee, $branch, $period, $sic, $sicUser] = $this->setupEmployeeAndBranch();

        $visibleDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-08',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-08',
            'time_out' => null,
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'is_absent' => true,
        ]);

        $this->actingAs($sicUser);

        $tableWidget = new TestStationDtrManageTable;

        // With no change request, editing is denied
        $this->assertFalse($tableWidget->testCanEditDtrRecord($visibleDtr));
        $this->assertFalse($tableWidget->testUpdateDtr($visibleDtr, ['time_out' => '05:00 PM']));
    }

    /**
     * Scenario 8: Second-Level Approver Cannot Edit DTR
     */
    public function test_scenario_8_second_level_editing_attempt_blocked(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $rcUser = User::query()->create(['name' => 'Robert RC', 'role' => 'employee']);
        $rc = Employee::query()->create([
            'user_id' => $rcUser->id,
            'firstname' => 'Robert',
            'lastname' => 'RC',
            'company_id' => 'RC-001',
            'branch_id' => $branch->id,
        ]);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => '2-Level Workflow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'SIC',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'RC',
            'approver_employee_id' => $rc->id,
        ]);

        $visibleDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-09',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-09',
            'time_out' => null,
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'is_absent' => true,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $visibleDtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Punch out missing.',
        ]);

        // Level 1 SIC edits and approves
        $service->saveCorrections($request, $sic, [
            'date_in' => '2026-09-09',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-09',
            'time_out' => '05:00 PM',
        ]);
        $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Endorsed.');

        // Now acting as RC at level 2
        $this->actingAs($rcUser);
        $tableWidget = new TestStationDtrManageTable;

        $this->assertFalse($tableWidget->testCanEditDtrRecord($visibleDtr));
    }

    /**
     * Scenario 9: Rejection Stops Workflow and Reverts Provisional DTR Edits
     */
    public function test_scenario_9_rejection_reverts_provisional_edits(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $rcUser = User::query()->create(['name' => 'Robert RC', 'role' => 'employee']);
        $rc = Employee::query()->create([
            'user_id' => $rcUser->id,
            'firstname' => 'Robert',
            'lastname' => 'RC',
            'company_id' => 'RC-001',
            'branch_id' => $branch->id,
        ]);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => '2-Level Workflow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'SIC',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'RC',
            'approver_employee_id' => $rc->id,
        ]);

        $visibleDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-10',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-10',
            'time_out' => null,
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'is_absent' => true,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $visibleDtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Punch out missing.',
        ]);

        // Level 1 edits and endorses
        $service->saveCorrections($request, $sic, [
            'date_in' => '2026-09-10',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-10',
            'time_out' => '05:00 PM',
        ]);
        $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Endorsed.');

        // Level 2 RC rejects the request
        $request = $service->reject($request, $rc, 'No record of attendance on CCTV.');

        $this->assertSame(DtrChangeRequest::STATUS_REJECTED, $request->status);
        $this->assertSame('No record of attendance on CCTV.', $request->rejection_reason);

        // Target record is preserved / reverted to original
        $visibleDtr->refresh();
        $this->assertNull($visibleDtr->time_out);
        $this->assertTrue((bool) $visibleDtr->is_absent);
    }

    /**
     * Scenario 10: Cancellation by Employee
     */
    public function test_scenario_10_cancellation_by_employee(): void
    {
        [$employee, $branch, $period] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-11',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-11',
            'time_out' => null,
            'schedule_type' => 'Regular',
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Mistaken request.',
        ]);

        $this->assertTrue($request->canBeCancelledBy($employee));

        $request = $service->cancel($request, $employee);
        $this->assertSame(DtrChangeRequest::STATUS_CANCELLED, $request->status);

        // Cannot cancel already cancelled request
        $this->expectException(ValidationException::class);
        $service->cancel($request, $employee);
    }

    /**
     * Scenario 11: Locked Payroll Period Protection
     */
    public function test_scenario_11_locked_payroll_period_protection(): void
    {
        [$employee, $branch, $period] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-12',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-12',
            'time_out' => '05:00 PM',
            'schedule_type' => 'Regular',
        ]);
        $period->update(['is_locked' => true]);

        $service = app(DtrChangeRequestService::class);

        $this->expectException(ValidationException::class);
        $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_TIME_DISCREPANCY,
            'explanation' => 'Change request on locked period.',
        ]);
    }

    /**
     * Scenario: Overtime Only Requested -> Admin can only approve overtime hours, early OT remains unapproved.
     */
    public function test_overtime_request_scope_overtime_only_approves_only_regular_overtime(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-06',
            'time_in' => '07:15 AM',
            'date_out' => '2026-09-06',
            'time_out' => '06:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'early_clock_in' => 45,
            'overtime' => 60,
            'overtime_approved' => false,
            'early_clock_in_approved' => false,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY,
            'explanation' => 'Only requesting regular overtime approval.',
        ]);

        $this->assertSame(DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY, $request->overtime_request_scope);

        // Approver approves the request (overtime only)
        $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Overtime approved.', [
            'credited_overtime_minutes' => 60,
        ]);

        $dtr->refresh();
        $this->assertTrue((bool) $dtr->overtime_approved);
        $this->assertSame(60, (int) $dtr->credited_overtime);
        $this->assertFalse((bool) $dtr->early_clock_in_approved);
        $this->assertSame(0, (int) $dtr->credited_early_clock_in);
    }

    /**
     * Scenario: Early Clock In Requested -> Only early clock in can be approved, regular overtime remains unapproved.
     */
    public function test_overtime_request_scope_early_only_approves_only_early_clock_in(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-07',
            'time_in' => '06:45 AM',
            'date_out' => '2026-09-07',
            'time_out' => '06:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'early_clock_in' => 75,
            'overtime' => 60,
            'overtime_approved' => false,
            'early_clock_in_approved' => false,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_EARLY_ONLY,
            'explanation' => 'Only requesting early clock in overtime approval.',
        ]);

        $this->assertSame(DtrChangeRequest::OT_SCOPE_EARLY_ONLY, $request->overtime_request_scope);

        // Approver approves early clock in only
        $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Early clock in approved.', [
            'credited_early_minutes' => 75,
        ]);

        $dtr->refresh();
        $this->assertTrue((bool) $dtr->early_clock_in_approved);
        $this->assertSame(75, (int) $dtr->credited_early_clock_in);
        $this->assertFalse((bool) $dtr->overtime_approved);
        $this->assertSame(75, (int) $dtr->credited_overtime);
    }

    /**
     * Scenario: Both Requested -> Manager approves both early clock in and overtime.
     */
    public function test_overtime_request_scope_both_allows_manager_to_approve_both(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-08',
            'time_in' => '06:45 AM',
            'date_out' => '2026-09-08',
            'time_out' => '06:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'early_clock_in' => 75,
            'overtime' => 60,
            'overtime_approved' => false,
            'early_clock_in_approved' => false,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
            'explanation' => 'Requesting approval for both early clock in and regular OT.',
        ]);

        $this->assertSame(DtrChangeRequest::OT_SCOPE_BOTH, $request->overtime_request_scope);

        // Approver approves both components
        $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Both approved.', [
            'approved_overtime_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
            'credited_early_minutes' => 75,
            'credited_overtime_minutes' => 60,
        ]);

        $dtr->refresh();
        $this->assertTrue((bool) $dtr->early_clock_in_approved);
        $this->assertTrue((bool) $dtr->overtime_approved);
        $this->assertSame(75, (int) $dtr->credited_early_clock_in);
        $this->assertSame(135, (int) $dtr->credited_overtime);
    }

    /**
     * Scenario: Both Requested -> Manager chooses to approve Overtime Only.
     */
    public function test_overtime_request_scope_both_allows_manager_to_approve_overtime_only(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-09',
            'time_in' => '06:45 AM',
            'date_out' => '2026-09-09',
            'time_out' => '06:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'early_clock_in' => 75,
            'overtime' => 60,
            'overtime_approved' => false,
            'early_clock_in_approved' => false,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
            'explanation' => 'Requesting both.',
        ]);

        // Manager decides to approve Overtime Only
        $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Only regular OT verified.', [
            'approved_overtime_scope' => DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY,
            'credited_overtime_minutes' => 60,
        ]);

        $dtr->refresh();
        $this->assertTrue((bool) $dtr->overtime_approved);
        $this->assertSame(60, (int) $dtr->credited_overtime);
        $this->assertFalse((bool) $dtr->early_clock_in_approved);
        $this->assertSame(0, (int) $dtr->credited_early_clock_in);
    }

    /**
     * Scenario: Both Requested -> Manager chooses to reject the entire request.
     */
    public function test_overtime_request_scope_both_allows_manager_to_reject(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-10',
            'time_in' => '06:45 AM',
            'date_out' => '2026-09-10',
            'time_out' => '06:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'early_clock_in' => 75,
            'overtime' => 60,
            'overtime_approved' => false,
            'early_clock_in_approved' => false,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
            'explanation' => 'Requesting both.',
        ]);

        // Manager rejects the request
        $request = $service->reject($request, $sic, 'Overtime unauthorized by station head.');

        $this->assertSame(DtrChangeRequest::STATUS_REJECTED, $request->status);

        $dtr->refresh();
        $this->assertFalse((bool) $dtr->overtime_approved);
        $this->assertFalse((bool) $dtr->early_clock_in_approved);
        $this->assertSame(0, (int) $dtr->credited_overtime);
        $this->assertSame(0, (int) $dtr->credited_early_clock_in);
    }

    /**
     * Scenario: Requesting overtime_only when regular OT < 60 is rejected by validation.
     */
    public function test_overtime_request_scope_validation_prevents_ineligible_components(): void
    {
        [$employee, $branch, $period] = $this->setupEmployeeAndBranch();

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-13',
            'time_in' => '06:45 AM',
            'date_out' => '2026-09-13',
            'time_out' => '05:45 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'early_clock_in' => 75,
            'overtime' => 45,
        ]);

        $service = app(DtrChangeRequestService::class);

        // Attempting overtime_only when regular OT is only 45 mins (< 60 mins)
        $this->expectException(ValidationException::class);
        $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY,
            'explanation' => 'Trying to request regular OT only.',
        ]);
    }

    /**
     * Scenario: Multi-Level Overtime Approval -> Level 1 sets early_only approval, Level 2 finalizes.
     */
    public function test_multi_level_overtime_request_scope_advances_and_finalizes(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $rcUser = User::query()->create(['name' => 'Rachel RC', 'role' => 'employee']);
        $rc = Employee::query()->create([
            'user_id' => $rcUser->id,
            'firstname' => 'Rachel',
            'lastname' => 'RC',
            'company_id' => 'RC-002',
            'branch_id' => $branch->id,
        ]);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => '2-Level OT Workflow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'Station In-Charge',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'Regional Coordinator',
            'approver_employee_id' => $rc->id,
        ]);

        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-14',
            'time_in' => '07:00 AM',
            'date_out' => '2026-09-14',
            'time_out' => '06:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'early_clock_in' => 60,
            'overtime' => 60,
            'overtime_approved' => false,
            'early_clock_in_approved' => false,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
            'explanation' => 'Both requested.',
        ]);

        $this->assertSame(1, $request->current_level);
        $this->assertSame(3, $request->total_levels);

        // Level 1 SIC approves with Early Only
        $request = $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Verified early OT only.', [
            'approved_overtime_scope' => DtrChangeRequest::OT_SCOPE_EARLY_ONLY,
            'credited_early_minutes' => 60,
        ]);

        $this->assertSame(2, $request->current_level);
        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $request->status);

        // DTR not finalized yet
        $dtr->refresh();
        $this->assertFalse((bool) $dtr->early_clock_in_approved);

        // Level 2 RC approves and finalizes
        $request = $this->approveAndCompleteHrWhenPending($service, $request, $rc, 'Finalized by RC.');

        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);

        $dtr->refresh();
        $this->assertTrue((bool) $dtr->early_clock_in_approved);
        $this->assertSame(60, (int) $dtr->credited_early_clock_in);
        $this->assertFalse((bool) $dtr->overtime_approved);
        $this->assertSame(60, (int) $dtr->credited_overtime);
    }

    public function test_sequential_visibility_across_three_approval_levels_employee_to_sic_to_rc_to_am(): void
    {
        [$employee, $branch, $period, $sic, $sicUser] = $this->setupEmployeeAndBranch();

        $rcUser = User::query()->create(['name' => 'Robert RC', 'role' => 'employee']);
        $rc = Employee::query()->create([
            'user_id' => $rcUser->id,
            'firstname' => 'Robert',
            'lastname' => 'RC',
            'company_id' => 'RC-001',
            'branch_id' => $branch->id,
            'is_station_manager' => true,
            'managed_branches' => [$branch->id],
        ]);
        $rc->setRelation('user', $rcUser);
        $rcUser->setRelation('employee', $rc);

        $amUser = User::query()->create(['name' => 'Alice AM', 'role' => 'employee']);
        $am = Employee::query()->create([
            'user_id' => $amUser->id,
            'firstname' => 'Alice',
            'lastname' => 'AreaMgr',
            'company_id' => 'AM-001',
            'branch_id' => $branch->id,
            'is_station_manager' => true,
            'managed_branches' => [$branch->id],
        ]);
        $am->setRelation('user', $amUser);
        $amUser->setRelation('employee', $am);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => '3-Level Approval Flow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'Station In-Charge',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'Regional Coordinator',
            'approver_employee_id' => $rc->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 3,
            'label' => 'Area Manager',
            'approver_employee_id' => $am->id,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submit($employee, [
            'payroll_period_id' => $period->id,
            'date_from' => '2026-09-06',
            'date_to' => '2026-09-06',
            'request_type' => DtrChangeRequest::TYPE_WRONG_SCHEDULE,
            'description' => 'Applied wrong schedule.',
        ]);

        // === LEVEL 1: Visible only to SIC ===
        $this->assertSame(4, $request->total_levels);
        $this->assertSame(1, $request->current_level);
        $this->assertSame($sic->id, $request->assigned_employee_id);

        // Pending count by scope
        $this->assertSame(1, DtrChangeRequest::query()->pendingForApprover($sic)->count());
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($rc)->count());
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($am)->count());

        // Navigation Badge visibility
        $this->actingAs($sicUser);
        $this->assertSame('1', ReviewDtrChangeRequests::getNavigationBadge());
        $this->actingAs($rcUser);
        $this->assertNull(ReviewDtrChangeRequests::getNavigationBadge());
        $this->actingAs($amUser);
        $this->assertNull(ReviewDtrChangeRequests::getNavigationBadge());

        // Authorization checks
        $this->assertTrue($request->canBeReviewedBy($sic));
        $this->assertFalse($request->canBeReviewedBy($rc));
        $this->assertFalse($request->canBeReviewedBy($am));

        // Premature approval / rejection attempts by higher levels are blocked
        try {
            $this->approveAndCompleteHrWhenPending($service, $request, $rc, 'Trying premature approve');
            $this->fail('RC should not be authorized to approve at Level 1');
        } catch (AuthorizationException $e) {
            $this->assertTrue(true);
        }

        try {
            $this->approveAndCompleteHrWhenPending($service, $request, $am, 'Trying premature approve');
            $this->fail('Area Manager should not be authorized to approve at Level 1');
        } catch (AuthorizationException $e) {
            $this->assertTrue(true);
        }

        // === LEVEL 1 APPROVAL by SIC -> advances to LEVEL 2 (RC) ===
        $request = $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Endorsed by SIC.');
        $this->assertSame(2, $request->current_level);
        $this->assertSame($rc->id, $request->assigned_employee_id);

        // Pending count by scope
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($sic)->count());
        $this->assertSame(1, DtrChangeRequest::query()->pendingForApprover($rc)->count());
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($am)->count());

        // Navigation Badge visibility
        $this->actingAs($sicUser);
        $this->assertNull(ReviewDtrChangeRequests::getNavigationBadge());
        $this->actingAs($rcUser);
        $this->assertSame('1', ReviewDtrChangeRequests::getNavigationBadge());
        $this->actingAs($amUser);
        $this->assertNull(ReviewDtrChangeRequests::getNavigationBadge());

        // Authorization checks
        $this->assertFalse($request->canBeReviewedBy($sic));
        $this->assertTrue($request->canBeReviewedBy($rc));
        $this->assertFalse($request->canBeReviewedBy($am));

        // Area Manager still blocked
        try {
            $this->approveAndCompleteHrWhenPending($service, $request, $am, 'Trying premature approve');
            $this->fail('Area Manager should not be authorized to approve at Level 2');
        } catch (AuthorizationException $e) {
            $this->assertTrue(true);
        }

        // === LEVEL 2 APPROVAL by RC -> advances to LEVEL 3 (Area Manager) ===
        $request = $this->approveAndCompleteHrWhenPending($service, $request, $rc, 'Endorsed by RC.');
        $this->assertSame(3, $request->current_level);
        $this->assertSame($am->id, $request->assigned_employee_id);

        // Pending count by scope
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($sic)->count());
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($rc)->count());
        $this->assertSame(1, DtrChangeRequest::query()->pendingForApprover($am)->count());

        // Navigation Badge visibility
        $this->actingAs($sicUser);
        $this->assertNull(ReviewDtrChangeRequests::getNavigationBadge());
        $this->actingAs($rcUser);
        $this->assertNull(ReviewDtrChangeRequests::getNavigationBadge());
        $this->actingAs($amUser);
        $this->assertSame('1', ReviewDtrChangeRequests::getNavigationBadge());

        // Authorization checks
        $this->assertFalse($request->canBeReviewedBy($sic));
        $this->assertFalse($request->canBeReviewedBy($rc));
        $this->assertTrue($request->canBeReviewedBy($am));

        // === LEVEL 3 APPROVAL by Area Manager -> Finalizes ===
        $request = $this->approveAndCompleteHrWhenPending($service, $request, $am, 'Final approval by Area Manager.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);
        $this->assertCount(4, $request->approvals_history);

        // All pending counts and badges are cleared
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($sic)->count());
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($rc)->count());
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($am)->count());

        $this->actingAs($amUser);
        $this->assertNull(ReviewDtrChangeRequests::getNavigationBadge());
    }

    public function test_sequential_rejection_at_intermediate_level_stops_workflow_and_hides_from_higher_levels(): void
    {
        [$employee, $branch, $period, $sic, $sicUser] = $this->setupEmployeeAndBranch();

        $rcUser = User::query()->create(['name' => 'Robert RC', 'role' => 'employee']);
        $rc = Employee::query()->create([
            'user_id' => $rcUser->id,
            'firstname' => 'Robert',
            'lastname' => 'RC',
            'company_id' => 'RC-001',
            'branch_id' => $branch->id,
            'is_station_manager' => true,
            'managed_branches' => [$branch->id],
        ]);
        $rc->setRelation('user', $rcUser);
        $rcUser->setRelation('employee', $rc);

        $amUser = User::query()->create(['name' => 'Alice AM', 'role' => 'employee']);
        $am = Employee::query()->create([
            'user_id' => $amUser->id,
            'firstname' => 'Alice',
            'lastname' => 'AreaMgr',
            'company_id' => 'AM-001',
            'branch_id' => $branch->id,
            'is_station_manager' => true,
            'managed_branches' => [$branch->id],
        ]);
        $am->setRelation('user', $amUser);
        $amUser->setRelation('employee', $am);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => '3-Level Approval Flow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'Station In-Charge',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'Regional Coordinator',
            'approver_employee_id' => $rc->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 3,
            'label' => 'Area Manager',
            'approver_employee_id' => $am->id,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submit($employee, [
            'payroll_period_id' => $period->id,
            'date_from' => '2026-09-07',
            'date_to' => '2026-09-07',
            'request_type' => DtrChangeRequest::TYPE_WRONG_SCHEDULE,
            'description' => 'Applied wrong schedule.',
        ]);

        // Level 1 approves
        $request = $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Endorsed by SIC.');
        $this->assertSame(2, $request->current_level);

        // Level 2 (RC) rejects
        $request = $service->reject($request, $rc, 'Rejected due to invalid schedule proof.');
        $this->assertSame(DtrChangeRequest::STATUS_REJECTED, $request->status);

        // Workflow stopped: never forwarded to Area Manager
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($sic)->count());
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($rc)->count());
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($am)->count());

        $this->actingAs($amUser);
        $this->assertNull(ReviewDtrChangeRequests::getNavigationBadge());

        $this->assertFalse($request->canBeReviewedBy($am));

        try {
            $this->approveAndCompleteHrWhenPending($service, $request, $am, 'Area manager trying to approve rejected');
            $this->fail('Area manager should not be able to approve a rejected request');
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }
    }

    public function test_station_endorsement_waits_for_hr_before_finalizing_dtr(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();
        $dtr = Dtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-08',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-08',
            'time_out' => '05:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'overtime' => 60,
            'overtime_approved' => false,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'explanation' => 'One hour of verified overtime.',
        ]);

        $this->assertSame(2, $request->total_levels);
        $this->assertTrue($request->workflow_snapshot[1]['is_hr']);
        $request = $service->approve($request, $sic, 'Endorsed to HR.');

        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $request->status);
        $this->assertTrue($request->isPendingHrApproval());
        $this->assertSame(1, DtrChangeRequest::query()->pendingForHr()->count());
        $this->assertFalse((bool) $dtr->fresh()->overtime_approved);
        $this->assertFalse($request->canBeReviewedBy($sic));

        $request = $service->approve($request, $this->hrReviewer(), 'Final HR approval.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);
        $this->assertTrue((bool) $dtr->fresh()->overtime_approved);
        $this->assertCount(2, $request->approvals_history);
        $this->assertSame('HR Final Approval', $request->approvals_history[1]['label']);
    }

    public function test_single_level_workflow_immediately_visible_to_sic_and_finalized_upon_approval(): void
    {
        [$employee, $branch, $period, $sic, $sicUser] = $this->setupEmployeeAndBranch();

        $service = app(DtrChangeRequestService::class);
        $request = $service->submit($employee, [
            'payroll_period_id' => $period->id,
            'date_from' => '2026-09-08',
            'date_to' => '2026-09-08',
            'request_type' => DtrChangeRequest::TYPE_WRONG_SCHEDULE,
            'description' => 'Single level schedule correction.',
        ]);

        // Single level workflow
        $this->assertSame(2, $request->total_levels);
        $this->assertSame(1, $request->current_level);
        $this->assertSame($sic->id, $request->assigned_employee_id);

        // Immediately visible to SIC
        $this->assertSame(1, DtrChangeRequest::query()->pendingForApprover($sic)->count());
        $this->actingAs($sicUser);
        $this->assertSame('1', ReviewDtrChangeRequests::getNavigationBadge());
        $this->assertTrue($request->canBeReviewedBy($sic));

        // SIC approves -> Automatically treated as fully approved
        $request = $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Approved by SIC.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);

        // Badge and table cleared
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($sic)->count());
        $this->assertNull(ReviewDtrChangeRequests::getNavigationBadge());
    }

    public function test_forgot_to_punch_edit_modal_uses_branch_configured_shift(): void
    {
        [$employee, $branch, $period, $sic, $sicUser] = $this->setupEmployeeAndBranch();

        // Configure branch with Shift 1 & Shift 2
        $branch->update([
            'shift1_start' => '04:30:00',
            'shift1_end' => '12:30:00',
            'shift2_start' => '12:30:00',
            'shift2_end' => '20:30:00',
        ]);

        $visibleDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-02',
            'time_in' => '04:30:00',
            'date_out' => '2026-09-02',
            'time_out' => null,
            'schedule_type' => 'Shift1',
            'schedule_start' => '04:30:00',
            'schedule_end' => '12:30:00',
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $visibleDtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Forgot to punch out at end of shift 1.',
        ]);

        // Level 1 approver saves corrections selecting Shift 1
        $request = $service->saveCorrections($request, $sic, [
            'date_in' => '2026-09-02',
            'time_in' => '04:30:00',
            'date_out' => '2026-09-02',
            'time_out' => '12:30:00',
            'schedule_type' => 'Shift1',
        ]);

        // Sourced directly from branch
        $this->assertSame('04:30:00', $request->proposed_values['schedule_start']);
        $this->assertSame('12:30:00', $request->proposed_values['schedule_end']);
        $this->assertSame('Shift1', $request->proposed_values['schedule_type']);

        // Approving finalizes and updates visible DTR
        $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Verified and approved.');
        $visibleDtr->refresh();
        $this->assertSame('12:30:00', $visibleDtr->time_out);
        $this->assertSame('Shift1', $visibleDtr->schedule_type);
    }

    public function test_time_entry_discrepancy_edit_modal_uses_branch_configured_shift(): void
    {
        [$employee, $branch, $period, $sic, $sicUser] = $this->setupEmployeeAndBranch();

        $branch->update([
            'shift2_start' => '12:30:00',
            'shift2_end' => '20:30:00',
        ]);

        $visibleDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-03',
            'time_in' => '12:00:00',
            'date_out' => '2026-09-03',
            'time_out' => '19:00:00',
            'schedule_type' => 'Regular',
            'schedule_start' => '08:00:00',
            'schedule_end' => '17:00:00',
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $visibleDtr, [
            'request_type' => DtrChangeRequest::TYPE_TIME_DISCREPANCY,
            'explanation' => 'Worked Shift 2 instead of Regular.',
        ]);

        // Level 1 approver corrects schedule to Shift 2
        $request = $service->saveCorrections($request, $sic, [
            'date_in' => '2026-09-03',
            'time_in' => '12:30:00',
            'date_out' => '2026-09-03',
            'time_out' => '20:30:00',
            'schedule_type' => 'Shift2',
        ]);

        $this->assertSame('12:30:00', $request->proposed_values['schedule_start']);
        $this->assertSame('20:30:00', $request->proposed_values['schedule_end']);
        $this->assertSame('Shift2', $request->proposed_values['schedule_type']);

        $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Shift 2 confirmed.');
        $visibleDtr->refresh();
        $this->assertSame('Shift2', $visibleDtr->schedule_type);
        $this->assertSame('20:30:00', $visibleDtr->time_out);
    }

    public function test_save_corrections_rejects_unconfigured_schedule_type_for_branch(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        // Branch only has Regular configured (no Shift 3)
        $branch->update([
            'shift3_start' => null,
            'shift3_end' => null,
        ]);

        $visibleDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-04',
            'time_in' => '08:00:00',
            'date_out' => '2026-09-04',
            'time_out' => null,
            'schedule_type' => 'Regular',
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $visibleDtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Punch out missing.',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("The selected schedule type is not configured for {$branch->branch_name}.");

        $service->saveCorrections($request, $sic, [
            'date_in' => '2026-09-04',
            'time_in' => '20:00:00',
            'date_out' => '2026-09-05',
            'time_out' => '04:00:00',
            'schedule_type' => 'Shift3',
        ]);
    }

    public function test_save_corrections_rejects_when_branch_has_no_schedules_configured(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        // Clear all schedules on branch
        $branch->update([
            'reg_sched_start' => null,
            'reg_sched_end' => null,
            'shift1_start' => null,
            'shift1_end' => null,
        ]);

        $visibleDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-04',
            'time_in' => '08:00:00',
            'date_out' => '2026-09-04',
            'time_out' => null,
            'schedule_type' => 'Regular',
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $visibleDtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Punch out missing.',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("No schedule configuration is available for this employee's assigned station or branch.");

        $service->saveCorrections($request, $sic, [
            'date_in' => '2026-09-04',
            'time_in' => '08:00:00',
            'date_out' => '2026-09-04',
            'time_out' => '17:00:00',
            'schedule_type' => 'Regular',
        ]);
    }

    public function test_save_corrections_handles_overnight_shifts_and_auto_aligns_date_out(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        // Configure overnight shift (8:00 PM to 4:00 AM)
        $branch->update([
            'shift3_start' => '20:00:00',
            'shift3_end' => '04:00:00',
        ]);

        $visibleDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-05',
            'time_in' => '20:00:00',
            'date_out' => '2026-09-05',
            'time_out' => null,
            'schedule_type' => 'Shift3',
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $visibleDtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Overnight shift punch out missing.',
        ]);

        // Submit with same date_in and date_out; backend recognizes overnight shift and auto-adjusts date_out to next day
        $request = $service->saveCorrections($request, $sic, [
            'date_in' => '2026-09-05',
            'time_in' => '20:00:00',
            'date_out' => '2026-09-05',
            'time_out' => '04:00:00',
            'schedule_type' => 'Shift3',
        ]);

        $this->assertSame('2026-09-05', $request->proposed_values['date_in']);
        $this->assertSame('2026-09-06', $request->proposed_values['date_out']);
        $this->assertSame('20:00:00', $request->proposed_values['schedule_start']);
        $this->assertSame('04:00:00', $request->proposed_values['schedule_end']);

        // Approve and verify DTR calculation
        $this->approveAndCompleteHrWhenPending($service, $request, $sic, 'Overnight shift approved.');
        $visibleDtr->refresh();
        $this->assertSame('2026-09-06', $visibleDtr->date_out);
        $this->assertSame('04:00:00', $visibleDtr->time_out);
        $this->assertSame(480, (int) $visibleDtr->work_hrs); // 8 hours overnight work
    }

    public function test_available_schedule_options_for_branch(): void
    {
        [$employee, $branch] = $this->setupEmployeeAndBranch();

        $branch->update([
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '17:00:00',
            'shift1_start' => '04:30:00',
            'shift1_end' => '12:30:00',
            'shift2_start' => null,
            'shift2_end' => null,
            'broken_shift1_start' => '06:00:00',
            'broken_shift1_end' => '10:00:00',
        ]);

        $service = app(DtrChangeRequestService::class);
        $options = $service->availableScheduleOptionsForBranch($branch, withTimes: true);

        $this->assertArrayHasKey('Regular', $options);
        $this->assertArrayHasKey('Shift1', $options);
        $this->assertArrayHasKey('Broken1', $options);
        $this->assertArrayNotHasKey('Shift2', $options);
        $this->assertArrayNotHasKey('Shift3', $options);
        $this->assertStringContainsString('04:30 AM', $options['Shift1']);
    }

    public function test_save_corrections_rejects_invalid_time_format(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $visibleDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-04',
            'time_in' => '08:00:00',
            'date_out' => '2026-09-04',
            'time_out' => null,
            'schedule_type' => 'Regular',
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $visibleDtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Punch out missing.',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Please provide a valid time format for Time In.');

        $service->saveCorrections($request, $sic, [
            'date_in' => '2026-09-04',
            'time_in' => 'invalid-time',
            'date_out' => '2026-09-04',
            'time_out' => '17:00:00',
            'schedule_type' => 'Regular',
        ]);
    }

    public function test_save_corrections_rejects_mismatched_target_dtr_employee(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $otherEmployee = Employee::query()->create([
            'firstname' => 'Other',
            'lastname' => 'Person',
            'company_id' => 'EMP-OTHER',
            'branch_id' => $branch->id,
        ]);

        $visibleDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $otherEmployee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-04',
            'time_in' => '08:00:00',
            'date_out' => '2026-09-04',
            'time_out' => null,
            'schedule_type' => 'Regular',
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = DtrChangeRequest::query()->create([
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'dtr_date' => '2026-09-04',
            'date_from' => '2026-09-04',
            'date_to' => '2026-09-04',
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'status' => DtrChangeRequest::STATUS_PENDING,
            'current_level' => 1,
            'total_levels' => 1,
            'assigned_employee_id' => $sic->id,
            'employee_visible_dtr_id' => $visibleDtr->id,
            'description' => 'Test mismatched employee target.',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The selected DTR record does not belong to the requesting employee.');

        $service->saveCorrections($request, $sic, [
            'date_in' => '2026-09-04',
            'time_in' => '08:00:00',
            'date_out' => '2026-09-04',
            'time_out' => '17:00:00',
            'schedule_type' => 'Regular',
        ]);
    }

    public function test_two_level_workflow_sic_self_request_skips_self_and_rc_becomes_final_approver(): void
    {
        [$employee, $branch, $period, $sic, $sicUser] = $this->setupEmployeeAndBranch();

        $rcUser = User::query()->create(['name' => 'Robert RC', 'role' => 'employee']);
        $rc = Employee::query()->create([
            'user_id' => $rcUser->id,
            'firstname' => 'Robert',
            'lastname' => 'RC',
            'company_id' => 'RC-001',
            'branch_id' => $branch->id,
        ]);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => 'Tagum 2-Level Workflow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'SIC',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'RC',
            'approver_employee_id' => $rc->id,
        ]);

        $sicDtr = Dtr::query()->create([
            'employee_id' => $sic->id,
            'fingerprint_id' => 'FP-SIC',
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-05',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-05',
            'time_out' => '06:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'overtime' => 60,
        ]);

        $service = app(DtrChangeRequestService::class);

        // SIC submits their own request -> skips Level 1 (SIC) and routes to RC as final approver
        $request = $service->submitFromDtr($sic, $sicDtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'explanation' => 'SIC overtime work.',
        ]);

        $this->assertSame(2, $request->total_levels);
        $this->assertSame(1, $request->current_level);
        $this->assertSame($rc->id, $request->assigned_employee_id);
        $this->assertSame('RC', $request->current_step_label);
        $this->assertFalse($request->isPendingHrApproval());
        $this->assertFalse($request->canBeReviewedBy($sic));
        $this->assertTrue($request->canBeReviewedBy($rc));

        // RC approves -> RC is the final approver, so DTR is finalized immediately
        $request = $this->approveAndCompleteHrWhenPending($service, $request, $rc, 'Approved by RC as final approver.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);
        $this->assertTrue((bool) $sicDtr->fresh()->overtime_approved);
    }

    public function test_two_level_workflow_rc_self_request_routes_directly_to_hr_as_final_approver(): void
    {
        [$employee, $branch, $period, $sic, $sicUser] = $this->setupEmployeeAndBranch();
        $hrUser = User::query()->create([
            'name' => 'HR Admin',
            'email' => 'hr@example.com',
            'role' => 'hr',
        ]);

        $rcUser = User::query()->create(['name' => 'Robert RC', 'role' => 'employee']);
        $rc = Employee::query()->create([
            'user_id' => $rcUser->id,
            'firstname' => 'Robert',
            'lastname' => 'RC',
            'company_id' => 'RC-001',
            'fingerprint_id' => 'FP-RC',
            'branch_id' => $branch->id,
        ]);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => 'Tagum 2-Level Workflow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'SIC',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'RC',
            'approver_employee_id' => $rc->id,
        ]);

        $rcDtr = Dtr::query()->create([
            'employee_id' => $rc->id,
            'fingerprint_id' => 'FP-RC',
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-06',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-06',
            'time_out' => null,
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'is_absent' => true,
        ]);

        $service = app(DtrChangeRequestService::class);

        // RC is the last configured approver in the workflow. Submitting routes directly to HR Final Approval!
        $request = $service->submitFromDtr($rc, $rcDtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'RC forgot to punch out during station visit.',
        ]);

        $this->assertSame(1, $request->total_levels);
        $this->assertSame(1, $request->current_level);
        $this->assertNull($request->assigned_employee_id);
        $this->assertTrue($request->isPendingHrApproval());
        $this->assertSame('Pending HR Final Approval', $request->display_status);
        $this->assertFalse($request->canBeReviewedBy($rc));
        $this->assertFalse($request->canBeReviewedBy($sic));

        // Because HR is Level 1 for this request, HR can edit and approve the Forgot to Punch request
        $this->assertTrue($request->canBeEditedBy($hrUser));
        $this->assertTrue($request->canBeReviewedBy($hrUser));

        $request = $service->saveCorrections($request, $hrUser, [
            'date_in' => '2026-09-06',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-06',
            'time_out' => '05:00 PM',
            'schedule_type' => 'Regular',
        ]);

        $request = $this->approveAndCompleteHrWhenPending($service, $request, $hrUser, 'Verified and approved by HR.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);
        $this->assertSame('05:00 PM', $rcDtr->fresh()->time_out);
    }

    public function test_one_level_workflow_sic_self_request_routes_directly_to_hr_as_final_approver(): void
    {
        [$employee, $branch, $period, $sic, $sicUser] = $this->setupEmployeeAndBranch();
        $hrUser = User::query()->create([
            'name' => 'HR Admin',
            'email' => 'hr@example.com',
            'role' => 'hr',
        ]);

        $sicDtr = Dtr::query()->create([
            'employee_id' => $sic->id,
            'fingerprint_id' => 'FP-SIC',
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-07',
            'time_in' => '08:00 AM',
            'date_out' => '2026-09-07',
            'time_out' => '06:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'overtime' => 60,
        ]);

        $service = app(DtrChangeRequestService::class);

        // In a 1-level workflow (Level 1: SIC), SIC submits own request -> routes directly to HR
        $request = $service->submitFromDtr($sic, $sicDtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'explanation' => 'SIC overtime in 1-level workflow.',
        ]);

        $this->assertSame(1, $request->total_levels);
        $this->assertSame(1, $request->current_level);
        $this->assertNull($request->assigned_employee_id);
        $this->assertTrue($request->isPendingHrApproval());
        $this->assertFalse($request->canBeReviewedBy($sic));
        $this->assertSame(1, DtrChangeRequest::query()->pendingForHr()->count());

        $request = $this->approveAndCompleteHrWhenPending($service, $request, $hrUser, 'Approved by HR.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);
        $this->assertTrue((bool) $sicDtr->fresh()->overtime_approved);
        $this->assertSame(0, DtrChangeRequest::query()->pendingForHr()->count());
    }

    public function test_first_and_subsequent_non_hr_approvers_can_edit_overtime_request_within_actual_limit_and_persist_to_hr_finalization(): void
    {
        [$employee, $branch, $period, $sic, $sicUser] = $this->setupEmployeeAndBranch();
        $hrUser = $this->hrReviewer();

        $rcUser = User::query()->create([
            'name' => 'Robert RC',
            'email' => 'robert.rc@example.com',
            'role' => 'employee',
        ]);
        $rc = Employee::query()->create([
            'user_id' => $rcUser->id,
            'firstname' => 'Robert',
            'lastname' => 'RC',
            'company_id' => 'RC-101',
            'branch_id' => $branch->id,
        ]);

        $amUser = User::query()->create([
            'name' => 'Alice AM',
            'email' => 'alice.am@example.com',
            'role' => 'employee',
        ]);
        $am = Employee::query()->create([
            'user_id' => $amUser->id,
            'firstname' => 'Alice',
            'lastname' => 'AreaMgr',
            'company_id' => 'AM-101',
            'branch_id' => $branch->id,
        ]);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => '3-Level OT Edit Workflow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'Station In-Charge',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'Regional Coordinator',
            'approver_employee_id' => $rc->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 3,
            'label' => 'Area Manager',
            'approver_employee_id' => $am->id,
        ]);

        $visibleDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'fingerprint_id' => '1001',
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-10',
            'time_in' => '06:30:00',
            'date_out' => '2026-09-10',
            'time_out' => '18:45:00',
            'schedule_start' => '08:00:00',
            'schedule_end' => '17:00:00',
            'schedule_type' => 'Regular',
            'work_hrs' => 675,
            'credited_work_hrs' => 480,
            'early_clock_in' => 90,
            'overtime' => 105,
            'early_clock_in_approved' => false,
            'overtime_approved' => false,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $visibleDtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
            'explanation' => 'Rendered early setup and post-shift closing overtime.',
        ]);

        $this->assertSame(1, $request->current_level);
        $this->assertSame(4, $request->total_levels);
        $this->assertTrue($request->canEditOvertimeBy($sicUser));
        $this->assertFalse($request->canEditOvertimeBy($rcUser));
        $this->assertFalse($request->canEditOvertimeBy($hrUser));

        // Level 1 SIC edits overtime (without approving yet): 90m early -> 75m, 105m OT -> 90m
        $request = $service->updateOvertimeRequest($request, $sic, [
            'approved_overtime_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
            'credited_early_minutes' => 75,
            'credited_overtime_minutes' => 90,
            'overtime_edit_remarks' => 'Capped to 75m early and 90m regular OT per shift log.',
        ]);

        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $request->status);
        $this->assertSame(1, $request->current_level);
        $this->assertSame($sic->id, $request->assigned_employee_id);
        $this->assertSame(75, $request->effectiveCreditedEarlyMinutes());
        $this->assertSame(90, $request->effectiveCreditedOvertimeMinutes());
        $this->assertStringStartsWith('Edited:', (string) $request->overtime_summary_description);
        $this->assertSame($sic->full_name, $request->proposed_values['overtime_edited_by_name'] ?? null);

        $editHistory = collect($request->approvals_history)->last();
        $this->assertSame('Overtime Edited', $editHistory['action']);
        $this->assertSame(90, $editHistory['overtime_decision']['previous_credited_early_clock_in']);
        $this->assertSame(105, $editHistory['overtime_decision']['previous_credited_overtime']);
        $this->assertSame(75, $editHistory['overtime_decision']['credited_early_clock_in']);
        $this->assertSame(90, $editHistory['overtime_decision']['credited_overtime']);

        // Official DTR must not change while still pending
        $visibleDtr->refresh();
        $this->assertFalse((bool) $visibleDtr->overtime_approved);
        $this->assertFalse((bool) $visibleDtr->early_clock_in_approved);
        $this->assertSame(0, (int) $visibleDtr->credited_overtime);

        // Level 1 SIC approves without overrides -> edited values and attribution carry to Level 2 RC
        $request = $service->approve($request, $sic, 'SIC endorses edited OT.');
        $this->assertSame(2, $request->current_level);
        $this->assertSame($rc->id, $request->assigned_employee_id);
        $this->assertSame(75, $request->effectiveCreditedEarlyMinutes());
        $this->assertSame(90, $request->effectiveCreditedOvertimeMinutes());
        $this->assertSame($sic->full_name, $request->proposed_values['overtime_edited_by_name'] ?? null);

        // Level 2 RC can edit and switches scope to Overtime Only (80m OT, 0m Early)
        $this->assertFalse($request->canEditOvertimeBy($sicUser));
        $this->assertTrue($request->canEditOvertimeBy($rcUser));

        $request = $service->updateOvertimeRequest($request, $rc, [
            'approved_overtime_scope' => DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY,
            'credited_overtime_minutes' => 80,
            'overtime_edit_remarks' => 'RC excludes early clock in and approves 80m regular OT.',
        ]);

        $this->assertSame(DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY, $request->effectiveOvertimeScope());
        $this->assertSame(0, $request->effectiveCreditedEarlyMinutes());
        $this->assertSame(80, $request->effectiveCreditedOvertimeMinutes());
        $this->assertSame($rc->id, $request->proposed_values['overtime_edited_by_id'] ?? null);

        // Level 2 RC approves without overrides -> advances to Level 3 AM
        $request = $service->approve($request, $rc, 'RC endorses 80m regular OT.');
        $this->assertSame(3, $request->current_level);
        $this->assertSame($am->id, $request->assigned_employee_id);
        $this->assertSame(DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY, $request->effectiveOvertimeScope());
        $this->assertSame(80, $request->effectiveCreditedOvertimeMinutes());

        // Level 3 AM edits back to Both (60m early, 70m OT) and approves to HR Final Approval
        $this->assertTrue($request->canEditOvertimeBy($amUser));
        $request = $service->updateOvertimeRequest($request, $am, [
            'approved_overtime_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
            'credited_early_minutes' => 60,
            'credited_overtime_minutes' => 70,
            'overtime_edit_remarks' => 'AM restores 60m early and adjusts post-shift OT to 70m.',
        ]);

        $request = $service->approve($request, $am, 'AM endorses 60m early + 70m OT.');
        $this->assertSame(4, $request->current_level);
        $this->assertTrue($request->isPendingHrApproval());
        $this->assertFalse($request->canEditOvertimeBy($hrUser));

        // HR cannot use edit-overtime endpoint even at HR stage
        try {
            $service->updateOvertimeRequest($request, $hrUser, [
                'approved_overtime_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
                'credited_early_minutes' => 60,
                'credited_overtime_minutes' => 70,
            ]);
            $this->fail('HR must not be allowed to use non-HR edit-overtime service.');
        } catch (AuthorizationException $e) {
            $this->assertTrue(true);
        }

        // HR approves without overrides -> applies AM's edited values (60m early + 70m OT = 130m credited)
        $request = $service->approve($request, $hrUser, 'Approved by HR.');
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $request->status);

        $visibleDtr->refresh();
        $this->assertTrue((bool) $visibleDtr->early_clock_in_approved);
        $this->assertTrue((bool) $visibleDtr->overtime_approved);
        $this->assertSame('Approved', $visibleDtr->overtime_status);
        $this->assertSame(60, (int) $visibleDtr->credited_early_clock_in);
        $this->assertSame(130, (int) $visibleDtr->credited_overtime);
        $this->assertSame(610, (int) $visibleDtr->credited_work_hrs);
    }

    public function test_overtime_edit_and_approval_enforce_actual_overtime_cap_and_minimum_threshold(): void
    {
        [$employee, $branch, $period, $sic] = $this->setupEmployeeAndBranch();

        $visibleDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'fingerprint_id' => '1001',
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-11',
            'time_in' => '04:15:00',
            'date_out' => '2026-09-11',
            'time_out' => '13:45:00',
            'schedule_start' => '04:30:00',
            'schedule_end' => '12:30:00',
            'schedule_type' => 'Shift1',
            'early_clock_in' => 14,
            'overtime' => 75,
        ]);

        $service = app(DtrChangeRequestService::class);
        $request = $service->submitFromDtr($employee, $visibleDtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY,
            'explanation' => '75 mins regular OT.',
        ]);

        // 1. Reject minutes exceeding actual overtime (76 > 75)
        try {
            $service->updateOvertimeRequest($request, $sic, [
                'credited_overtime_minutes' => 76,
            ]);
            $this->fail('Editing overtime above actual D.T.R overtime must fail.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('cannot exceed the actual 75 minutes', collect($e->errors())->flatten()->first());
        }

        // 2. Same actual cap must hold during approve()
        try {
            $service->approve($request, $sic, 'Trying to over-credit.', [
                'credited_overtime_minutes' => 76,
            ]);
            $this->fail('Approving overtime above actual D.T.R overtime must fail.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('cannot exceed the actual 75 minutes', collect($e->errors())->flatten()->first());
        }

        // 3. Reject below 60-minute policy minimum (59 < 60)
        try {
            $service->updateOvertimeRequest($request, $sic, [
                'credited_overtime_minutes' => 59,
            ]);
            $this->fail('Editing overtime below 60 minutes must fail.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('at least 60 minutes', collect($e->errors())->flatten()->first());
        }

        // 4. Reject non-integer values
        try {
            $service->updateOvertimeRequest($request, $sic, [
                'credited_overtime_minutes' => '65.5',
            ]);
            $this->fail('Decimal overtime minutes must fail.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('whole number', collect($e->errors())->flatten()->first());
        }

        // 5. Reject switching scope on an overtime_only request
        try {
            $service->updateOvertimeRequest($request, $sic, [
                'approved_overtime_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
                'credited_overtime_minutes' => 70,
            ]);
            $this->fail('Switching scope on Overtime Only request must fail.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('regular after-schedule overtime', collect($e->errors())->flatten()->first());
        }

        // 6. Exact boundaries (60 and 75) succeed
        $request = $service->updateOvertimeRequest($request, $sic, [
            'credited_overtime_minutes' => 60,
        ]);
        $this->assertSame(60, $request->effectiveCreditedOvertimeMinutes());

        $request = $service->updateOvertimeRequest($request, $sic, [
            'credited_overtime_minutes' => 75,
        ]);
        $this->assertSame(75, $request->effectiveCreditedOvertimeMinutes());
    }

    public function test_overtime_edit_authorization_blocks_hr_wrong_level_self_locked_and_non_overtime_requests(): void
    {
        [$employee, $branch, $period, $sic, $sicUser] = $this->setupEmployeeAndBranch();
        $hrUser = $this->hrReviewer();

        $rcUser = User::query()->create(['name' => 'Rachel RC', 'role' => 'employee']);
        $rc = Employee::query()->create([
            'user_id' => $rcUser->id,
            'firstname' => 'Rachel',
            'lastname' => 'RC',
            'company_id' => 'RC-201',
            'branch_id' => $branch->id,
        ]);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => '2-Level OT Auth Test',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'SIC',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'RC',
            'approver_employee_id' => $rc->id,
        ]);

        $visibleDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'fingerprint_id' => '1001',
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-12',
            'time_in' => '08:00:00',
            'date_out' => '2026-09-12',
            'time_out' => '18:00:00',
            'schedule_start' => '08:00:00',
            'schedule_end' => '17:00:00',
            'schedule_type' => 'Regular',
            'overtime' => 60,
        ]);

        $service = app(DtrChangeRequestService::class);
        $otRequest = $service->submitFromDtr($employee, $visibleDtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY,
            'explanation' => 'One hour regular overtime.',
        ]);

        // Blocked: HR at Level 1, higher-level approver at Level 1, and requesting employee
        $this->assertFalse($otRequest->canEditOvertimeBy($hrUser));
        $this->assertFalse($otRequest->canEditOvertimeBy($rcUser));
        $this->assertFalse($otRequest->canEditOvertimeBy($employee));

        // Blocked: disabled approver account
        $sicUser->update(['is_disabled' => true]);
        $this->assertFalse($otRequest->fresh()->canEditOvertimeBy($sicUser->fresh()));
        $sicUser->update(['is_disabled' => false]);

        // Blocked: locked target DTR record
        $visibleDtr->update(['is_locked' => true]);
        $this->assertFalse($otRequest->fresh()->canEditOvertimeBy($sicUser->fresh()));
        $visibleDtr->update(['is_locked' => false]);

        // Blocked: locked payroll period
        $period->update(['is_locked' => true]);
        $this->assertFalse($otRequest->fresh()->canEditOvertimeBy($sicUser->fresh()));
        $period->update(['is_locked' => false]);

        // Non-overtime Forgot To Punch request is not editable via updateOvertimeRequest
        $forgotDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'fingerprint_id' => '1001',
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-13',
            'time_in' => '08:00:00',
            'date_out' => '2026-09-13',
            'time_out' => null,
            'schedule_type' => 'Regular',
        ]);
        $forgotRequest = $service->submitFromDtr($employee, $forgotDtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Forgot to punch out.',
        ]);

        $this->assertFalse($forgotRequest->canEditOvertimeBy($sicUser));

        try {
            $service->updateOvertimeRequest($forgotRequest, $sic, [
                'credited_overtime_minutes' => 60,
            ]);
            $this->fail('updateOvertimeRequest must reject non-overtime requests.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Overtime Approval', collect($e->errors())->flatten()->first());
        }
    }

    public function test_station_review_and_hr_livewire_modals_edit_overtime_and_enforce_cap(): void
    {
        [$employee, $branch, $period, $sic, $sicUser] = $this->setupEmployeeAndBranch();
        $period->update([
            'date_start' => now()->subDays(5)->toDateString(),
            'date_end' => now()->addDays(10)->toDateString(),
            'date_payout' => now()->addDays(15)->toDateString(),
        ]);
        $hrUser = $this->hrReviewer();

        $rcUser = User::query()->create([
            'name' => 'Robert RC',
            'email' => 'robert.rc@example.com',
            'role' => 'employee',
        ]);
        $rc = Employee::query()->create([
            'user_id' => $rcUser->id,
            'firstname' => 'Robert',
            'lastname' => 'RC',
            'company_id' => 'RC-301',
            'branch_id' => $branch->id,
        ]);

        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => '2-Level Livewire OT Edit Workflow',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'SIC TAGUM',
            'approver_employee_id' => $sic->id,
        ]);
        LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'RC TAGUM',
            'approver_employee_id' => $rc->id,
        ]);

        // Overtime Only (75 mins actual) + Forgot To Punch request in the same table
        $otOnlyDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'fingerprint_id' => '1001',
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-11',
            'time_in' => '04:15:00',
            'date_out' => '2026-09-11',
            'time_out' => '13:45:00',
            'schedule_start' => '04:30:00',
            'schedule_end' => '12:30:00',
            'schedule_type' => 'Shift1',
            'early_clock_in' => 14,
            'overtime' => 75,
        ]);
        $forgotDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'fingerprint_id' => '1001',
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-12',
            'time_in' => '08:00:00',
            'date_out' => '2026-09-12',
            'time_out' => null,
            'schedule_start' => '08:00:00',
            'schedule_end' => '17:00:00',
            'schedule_type' => 'Regular',
        ]);
        $bothOtDtr = EmployeeVisibleDtr::query()->create([
            'employee_id' => $employee->id,
            'fingerprint_id' => '1001',
            'branch_id' => $branch->id,
            'payroll_period_id' => $period->id,
            'date_in' => '2026-09-14',
            'time_in' => '06:45:00',
            'date_out' => '2026-09-14',
            'time_out' => '18:30:00',
            'schedule_start' => '08:00:00',
            'schedule_end' => '17:00:00',
            'schedule_type' => 'Regular',
            'work_hrs' => 645,
            'credited_work_hrs' => 480,
            'early_clock_in' => 75,
            'overtime' => 90,
        ]);

        $service = app(DtrChangeRequestService::class);
        $otRequest = $service->submitFromDtr($employee, $otOnlyDtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY,
            'explanation' => 'After-shift closing tasks.',
        ]);
        $forgotRequest = $service->submitFromDtr($employee, $forgotDtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Forgot afternoon punch.',
        ]);
        $bothRequest = $service->submitFromDtr($employee, $bothOtDtr, [
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'overtime_request_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
            'explanation' => 'Early inventory + post-shift closing.',
        ]);

        // Level 1 SIC opens Station Change Requests page
        Filament::setCurrentPanel(Filament::getPanel('employee'));
        $this->actingAs($sicUser);

        $sicPage = Livewire::actingAs($sicUser)
            ->test(ReviewDtrChangeRequests::class)
            ->assertSuccessful()
            ->assertTableActionVisible('editOvertime', $otRequest)
            ->assertTableActionVisible('editOvertime', $bothRequest)
            ->assertTableActionHidden('editOvertime', $forgotRequest);

        // Attempt to exceed 75m actual OT via Edit Overtime modal -> fails validation
        $sicPage
            ->mountTableAction('editOvertime', $otRequest)
            ->fillForm([
                'credited_overtime_minutes' => 80,
            ])
            ->callMountedTableAction()
            ->assertHasTableActionErrors(['credited_overtime_minutes']);

        $this->assertSame(75, $otRequest->fresh()->effectiveCreditedOvertimeMinutes());

        // Valid Edit Overtime (65m of 75m) saves cleanly and keeps status Pending
        $sicPage
            ->fillForm([
                'credited_overtime_minutes' => 65,
                'overtime_edit_remarks' => 'Verified 65m OT only.',
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors()
            ->assertNotified('Overtime request updated')
            ->assertSeeText('Edited: 65m (1.1h) (of 75m actual) Overtime');

        $otRequest->refresh();
        $this->assertSame(DtrChangeRequest::STATUS_PENDING, $otRequest->status);
        $this->assertSame(65, $otRequest->effectiveCreditedOvertimeMinutes());

        // View Details modal exposes Edit Overtime button and shows edited values
        $sicPage
            ->mountTableAction('view', $otRequest)
            ->assertMountedActionModalSee([
                'Edit Overtime',
                'Edited Request: 65 mins (of 75m actual)',
                'Actual D.T.R Limit (Max: 75 mins)',
            ])
            ->unmountTableAction();

        // Clicking Edit Overtime from inside View Details modal also updates and closes modals cleanly
        $sicPage
            ->mountTableAction(['view', 'editOvertimeFromDetails'], $otRequest)
            ->assertTableActionDataSet([
                'credited_overtime_minutes' => 65,
            ])
            ->fillForm([
                'credited_overtime_minutes' => 64,
                'overtime_edit_remarks' => 'Adjusted from View Details modal.',
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors()
            ->assertNotified('Overtime request updated')
            ->assertSet('mountedActions', []);

        $this->assertSame(64, $otRequest->fresh()->effectiveCreditedOvertimeMinutes());

        // Approve modal on $otRequest pre-fills edited 64m default
        $sicPage
            ->mountTableAction('approve', $otRequest)
            ->assertTableActionDataSet([
                'credited_overtime_minutes' => 64,
            ])
            ->unmountTableAction();

        // Level 1 SIC approves $bothRequest choosing Overtime Only (70m) -> advances to Level 2 RC
        $sicPage
            ->mountTableAction('approve', $bothRequest)
            ->fillForm([
                'approved_overtime_scope' => DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY,
                'credited_overtime_minutes' => 70,
                'reviewer_remarks' => 'SIC endorses 70m regular OT.',
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors()
            ->assertNotified('D.T.R change request approved');

        $bothRequest->refresh();
        $this->assertSame(2, $bothRequest->current_level);
        $this->assertSame($rc->id, $bothRequest->assigned_employee_id);
        $this->assertSame(DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY, $bothRequest->effectiveOvertimeScope());

        // Level 2 RC opens Station Change Requests, switches scope back to Both in editOvertime modal
        $rcPage = Livewire::actingAs($rcUser)
            ->test(ReviewDtrChangeRequests::class)
            ->assertSuccessful()
            ->assertTableActionVisible('editOvertime', $bothRequest)
            ->mountTableAction('editOvertime', $bothRequest)
            ->assertTableActionDataSet([
                'approved_overtime_scope' => DtrChangeRequest::OT_SCOPE_OVERTIME_ONLY,
                'credited_overtime_minutes' => 70,
            ])
            ->set('mountedActions.0.data.approved_overtime_scope', DtrChangeRequest::OT_SCOPE_BOTH);

        // Switching scope to Both restores early clock in default to actual (75m) instead of 0
        $this->assertSame(75, (int) $rcPage->get('mountedActions.0.data.credited_early_minutes'));
        $this->assertSame(70, (int) $rcPage->get('mountedActions.0.data.credited_overtime_minutes'));

        // RC edits to 60m early & 70m OT, saves, then approves to HR
        $rcPage
            ->fillForm([
                'approved_overtime_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
                'credited_early_minutes' => 60,
                'credited_overtime_minutes' => 70,
                'overtime_edit_remarks' => 'RC endorses 60m early + 70m OT.',
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors()
            ->assertNotified('Overtime request updated')
            ->mountTableAction('approve', $bothRequest)
            ->fillForm([
                'approved_overtime_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
                'credited_early_minutes' => 60,
                'credited_overtime_minutes' => 70,
                'reviewer_remarks' => 'Forward to HR.',
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors()
            ->assertNotified('D.T.R change request approved');

        $bothRequest->refresh();
        $this->assertTrue($bothRequest->isPendingHrApproval());

        // HR opens HR D.T.R Change Requests page: no editOvertime action, edited defaults pre-filled in Approve
        Filament::setCurrentPanel(Filament::getPanel('hr'));
        $hrPage = Livewire::actingAs($hrUser)
            ->test(HrDtrChangeRequests::class)
            ->assertSuccessful()
            ->assertTableActionDoesNotExist('editOvertime', record: $bothRequest)
            ->mountTableAction('approve', $bothRequest)
            ->assertTableActionDataSet([
                'approved_overtime_scope' => DtrChangeRequest::OT_SCOPE_BOTH,
                'credited_early_minutes' => 60,
                'credited_overtime_minutes' => 70,
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors()
            ->assertNotified('D.T.R change request finalized & approved');

        $bothRequest->refresh();
        $this->assertSame(DtrChangeRequest::STATUS_APPROVED, $bothRequest->status);

        $bothOtDtr->refresh();
        $this->assertTrue((bool) $bothOtDtr->early_clock_in_approved);
        $this->assertTrue((bool) $bothOtDtr->overtime_approved);
        $this->assertSame(60, (int) $bothOtDtr->credited_early_clock_in);
        $this->assertSame(130, (int) $bothOtDtr->credited_overtime);
        $this->assertSame(610, (int) $bothOtDtr->credited_work_hrs);
    }
}
