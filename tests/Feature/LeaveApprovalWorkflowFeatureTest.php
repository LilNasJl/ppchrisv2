<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Counter;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveApprovalEvent;
use App\Models\LeaveApprovalLevel;
use App\Models\LeaveApprovalWorkflow;
use App\Models\LeaveRequestApproval;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\LeaveApprovalAccess;
use App\Services\LeaveApprovalService;
use App\Services\LeaveDtrService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveApprovalWorkflowFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTables();

        Gate::define('Review:Leave', fn (User $user) => in_array($user->role, ['hr', 'admin'], true));
        Gate::define('Manage:LeaveWorkflow', fn (User $user) => in_array($user->role, ['hr', 'admin'], true));
        Gate::define('Override:Leave', fn (User $user) => in_array($user->role, ['hr', 'admin'], true));
        Gate::define('View:Leave', fn (User $user) => in_array($user->role, ['hr', 'admin'], true));
        Gate::define('Update:Leave', fn (User $user) => in_array($user->role, ['hr', 'admin'], true));
    }

    protected function createTestTables(): void
    {
        Schema::dropAllTables();

        Schema::create('counters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('uid')->default(0);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->default('secret');
            $table->string('role', 30)->default('employee');
            $table->boolean('is_disabled')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('branch_name');
            $table->time('reg_sched_start')->nullable();
            $table->time('reg_sched_end')->nullable();
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

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('uid')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('company_id', 20)->nullable();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('middlename')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->foreignId('designation_id')->nullable();
            $table->double('leave_credits')->default(5);
            $table->double('birthday_leave_credits')->default(1);
            $table->unsignedInteger('leave_credits_year')->nullable();
            $table->double('allowance')->default(0);
            $table->double('salary_adjustment')->default(0);
            $table->unsignedInteger('kids')->default(0);
            $table->string('schedule_type')->default('regular');
            $table->string('employment_status')->default('Regular');
            $table->string('rate_type')->default('Monthly');
            $table->date('end_status_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->date('date_start');
            $table->date('date_end');
            $table->boolean('is_locked')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('leave_approval_workflows', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            foreach (['employee', 'branch', 'department', 'designation'] as $scope) {
                $table->unsignedBigInteger($scope.'_id')->nullable()->index();
            }
            $table->string('active_scope_key')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('leave_approval_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_id')->constrained('leave_approval_workflows')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('label', 100);
            $table->unsignedBigInteger('approver_employee_id')->index();
            $table->unsignedBigInteger('alternate_employee_id')->nullable();
            $table->unique(['workflow_id', 'sequence']);
        });

        Schema::create('leave_workflow_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_id')->constrained('leave_approval_workflows')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->json('configuration');
            $table->timestamps();
            $table->unique(['workflow_id', 'version']);
        });

        Schema::create('leaves', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('leave_type');
            $table->date('leave_from');
            $table->date('leave_to');
            $table->string('reason');
            $table->string('status')->default('Pending');
            $table->decimal('deducted_leave_credits', 5, 2)->default(0);
            $table->decimal('deducted_birthday_leave_credits', 5, 2)->default(0);
            $table->boolean('is_half_day')->default(false);
            $table->string('half_day_period')->nullable();
            $table->string('half_day_schedule')->nullable();
            $table->text('hr_comment')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->foreignId('approval_workflow_id')->nullable();
            $table->unsignedInteger('approval_workflow_version')->nullable();
            $table->json('approval_snapshot')->nullable();
            $table->string('approval_phase', 30)->nullable()->index();
            $table->unsignedInteger('current_approval_order')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('leave_request_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('leave_id')->constrained('leaves')->restrictOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('label', 100);
            $table->boolean('is_hr')->default(false);
            $table->unsignedBigInteger('approver_employee_id')->nullable()->index();
            $table->string('approver_name')->nullable();
            $table->string('status', 30)->default('Waiting');
            $table->unsignedBigInteger('acted_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();
            $table->unique(['leave_id', 'sequence']);
            $table->index(['approver_employee_id', 'status']);
        });

        Schema::create('leave_approval_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('leave_id')->constrained('leaves')->restrictOnDelete();
            $table->unsignedBigInteger('step_id')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name');
            $table->string('action', 60);
            $table->text('remarks')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_approval_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('event_id')->constrained('leave_approval_events')->restrictOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->json('payload');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
        });

        Schema::create('dtrs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('payroll_period_id')->nullable();
            $table->unsignedBigInteger('leave_id')->nullable();
            $table->string('fingerprint_id')->nullable();
            $table->string('batch_id')->nullable();
            $table->date('date_in')->nullable();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();
            $table->time('schedule_start')->nullable();
            $table->time('schedule_end')->nullable();
            $table->string('schedule_type')->default('Regular');
            $table->string('day_part')->nullable();
            $table->string('entry_source')->nullable();
            $table->double('late')->default(0);
            $table->double('undertime')->default(0);
            $table->double('overtime')->default(0);
            $table->double('early_clock_in')->default(0);
            $table->double('credited_early_clock_in')->default(0);
            $table->double('credited_overtime')->default(0);
            $table->double('work_hrs')->default(0);
            $table->double('credited_work_hrs')->default(0);
            $table->string('overtime_status')->nullable();
            $table->boolean('early_clock_in_approved')->default(false);
            $table->boolean('overtime_approved')->default(false);
            $table->boolean('is_holiday')->default(false);
            $table->unsignedBigInteger('holiday_id')->nullable();
            $table->string('holiday_type')->nullable();
            $table->double('holiday_rate')->nullable();
            $table->double('daily_rate')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_absent')->default(false);
            $table->double('absence_minutes')->default(0);
            $table->boolean('is_imported')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('employee_visible_dtrs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('payroll_calculation_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->nullable();
            $table->unsignedBigInteger('payroll_period_id')->nullable();
            $table->decimal('regular_work_days_per_month', 8, 2)->default(26);
            $table->decimal('regular_half_month_days', 8, 2)->default(13);
            $table->decimal('work_hours_per_day', 8, 2)->default(8);
            $table->integer('late_grace_minutes')->default(2);
            $table->decimal('half_day_work_day_value', 8, 2)->default(0.5);
            $table->decimal('overtime_rate_multiplier', 8, 2)->default(1);
            $table->decimal('regular_holiday_rate', 8, 2)->default(200);
            $table->decimal('special_holiday_rate', 8, 2)->default(30);
            $table->decimal('holiday_overtime_premium_rate', 8, 2)->default(30);
            $table->boolean('unworked_regular_holiday_pay_enabled')->default(true);
            $table->timestamps();
        });

        // Seed Company default direct-HR workflow
        LeaveApprovalWorkflow::create([
            'name' => 'Company default - HR review',
            'is_active' => true,
            'version' => 1,
            'active_scope_key' => '0:0:0:0',
        ]);
    }

    protected function createEmployeeWithUser(string $name, string $role = 'employee', ?Branch $branch = null): array
    {
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)).rand(100, 999).'@example.com',
            'role' => $role,
            'is_disabled' => false,
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'company_id' => 'EMP'.rand(1000, 9999),
            'firstname' => explode(' ', $name)[0],
            'lastname' => explode(' ', $name)[1] ?? 'User',
            'branch_id' => $branch?->id,
            'leave_credits' => 5,
            'birthday_leave_credits' => 1,
        ]);

        return [$user, $employee];
    }

    public function test_default_workflow_submits_directly_to_hr_and_approves_with_dtr(): void
    {
        $branch = Branch::create(['branch_name' => 'Main Branch', 'reg_sched_start' => '08:00:00', 'reg_sched_end' => '17:00:00']);
        [$empUser, $employee] = $this->createEmployeeWithUser('John Doe', 'employee', $branch);
        [$hrUser, $hrEmployee] = $this->createEmployeeWithUser('HR Admin', 'hr', $branch);

        $period = PayrollPeriod::create([
            'title' => 'Sep 01 - Sep 15, 2026',
            'date_start' => '2026-09-01',
            'date_end' => '2026-09-15',
            'is_locked' => false,
        ]);

        $service = app(LeaveApprovalService::class);

        $leave = $service->submit($employee, [
            'leave_type' => 'Vacation Leave',
            'leave_from' => '2026-09-05',
            'leave_to' => '2026-09-06',
            'reason' => 'Family vacation',
        ], $empUser);

        $this->assertSame('Pending', $leave->status);
        $this->assertSame('hr', $leave->approval_phase);
        $this->assertTrue($leave->isReadyForHr());
        $this->assertSame('Pending - HR', $leave->approval_label);

        // Steps should have only 1 step (HR)
        $this->assertCount(1, $leave->approvalSteps);
        $this->assertTrue($leave->approvalSteps->first()->is_hr);
        $this->assertSame('Pending', $leave->approvalSteps->first()->status);

        // HR approves
        $service->decideHr($leave, $hrUser, true, $period, 'Approved by HR');

        $leave->refresh();
        $this->assertSame('Approved', $leave->status);
        $this->assertSame('complete', $leave->approval_phase);

        // Employee credits should be deducted (2 days)
        $employee->refresh();
        $this->assertEquals(3, $employee->leave_credits);

        // DTR records must be created
        $this->assertDatabaseCount('dtrs', 2);
        $this->assertDatabaseHas('dtrs', [
            'leave_id' => $leave->id,
            'branch_id' => $employee->branch_id,
            'date_in' => '2026-09-05',
            'schedule_type' => 'Leave',
        ]);
    }

    public function test_multi_level_workflow_progresses_sequentially_sic_to_rc_to_hr(): void
    {
        $branch = Branch::create(['branch_name' => 'Tagum Station', 'reg_sched_start' => '08:00:00', 'reg_sched_end' => '17:00:00']);
        [$attendantUser, $attendant] = $this->createEmployeeWithUser('Attendant User', 'employee', $branch);
        [$sicUser, $sic] = $this->createEmployeeWithUser('Station SIC', 'employee', $branch);
        [$rcUser, $rc] = $this->createEmployeeWithUser('Station RC', 'employee', $branch);
        [$hrUser, $hr] = $this->createEmployeeWithUser('HR Officer', 'hr', $branch);

        $period = PayrollPeriod::create([
            'title' => 'Sep 01 - Sep 15, 2026',
            'date_start' => '2026-09-01',
            'date_end' => '2026-09-15',
            'is_locked' => false,
        ]);

        // Configure Station Multi-Level Workflow: Level 1 = SIC, Level 2 = RC, Final = HR
        $workflow = LeaveApprovalWorkflow::create([
            'name' => 'Tagum Station Flow',
            'is_active' => true,
            'version' => 1,
            'branch_id' => $branch->id,
            'active_scope_key' => "0:{$branch->id}:0:0",
        ]);
        $workflow->levels()->create(['sequence' => 1, 'label' => 'SIC', 'approver_employee_id' => $sic->id]);
        $workflow->levels()->create(['sequence' => 2, 'label' => 'RC', 'approver_employee_id' => $rc->id]);

        $service = app(LeaveApprovalService::class);

        // 1. Employee submits
        $leave = $service->submit($attendant, [
            'leave_type' => 'Sick Leave',
            'leave_from' => '2026-09-08',
            'leave_to' => '2026-09-08',
            'reason' => 'Fever and rest',
        ], $attendantUser);

        $this->assertSame('Pending', $leave->status);
        $this->assertSame('preliminary', $leave->approval_phase);
        $this->assertFalse($leave->isReadyForHr());
        $this->assertSame('Pending - SIC', $leave->approval_label);

        $steps = $leave->approvalSteps;
        $this->assertCount(3, $steps); // SIC, RC, HR
        $this->assertSame('Pending', $steps[0]->status); // Level 1 (SIC) active
        $this->assertSame('Waiting', $steps[1]->status); // Level 2 (RC) waiting
        $this->assertSame('Waiting', $steps[2]->status); // Level 3 (HR) waiting

        // HR attempt to approve early must fail
        try {
            $service->decideHr($leave, $hrUser, true, $period, 'Premature HR approval');
            $this->fail('Expected exception for premature HR approval');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('All preliminary levels must be completed', $e->getMessage());
        }

        // 2. Level 1 (SIC) approves
        $service->decide($leave, $steps[0]->id, $sicUser, true, 'SIC approved');

        $leave->refresh();
        $this->assertSame('preliminary', $leave->approval_phase);
        $this->assertSame('Pending - RC', $leave->approval_label);
        $this->assertFalse($leave->isReadyForHr());

        $step1 = $leave->approvalSteps()->where('sequence', 1)->first();
        $step2 = $leave->approvalSteps()->where('sequence', 2)->first();
        $this->assertSame('Approved', $step1->status);
        $this->assertSame('Pending', $step2->status); // RC is now active

        // Attendant's leave credits must still be untouched!
        $attendant->refresh();
        $this->assertEquals(5, $attendant->leave_credits);
        $this->assertDatabaseCount('dtrs', 0);

        // 3. Level 2 (RC) approves
        $service->decide($leave, $step2->id, $rcUser, true, 'RC approved');

        $leave->refresh();
        $this->assertSame('hr', $leave->approval_phase);
        $this->assertSame('Pending - HR', $leave->approval_label);
        $this->assertTrue($leave->isReadyForHr());

        $step3 = $leave->approvalSteps()->where('sequence', 3)->first();
        $this->assertSame('Approved', $step2->fresh()->status);
        $this->assertSame('Pending', $step3->status); // HR is now active

        // 4. Final HR approval
        $service->decideHr($leave, $hrUser, true, $period, 'Final HR Approval granted');

        $leave->refresh();
        $this->assertSame('Approved', $leave->status);
        $this->assertSame('complete', $leave->approval_phase);

        // Credits deducted now
        $attendant->refresh();
        $this->assertEquals(4, $attendant->leave_credits);

        // DTR row created
        $this->assertDatabaseCount('dtrs', 1);
        $this->assertDatabaseHas('dtrs', [
            'leave_id' => $leave->id,
            'branch_id' => $attendant->branch_id,
            'date_in' => '2026-09-08',
            'schedule_type' => 'Leave',
        ]);
    }

    public function test_rejection_at_intermediate_level_halts_approval_chain(): void
    {
        $branch = Branch::create(['branch_name' => 'Accounting Branch']);
        [$empUser, $employee] = $this->createEmployeeWithUser('Staff One', 'employee', $branch);
        [$headUser, $head] = $this->createEmployeeWithUser('Dept Head', 'employee', $branch);

        $workflow = LeaveApprovalWorkflow::create([
            'name' => 'Accounting Flow',
            'is_active' => true,
            'version' => 1,
            'branch_id' => $branch->id,
            'active_scope_key' => "0:{$branch->id}:0:0",
        ]);
        $workflow->levels()->create(['sequence' => 1, 'label' => 'Department Head', 'approver_employee_id' => $head->id]);

        $service = app(LeaveApprovalService::class);
        $leave = $service->submit($employee, [
            'leave_type' => 'Vacation Leave',
            'leave_from' => '2026-09-10',
            'leave_to' => '2026-09-11',
            'reason' => 'Vacation',
        ], $empUser);

        $step1 = $leave->approvalSteps->first();

        // Department head rejects
        $service->decide($leave, $step1->id, $headUser, false, 'Insufficient staff during month-end');

        $leave->refresh();
        $this->assertSame('Rejected', $leave->status);
        $this->assertSame('complete', $leave->approval_phase);

        // HR step cancelled
        $hrStep = $leave->approvalSteps()->where('is_hr', true)->first();
        $this->assertSame('Cancelled', $hrStep->status);

        // Credits untouched, no DTR
        $employee->refresh();
        $this->assertEquals(5, $employee->leave_credits);
        $this->assertDatabaseCount('dtrs', 0);
    }

    public function test_employee_can_cancel_pending_leave_request(): void
    {
        $branch = Branch::create(['branch_name' => 'General Branch']);
        [$empUser, $employee] = $this->createEmployeeWithUser('Worker Bob', 'employee', $branch);

        $service = app(LeaveApprovalService::class);
        $leave = $service->submit($employee, [
            'leave_type' => 'Vacation Leave',
            'leave_from' => '2026-09-12',
            'leave_to' => '2026-09-12',
            'reason' => 'Personal matter',
        ], $empUser);

        $this->assertSame('Pending', $leave->status);

        // Cancel
        $service->cancel($leave, $empUser);

        $leave->refresh();
        $this->assertSame('Cancelled', $leave->status);
        $this->assertSame('Cancelled', $leave->approvalSteps->first()->status);

        // Credits untouched
        $employee->refresh();
        $this->assertEquals(5, $employee->leave_credits);
    }

    public function test_hr_can_reassign_approver_and_skip_level(): void
    {
        $branch = Branch::create(['branch_name' => 'Branch Alpha']);
        [$empUser, $employee] = $this->createEmployeeWithUser('Worker Alice', 'employee', $branch);
        [$origUser, $origApprover] = $this->createEmployeeWithUser('Old Approver', 'employee', $branch);
        [$newUser, $newApprover] = $this->createEmployeeWithUser('New Approver', 'employee', $branch);
        [$hrUser, $hr] = $this->createEmployeeWithUser('Admin HR', 'admin', $branch);

        $workflow = LeaveApprovalWorkflow::create([
            'name' => 'Alpha Flow',
            'is_active' => true,
            'version' => 1,
            'branch_id' => $branch->id,
            'active_scope_key' => "0:{$branch->id}:0:0",
        ]);
        $workflow->levels()->create(['sequence' => 1, 'label' => 'Level 1', 'approver_employee_id' => $origApprover->id]);

        $service = app(LeaveApprovalService::class);
        $leave = $service->submit($employee, [
            'leave_type' => 'Emergency Leave',
            'leave_from' => '2026-09-15',
            'leave_to' => '2026-09-15',
            'reason' => 'Emergency',
        ], $empUser);

        $step1 = $leave->approvalSteps->first();

        // 1. Reassign approver
        $service->reassign($leave, $step1->id, $newApprover, $hrUser, 'Old approver on leave');

        $step1->refresh();
        $this->assertSame($newApprover->id, $step1->approver_employee_id);
        $this->assertSame($newApprover->full_name, $step1->approver_name);

        // Audit event recorded
        $this->assertDatabaseHas('leave_approval_events', [
            'leave_id' => $leave->id,
            'action' => 'Approver reassigned',
            'remarks' => 'Old approver on leave',
        ]);

        // 2. Skip level
        $service->skip($leave, $step1->id, $hrUser, 'Skipped due to urgent travel schedule');

        $step1->refresh();
        $this->assertSame('Skipped', $step1->status);

        $leave->refresh();
        $this->assertSame('hr', $leave->approval_phase);
        $this->assertTrue($leave->isReadyForHr());
    }

    public function test_self_approval_falls_back_to_alternate_approver(): void
    {
        $branch = Branch::create(['branch_name' => 'Branch Beta']);
        [$mgrUser, $manager] = $this->createEmployeeWithUser('Manager John', 'employee', $branch);
        [$asstUser, $assistant] = $this->createEmployeeWithUser('Assistant Mary', 'employee', $branch);

        $workflow = LeaveApprovalWorkflow::create([
            'name' => 'Beta Flow',
            'is_active' => true,
            'version' => 1,
            'branch_id' => $branch->id,
            'active_scope_key' => "0:{$branch->id}:0:0",
        ]);
        $workflow->levels()->create([
            'sequence' => 1,
            'label' => 'Manager Review',
            'approver_employee_id' => $manager->id, // Requester is the manager!
            'alternate_employee_id' => $assistant->id, // Alternate
        ]);

        $service = app(LeaveApprovalService::class);

        // Manager submits leave
        $leave = $service->submit($manager, [
            'leave_type' => 'Vacation Leave',
            'leave_from' => '2026-09-20',
            'leave_to' => '2026-09-20',
            'reason' => 'Annual leave',
        ], $mgrUser);

        $step1 = $leave->approvalSteps->first();

        // Step 1 must have used the alternate (Assistant Mary) to avoid self-approval!
        $this->assertSame($assistant->id, $step1->approver_employee_id);
        $this->assertSame($assistant->full_name, $step1->approver_name);

        // Manager cannot approve their own step
        try {
            $service->decide($leave, $step1->id, $mgrUser, true, 'Trying to self approve');
            $this->fail('Expected 403 when manager attempts to approve own request');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // Assistant can approve
        $service->decide($leave, $step1->id, $asstUser, true, 'Assistant approved');
        $this->assertSame('Approved', $step1->fresh()->status);
    }
}
