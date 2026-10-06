<?php

namespace Tests\Feature;

use App\Filament\Employee\Pages\Station\ReviewDtrChangeRequests;
use App\Filament\Employee\Pages\Station\StationChangeRequestHistory;
use App\Models\Branch;
use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class StationChangeRequestHistoryFeatureTest extends TestCase
{
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
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('designations', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('dtrs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('employee_visible_dtrs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->boolean('is_locked')->default(false);
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

        Schema::create('dtr_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('payroll_period_id')->nullable();
            $table->unsignedBigInteger('dtr_id')->nullable();
            $table->unsignedBigInteger('employee_visible_dtr_id')->nullable();
            $table->date('dtr_date')->nullable();
            $table->unsignedBigInteger('assigned_employee_id')->nullable();
            $table->unsignedBigInteger('reviewed_by_employee_id')->nullable();
            $table->string('employee_name_snapshot')->nullable();
            $table->string('employee_company_id_snapshot')->nullable();
            $table->string('branch_name_snapshot')->nullable();
            $table->string('payroll_period_title_snapshot')->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->string('request_type');
            $table->text('description')->nullable();
            $table->string('attachment_path', 2048)->nullable();
            $table->string('attachment_original_name', 255)->nullable();
            $table->string('status')->default(DtrChangeRequest::STATUS_PENDING);
            $table->unsignedInteger('current_level')->default(1);
            $table->unsignedInteger('total_levels')->default(1);
            $table->string('current_step_label')->nullable();
            $table->json('workflow_snapshot')->nullable();
            $table->json('approvals_history')->nullable();
            $table->json('original_values')->nullable();
            $table->json('proposed_values')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('first_level_editor_id')->nullable();
            $table->timestamp('first_level_edited_at')->nullable();
            $table->text('reviewer_remarks')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('employee_seen_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_approval_workflows', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->string('active_scope_key')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_approval_levels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedInteger('sequence')->default(1);
            $table->string('label')->default('Level 1');
            $table->unsignedBigInteger('approver_employee_id');
        });
    }

    protected function setupBranchAndUsers(): array
    {
        $branch1 = Branch::query()->create([
            'branch_name' => 'Branch Alpha',
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '17:00:00',
        ]);

        $branch2 = Branch::query()->create([
            'branch_name' => 'Branch Beta',
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '17:00:00',
        ]);

        $period = PayrollPeriod::query()->create([
            'title' => 'October 1-15, 2026',
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-15',
            'date_payout' => '2026-10-20',
        ]);

        $managerUser = User::query()->create([
            'name' => 'Station Manager User',
            'email' => 'manager@example.com',
            'username' => 'manager',
        ]);

        $managerEmployee = Employee::query()->create([
            'user_id' => $managerUser->id,
            'firstname' => 'Manager',
            'lastname' => 'One',
            'branch_id' => $branch1->id,
            'is_station_manager' => false,
            'managed_branches' => null,
        ]);

        $managerUser->update(['employee_id' => $managerEmployee->id]);

        $workflow = \App\Models\LeaveApprovalWorkflow::query()->create([
            'name' => 'Branch Alpha Workflow',
            'branch_id' => $branch1->id,
            'is_active' => true,
        ]);
        \App\Models\LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 1,
            'label' => 'Station In-Charge',
            'approver_employee_id' => $managerEmployee->id,
        ]);

        $regularUser = User::query()->create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'username' => 'regular',
        ]);

        $regularEmployee = Employee::query()->create([
            'user_id' => $regularUser->id,
            'firstname' => 'Regular',
            'lastname' => 'Employee',
            'branch_id' => $branch1->id,
        ]);

        $regularUser->update(['employee_id' => $regularEmployee->id]);

        return [$branch1, $branch2, $period, $managerUser, $managerEmployee, $regularUser, $regularEmployee];
    }

    public function test_review_dtr_change_requests_has_header_button_for_history(): void
    {
        [$branch1, , $period, $managerUser] = $this->setupBranchAndUsers();

        $this->actingAs($managerUser, 'web');

        $component = Livewire::test(ReviewDtrChangeRequests::class);
        $component->assertSuccessful();

        $method = new \ReflectionMethod(ReviewDtrChangeRequests::class, 'getHeaderActions');
        $actions = $method->invoke($component->instance());
        $this->assertNotEmpty($actions);

        $historyAction = collect($actions)->first(fn ($action) => $action->getName() === 'changeRequestHistory');
        $this->assertNotNull($historyAction, 'Header action changeRequestHistory should exist');
        $this->assertEquals('Change Request History', $historyAction->getLabel());
        $this->assertEquals(StationChangeRequestHistory::getUrl(), $historyAction->getUrl());
    }

    public function test_station_change_request_history_can_be_accessed_by_station_manager(): void
    {
        [$branch1, , $period, $managerUser] = $this->setupBranchAndUsers();

        $this->actingAs($managerUser, 'web');

        $this->assertTrue(StationChangeRequestHistory::canAccess());

        $component = Livewire::test(StationChangeRequestHistory::class);
        $component->assertSuccessful();
    }

    public function test_station_change_request_history_denies_unauthorized_employee(): void
    {
        [, , , , , $regularUser] = $this->setupBranchAndUsers();

        $this->actingAs($regularUser, 'web');

        $this->assertFalse(StationChangeRequestHistory::canAccess());

        $component = Livewire::test(StationChangeRequestHistory::class);
        $component->assertForbidden();
    }

    public function test_station_change_request_history_shows_requests_from_managed_branch(): void
    {
        [$branch1, $branch2, $period, $managerUser, $managerEmployee, $regularUser, $regularEmployee] = $this->setupBranchAndUsers();

        // Request 1: Branch Alpha (managed) - Approved
        $req1 = DtrChangeRequest::query()->create([
            'employee_id' => $regularEmployee->id,
            'branch_id' => $branch1->id,
            'payroll_period_id' => $period->id,
            'dtr_date' => '2026-10-02',
            'employee_name_snapshot' => 'Regular Employee',
            'branch_name_snapshot' => 'Branch Alpha',
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'status' => DtrChangeRequest::STATUS_APPROVED,
            'reviewed_by_employee_id' => $managerEmployee->id,
            'reviewed_at' => now(),
        ]);

        // Request 2: Branch Alpha (managed) - Rejected
        $req2 = DtrChangeRequest::query()->create([
            'employee_id' => $regularEmployee->id,
            'branch_id' => $branch1->id,
            'payroll_period_id' => $period->id,
            'dtr_date' => '2026-10-03',
            'employee_name_snapshot' => 'Regular Employee',
            'branch_name_snapshot' => 'Branch Alpha',
            'request_type' => DtrChangeRequest::TYPE_TIME_DISCREPANCY,
            'status' => DtrChangeRequest::STATUS_REJECTED,
            'reviewed_by_employee_id' => $managerEmployee->id,
            'reviewed_at' => now(),
            'rejection_reason' => 'Invalid time records',
        ]);

        // Request 3: Branch Beta (not managed by this manager) - Approved
        $req3 = DtrChangeRequest::query()->create([
            'employee_id' => $regularEmployee->id,
            'branch_id' => $branch2->id,
            'payroll_period_id' => $period->id,
            'dtr_date' => '2026-10-04',
            'employee_name_snapshot' => 'Other Branch Employee',
            'branch_name_snapshot' => 'Branch Beta',
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'status' => DtrChangeRequest::STATUS_APPROVED,
        ]);

        $this->actingAs($managerUser, 'web');

        $component = Livewire::test(StationChangeRequestHistory::class);
        $component->assertSuccessful();

        $records = DtrChangeRequest::query()->forStationHistory($managerUser)->get();
        $this->assertCount(2, $records);
        $this->assertTrue($records->contains('id', $req1->id));
        $this->assertTrue($records->contains('id', $req2->id));
        $this->assertFalse($records->contains('id', $req3->id));
    }

    public function test_station_change_request_history_has_back_header_button(): void
    {
        [$branch1, , $period, $managerUser] = $this->setupBranchAndUsers();

        $this->actingAs($managerUser, 'web');

        $component = Livewire::test(StationChangeRequestHistory::class);
        $component->assertSuccessful();

        $method = new \ReflectionMethod(StationChangeRequestHistory::class, 'getHeaderActions');
        $actions = $method->invoke($component->instance());
        $this->assertNotEmpty($actions);

        $backAction = collect($actions)->first(fn ($action) => $action->getName() === 'back');
        $this->assertNotNull($backAction, 'Header action back should exist');
        $this->assertEquals(ReviewDtrChangeRequests::getUrl(), $backAction->getUrl());
    }

    public function test_approver_who_reviewed_request_can_access_history_even_without_station_manager_flag(): void
    {
        [$branch1, $branch2, $period, , , , $regularEmployee] = $this->setupBranchAndUsers();

        // RC / Area Manager assigned to Level 2 of Branch Alpha workflow without legacy station manager flag
        $approverUser = User::query()->create([
            'name' => 'Area Manager',
            'email' => 'area@example.com',
            'username' => 'areamanager',
        ]);

        $approverEmployee = Employee::query()->create([
            'user_id' => $approverUser->id,
            'firstname' => 'Area',
            'lastname' => 'Manager',
            'branch_id' => $branch2->id,
            'is_station_manager' => false,
            'managed_branches' => null,
        ]);

        $approverUser->update(['employee_id' => $approverEmployee->id]);

        $workflow = \App\Models\LeaveApprovalWorkflow::query()->where('branch_id', $branch1->id)->firstOrFail();
        \App\Models\LeaveApprovalLevel::query()->create([
            'workflow_id' => $workflow->id,
            'sequence' => 2,
            'label' => 'Area Manager',
            'approver_employee_id' => $approverEmployee->id,
        ]);

        // A change request that was reviewed by this Area Manager
        $req = DtrChangeRequest::query()->create([
            'employee_id' => $regularEmployee->id,
            'branch_id' => $branch1->id,
            'payroll_period_id' => $period->id,
            'dtr_date' => '2026-10-02',
            'employee_name_snapshot' => 'Regular Employee',
            'branch_name_snapshot' => 'Branch Alpha',
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'status' => DtrChangeRequest::STATUS_APPROVED,
            'reviewed_by_employee_id' => $approverEmployee->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($approverUser, 'web');

        $this->assertTrue(StationChangeRequestHistory::canAccess());

        $component = Livewire::test(StationChangeRequestHistory::class);
        $component->assertSuccessful();

        $records = DtrChangeRequest::query()->forStationHistory($approverUser)->get();
        $this->assertCount(1, $records);
        $this->assertEquals($req->id, $records->first()->id);
    }

    public function test_regular_employee_who_cancels_own_request_does_not_get_access_to_station_change_requests_or_history(): void
    {
        [$branch1, , $period, , , $regularUser, $regularEmployee] = $this->setupBranchAndUsers();

        // Change request submitted by regular employee and cancelled by them
        DtrChangeRequest::query()->create([
            'employee_id' => $regularEmployee->id,
            'branch_id' => $branch1->id,
            'payroll_period_id' => $period->id,
            'dtr_date' => '2026-10-02',
            'employee_name_snapshot' => 'Regular Employee',
            'branch_name_snapshot' => 'Branch Alpha',
            'request_type' => DtrChangeRequest::TYPE_OVERTIME_APPROVAL,
            'status' => DtrChangeRequest::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'approvals_history' => [
                [
                    'label' => 'Employee',
                    'level' => 1,
                    'action' => 'Cancelled',
                    'remarks' => 'Cancelled by employee.',
                    'acted_at' => '2026-10-04 13:13:10',
                    'actor_id' => $regularEmployee->id,
                    'actor_name' => $regularEmployee->full_name,
                ],
            ],
        ]);

        $this->actingAs($regularUser, 'web');

        $this->assertFalse(ReviewDtrChangeRequests::canAccess());
        $this->assertFalse(StationChangeRequestHistory::canAccess());
    }
}
