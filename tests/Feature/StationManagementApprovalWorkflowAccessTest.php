<?php

namespace Tests\Feature;

use App\Filament\Employee\Pages\LeaveApprovals;
use App\Filament\Employee\Pages\Station\ManageStationDtr;
use App\Filament\Employee\Pages\Station\ReviewDtrChangeRequests;
use App\Filament\Employee\Pages\Station\StationChangeRequestHistory;
use App\Filament\Employee\Pages\Station\StationEmployees;
use App\Filament\Employee\Pages\Station\StationOverview;
use App\Models\Branch;
use App\Models\Counter;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Dtr;
use App\Models\DtrChangeRequest;
use App\Models\Employee;
use App\Models\LeaveApprovalLevel;
use App\Models\LeaveApprovalWorkflow;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\DtrChangeRequestService;
use App\Services\LeaveApprovalAccess;
use App\Services\LeaveApprovalService;
use App\Services\StationManagementAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StationManagementApprovalWorkflowAccessTest extends TestCase
{
    protected Branch $tagum;

    protected Branch $maco;

    protected Branch $nabunturan;

    protected Department $operationsDept;

    protected Department $accountingDept;

    protected Designation $sicDesignation;

    protected Designation $rcDesignation;

    protected Designation $rmDesignation;

    protected Designation $staffDesignation;

    protected PayrollPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['permission.cache.store' => 'array']);
        app(PermissionRegistrar::class)->initializeCache();

        $this->createTestTables();

        Gate::define('Review:Leave', fn (User $user) => in_array($user->role, ['hr', 'admin'], true));
        Gate::define('Manage:LeaveWorkflow', fn (User $user) => in_array($user->role, ['hr', 'admin'], true));
        Gate::define('Override:Leave', fn (User $user) => in_array($user->role, ['hr', 'admin'], true));
        Gate::define('View:Leave', fn (User $user) => in_array($user->role, ['hr', 'admin'], true));
        Gate::define('Update:Leave', fn (User $user) => in_array($user->role, ['hr', 'admin'], true));

        Counter::query()->create(['uid' => 100]);

        $this->tagum = Branch::query()->create([
            'branch_name' => 'TAGUM STATION',
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '17:00:00',
        ]);

        $this->maco = Branch::query()->create([
            'branch_name' => 'MACO STATION',
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '17:00:00',
        ]);

        $this->nabunturan = Branch::query()->create([
            'branch_name' => 'NABUNTURAN STATION',
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '17:00:00',
        ]);

        $this->operationsDept = Department::query()->create(['name' => 'Operations']);
        $this->accountingDept = Department::query()->create(['name' => 'Accounting']);

        $this->sicDesignation = Designation::query()->create(['title' => 'Station In Charge']);
        $this->rcDesignation = Designation::query()->create(['title' => 'Roving Coordinator']);
        $this->rmDesignation = Designation::query()->create(['title' => 'Regional Manager']);
        $this->staffDesignation = Designation::query()->create(['title' => 'Forecourt Attendant']);

        $this->period = PayrollPeriod::query()->create([
            'title' => 'Oct 1-15, 2026',
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-15',
            'date_payout' => '2026-10-20',
            'is_locked' => false,
        ]);
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
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->default('secret');
            $table->string('role', 30)->default('employee');
            $table->boolean('is_disabled')->default(false);
            $table->rememberToken();
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

        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->date('date_start');
            $table->date('date_end');
            $table->date('date_payout')->nullable();
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
            $table->decimal('daily_rate', 10, 2)->nullable()->default(500);
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
            $table->string('entry_source')->default('manual');
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
            $table->decimal('daily_rate', 10, 2)->nullable()->default(500);
            $table->boolean('is_absent')->default(false);
            $table->integer('absence_minutes')->default(0);
            $table->string('comment')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_manually_edited')->default(false);
            $table->boolean('is_imported')->default(false);
            $table->boolean('is_holiday')->nullable()->default(false);
            $table->unsignedBigInteger('holiday_id')->nullable();
            $table->string('holiday_type')->nullable();
            $table->decimal('holiday_rate', 8, 2)->nullable()->default(0);
            $table->boolean('holiday_excluded')->default(false);
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
            $table->string('entry_source')->default('manual');
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
            $table->decimal('daily_rate', 10, 2)->nullable()->default(500);
            $table->boolean('is_absent')->default(false);
            $table->integer('absence_minutes')->default(0);
            $table->string('comment')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_imported')->default(false);
            $table->boolean('is_manually_edited')->default(false);
            $table->unsignedBigInteger('manual_edited_by_employee_id')->nullable();
            $table->timestamp('manual_edited_at')->nullable();
            $table->boolean('needs_review')->default(false);
            $table->text('review_reason')->nullable();
            $table->string('dtr_source')->nullable();
            $table->unsignedBigInteger('dtr_source_id')->nullable();
            $table->boolean('is_holiday')->nullable()->default(false);
            $table->unsignedBigInteger('holiday_id')->nullable();
            $table->string('holiday_type')->nullable();
            $table->decimal('holiday_rate', 8, 2)->nullable()->default(0);
            $table->boolean('holiday_excluded')->default(false);
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
            $table->string('pay_treatment', 16)->default('paid');
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

    /**
     * @return array{0: User, 1: Employee}
     */
    protected function makeEmployee(string $first, string $last, Branch $branch, ?Designation $designation = null, ?Department $department = null, array $extra = []): array
    {
        $user = User::query()->create([
            'name' => "{$first} {$last}",
            'username' => strtolower($first.'.'.$last.'.'.uniqid()),
            'email' => strtolower($first.'.'.$last.'.'.uniqid()).'@example.com',
            'password' => bcrypt('secret'),
            'role' => 'employee',
            'is_disabled' => false,
        ]);

        $employee = Employee::query()->create(array_merge([
            'user_id' => $user->id,
            'company_id' => 'PF-'.random_int(1000, 9999),
            'fingerprint_id' => (string) random_int(1000, 9999),
            'firstname' => $first,
            'lastname' => $last,
            'branch_id' => $branch->id,
            'department_id' => ($department ?? $this->operationsDept)->id,
            'designation_id' => ($designation ?? $this->staffDesignation)->id,
            'is_station_manager' => false,
            'managed_branches' => [],
        ], $extra));

        return [$user, $employee];
    }

    protected function makeBranchWorkflow(Branch $branch, array $levels, bool $isActive = true): LeaveApprovalWorkflow
    {
        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => $branch->branch_name.' Workflow',
            'is_active' => $isActive,
            'version' => 1,
            'branch_id' => $branch->id,
            'active_scope_key' => $isActive ? 'branch:'.$branch->id : null,
        ]);

        foreach ($levels as $index => $level) {
            LeaveApprovalLevel::query()->create([
                'workflow_id' => $workflow->id,
                'sequence' => $index + 1,
                'label' => $level['label'] ?? 'Level '.($index + 1),
                'approver_employee_id' => $level['employee']->id,
            ]);
        }

        return $workflow;
    }

    protected function makeDepartmentWorkflow(Department $department, array $levels, bool $isActive = true): LeaveApprovalWorkflow
    {
        $workflow = LeaveApprovalWorkflow::query()->create([
            'name' => $department->name.' Workflow',
            'is_active' => $isActive,
            'version' => 1,
            'department_id' => $department->id,
            'active_scope_key' => $isActive ? 'department:'.$department->id : null,
        ]);

        foreach ($levels as $index => $level) {
            LeaveApprovalLevel::query()->create([
                'workflow_id' => $workflow->id,
                'sequence' => $index + 1,
                'label' => $level['label'] ?? 'Level '.($index + 1),
                'approver_employee_id' => $level['employee']->id,
            ]);
        }

        return $workflow;
    }

    protected function makeDtr(Employee $employee, string $date = '2026-10-02', array $overrides = []): Dtr
    {
        return Dtr::query()->create(array_merge([
            'employee_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'payroll_period_id' => $this->period->id,
            'date_in' => $date,
            'time_in' => '08:00 AM',
            'date_out' => $date,
            'time_out' => '05:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
            'is_absent' => false,
        ], $overrides));
    }

    /**
     * Case 1: Level 1 Approver Access
     * Assign Employee A as Level 1 in Tagum workflow.
     * Expected: Station Management becomes available, scoped only to Tagum.
     */
    public function test_case_1_level_1_approver_access_is_scoped_only_to_assigned_station(): void
    {
        [$userA, $empA] = $this->makeEmployee('Alice', 'L1', $this->tagum, $this->sicDesignation);
        [$tagumStaffUser, $tagumStaff] = $this->makeEmployee('Tom', 'Tagum', $this->tagum);
        [$macoStaffUser, $macoStaff] = $this->makeEmployee('Mike', 'Maco', $this->maco);

        $this->makeBranchWorkflow($this->tagum, [
            ['label' => 'SIC', 'employee' => $empA],
        ]);

        $this->assertTrue(StationManagementAccess::canAccessStationManagement($userA));
        $this->assertSame([$this->tagum->id], StationManagementAccess::getManagedBranchIds($userA));
        $this->assertTrue(StationManagementAccess::canManageEmployee($userA, $tagumStaff));
        $this->assertFalse(StationManagementAccess::canManageEmployee($userA, $macoStaff));

        $this->actingAs($userA);
        $this->assertTrue(StationOverview::canAccess());
        $this->assertTrue(ManageStationDtr::canAccess());
        $this->assertTrue(ReviewDtrChangeRequests::canAccess());
        $this->assertTrue(StationChangeRequestHistory::canAccess());
        $this->assertTrue(StationEmployees::canAccess());
        $this->assertTrue(LeaveApprovals::canAccess());
    }

    /**
     * Case 2 & Case 3: Level 2 and Level 3 Approver Access
     * Assign Employee B as Level 2 and Employee C as Level 3 in Tagum workflow.
     * Expected: Station Management becomes available to both; B sees requests at Level 2, C sees requests at Level 3.
     */
    public function test_case_2_and_3_level_2_and_level_3_approver_access_and_sequential_visibility(): void
    {
        [$userA, $empA] = $this->makeEmployee('Alice', 'L1', $this->tagum, $this->sicDesignation);
        [$userB, $empB] = $this->makeEmployee('Bob', 'L2', $this->tagum, $this->rcDesignation);
        [$userC, $empC] = $this->makeEmployee('Charlie', 'L3', $this->tagum, $this->rmDesignation);
        [$staffUser, $staffEmp] = $this->makeEmployee('Sam', 'Worker', $this->tagum);

        $this->makeBranchWorkflow($this->tagum, [
            ['label' => 'SIC', 'employee' => $empA],
            ['label' => 'RC', 'employee' => $empB],
            ['label' => 'RM', 'employee' => $empC],
        ]);

        // Both B (L2) and C (L3) automatically receive Station Management access for Tagum
        $this->assertTrue(StationManagementAccess::canAccessStationManagement($userB));
        $this->assertTrue(StationManagementAccess::canAccessStationManagement($userC));
        $this->assertSame([$this->tagum->id], StationManagementAccess::getManagedBranchIds($userB));
        $this->assertSame([$this->tagum->id], StationManagementAccess::getManagedBranchIds($userC));

        $dtr = $this->makeDtr($staffEmp, '2026-10-02', ['time_out' => null, 'is_absent' => true]);
        $service = app(DtrChangeRequestService::class);
        $cr = $service->submitFromDtr($staffEmp, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Forgot to punch out at 5pm due to emergency.',
        ]);

        // At Level 1: only A sees it in pending queue
        $this->assertSame(1, DtrChangeRequest::query()->pendingForApprover($empA)->count());
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($empB)->count());
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($empC)->count());

        // Level 1 edits & approves -> moves to Level 2 (Employee B)
        $cr = $service->saveCorrections($cr, $empA, [
            'date_in' => '2026-10-02',
            'time_in' => '08:00 AM',
            'date_out' => '2026-10-02',
            'time_out' => '05:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
        ]);
        $cr = $service->approve($cr, $empA, 'L1 approved');
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($empA)->count());
        $this->assertSame(1, DtrChangeRequest::query()->pendingForApprover($empB)->count());
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($empC)->count());

        // Level 2 approves -> moves to Level 3 (Employee C)
        $cr = $service->approve($cr, $empB, 'L2 approved');
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($empA)->count());
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($empB)->count());
        $this->assertSame(1, DtrChangeRequest::query()->pendingForApprover($empC)->count());
    }

    /**
     * Case 4: Same Employee Assigned to Two Stations at Level 1
     * Assign Employee A as Level 1 in Tagum and Level 1 in Maco.
     * Expected: Employee A can manage both Tagum and Maco, and receives Level 1 requests from both stations.
     */
    public function test_case_4_same_employee_assigned_to_two_stations_at_level_1(): void
    {
        [$userA, $empA] = $this->makeEmployee('Alice', 'MultiL1', $this->tagum, $this->sicDesignation);
        [$tagumUser, $tagumEmp] = $this->makeEmployee('Tom', 'Tagum', $this->tagum);
        [$macoUser, $macoEmp] = $this->makeEmployee('Mike', 'Maco', $this->maco);
        [$nabUser, $nabEmp] = $this->makeEmployee('Ned', 'Nab', $this->nabunturan);

        $this->makeBranchWorkflow($this->tagum, [['label' => 'SIC', 'employee' => $empA]]);
        $this->makeBranchWorkflow($this->maco, [['label' => 'SIC', 'employee' => $empA]]);

        $branchIds = StationManagementAccess::getManagedBranchIds($userA);
        $this->assertEqualsCanonicalizing([$this->tagum->id, $this->maco->id], $branchIds);
        $this->assertTrue(StationManagementAccess::canManageEmployee($userA, $tagumEmp));
        $this->assertTrue(StationManagementAccess::canManageEmployee($userA, $macoEmp));
        $this->assertFalse(StationManagementAccess::canManageEmployee($userA, $nabEmp));

        $service = app(DtrChangeRequestService::class);
        $dtrTagum = $this->makeDtr($tagumEmp, '2026-10-02', ['time_out' => null]);
        $dtrMaco = $this->makeDtr($macoEmp, '2026-10-02', ['time_out' => null]);

        $crTagum = $service->submitFromDtr($tagumEmp, $dtrTagum, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Tagum punch',
        ]);
        $crMaco = $service->submitFromDtr($macoEmp, $dtrMaco, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Maco punch',
        ]);

        $pendingIds = DtrChangeRequest::query()->pendingForApprover($empA)->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$crTagum->id, $crMaco->id], $pendingIds);
    }

    /**
     * Case 5: Same Employee Assigned to Three Stations at Level 2
     * Assign Employee B as Level 2 in Tagum, Maco, and Nabunturan.
     * Expected: Employee B can manage all three stations and receives requests from all three when they reach Level 2.
     */
    public function test_case_5_same_employee_assigned_to_three_stations_at_level_2(): void
    {
        [$l1TagumUser, $l1Tagum] = $this->makeEmployee('Sic1', 'Tagum', $this->tagum);
        [$l1MacoUser, $l1Maco] = $this->makeEmployee('Sic2', 'Maco', $this->maco);
        [$l1NabUser, $l1Nab] = $this->makeEmployee('Sic3', 'Nab', $this->nabunturan);
        [$userB, $empB] = $this->makeEmployee('Bob', 'MultiRC', $this->tagum, $this->rcDesignation);

        [$emp1User, $emp1] = $this->makeEmployee('W1', 'Tagum', $this->tagum);
        [$emp2User, $emp2] = $this->makeEmployee('W2', 'Maco', $this->maco);
        [$emp3User, $emp3] = $this->makeEmployee('W3', 'Nab', $this->nabunturan);

        $this->makeBranchWorkflow($this->tagum, [['label' => 'SIC', 'employee' => $l1Tagum], ['label' => 'RC', 'employee' => $empB]]);
        $this->makeBranchWorkflow($this->maco, [['label' => 'SIC', 'employee' => $l1Maco], ['label' => 'RC', 'employee' => $empB]]);
        $this->makeBranchWorkflow($this->nabunturan, [['label' => 'SIC', 'employee' => $l1Nab], ['label' => 'RC', 'employee' => $empB]]);

        $this->assertEqualsCanonicalizing(
            [$this->tagum->id, $this->maco->id, $this->nabunturan->id],
            StationManagementAccess::getManagedBranchIds($userB)
        );

        $service = app(DtrChangeRequestService::class);
        $cr1 = $service->submitFromDtr($emp1, $this->makeDtr($emp1, '2026-10-02'), [
            'request_type' => DtrChangeRequest::TYPE_TIME_DISCREPANCY,
            'explanation' => 'Clock drift 1',
        ]);
        $cr2 = $service->submitFromDtr($emp2, $this->makeDtr($emp2, '2026-10-02'), [
            'request_type' => DtrChangeRequest::TYPE_TIME_DISCREPANCY,
            'explanation' => 'Clock drift 2',
        ]);
        $cr3 = $service->submitFromDtr($emp3, $this->makeDtr($emp3, '2026-10-02'), [
            'request_type' => DtrChangeRequest::TYPE_TIME_DISCREPANCY,
            'explanation' => 'Clock drift 3',
        ]);

        // None at Level 2 yet
        $this->assertSame(0, DtrChangeRequest::query()->pendingForApprover($empB)->count());

        // Each L1 saves corrections and approves
        $corr = [
            'date_in' => '2026-10-02',
            'time_in' => '08:00 AM',
            'date_out' => '2026-10-02',
            'time_out' => '05:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
        ];
        $cr1 = $service->saveCorrections($cr1, $l1Tagum, $corr);
        $cr2 = $service->saveCorrections($cr2, $l1Maco, $corr);
        $cr3 = $service->saveCorrections($cr3, $l1Nab, $corr);

        $service->approve($cr1, $l1Tagum, 'OK 1');
        $service->approve($cr2, $l1Maco, 'OK 2');
        $service->approve($cr3, $l1Nab, 'OK 3');

        // Now B sees all 3 at Level 2
        $this->assertEqualsCanonicalizing(
            [$cr1->id, $cr2->id, $cr3->id],
            DtrChangeRequest::query()->pendingForApprover($empB)->pluck('id')->all()
        );
    }

    /**
     * Case 6 & Case 12: Same Employee Assigned to Different Levels Across Different Workflows & Sequential Routing Integrity
     * Employee X is Level 1 in Tagum and Level 2 in Maco.
     * Expected: X receives Tagum requests at Level 1, and Maco requests ONLY after Maco Level 1 approves (never skips Maco L1).
     */
    public function test_case_6_and_12_mixed_levels_across_workflows_preserves_sequential_routing(): void
    {
        [$userX, $empX] = $this->makeEmployee('Xavier', 'Mixed', $this->tagum);
        [$macoL1User, $macoL1] = $this->makeEmployee('Marco', 'MacoSic', $this->maco);
        [$tagumStaffUser, $tagumStaff] = $this->makeEmployee('Tom', 'Tagum', $this->tagum);
        [$macoStaffUser, $macoStaff] = $this->makeEmployee('Mike', 'Maco', $this->maco);

        $this->makeBranchWorkflow($this->tagum, [
            ['label' => 'SIC', 'employee' => $empX],
        ]);
        $this->makeBranchWorkflow($this->maco, [
            ['label' => 'SIC', 'employee' => $macoL1],
            ['label' => 'RC', 'employee' => $empX],
        ]);

        $service = app(DtrChangeRequestService::class);
        $crTagum = $service->submitFromDtr($tagumStaff, $this->makeDtr($tagumStaff, '2026-10-02'), [
            'request_type' => DtrChangeRequest::TYPE_TIME_DISCREPANCY,
            'explanation' => 'Tagum req details',
        ]);
        $crMaco = $service->submitFromDtr($macoStaff, $this->makeDtr($macoStaff, '2026-10-02'), [
            'request_type' => DtrChangeRequest::TYPE_TIME_DISCREPANCY,
            'explanation' => 'Maco req details',
        ]);

        // Maco request must start at Level 1 (macoL1), NOT skip directly to X
        $this->assertSame(1, $crMaco->current_level);
        $this->assertSame([$crTagum->id], DtrChangeRequest::query()->pendingForApprover($empX)->pluck('id')->all());
        $this->assertFalse($crMaco->canBeReviewedBy($empX));
        $this->assertTrue($crMaco->canBeReviewedBy($macoL1));

        // After Maco L1 edits and approves, X receives Maco request at Level 2
        $crMaco = $service->saveCorrections($crMaco, $macoL1, [
            'date_in' => '2026-10-02',
            'time_in' => '08:00 AM',
            'date_out' => '2026-10-02',
            'time_out' => '05:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
        ]);
        $crMaco = $service->approve($crMaco, $macoL1, 'Maco L1 approved');
        $this->assertSame(2, $crMaco->current_level);
        $this->assertTrue($crMaco->canBeReviewedBy($empX));
        $this->assertEqualsCanonicalizing([$crTagum->id, $crMaco->id], DtrChangeRequest::query()->pendingForApprover($empX)->pluck('id')->all());
    }

    /**
     * Case 7: Same Employee Assigned to Department and Station Workflows
     * Assign Employee Y as Level 1 in Tagum Station workflow and Level 2 in Accounting Department workflow.
     * Expected: Employee Y can manage Tagum Station employees and Accounting Department employees, but not unrelated stations/departments.
     */
    public function test_case_7_same_employee_assigned_to_department_and_station_workflows(): void
    {
        [$userY, $empY] = $this->makeEmployee('Yara', 'Hybrid', $this->tagum);
        [$acctHeadUser, $acctHead] = $this->makeEmployee('Arthur', 'AcctL1', $this->maco, null, $this->accountingDept);

        [$tagumOpsUser, $tagumOps] = $this->makeEmployee('Tom', 'TagumOps', $this->tagum, null, $this->operationsDept);
        [$macoAcctUser, $macoAcct] = $this->makeEmployee('Mary', 'MacoAcct', $this->maco, null, $this->accountingDept);
        [$nabOpsUser, $nabOps] = $this->makeEmployee('Ned', 'NabOps', $this->nabunturan, null, $this->operationsDept);

        $this->makeBranchWorkflow($this->tagum, [['label' => 'SIC', 'employee' => $empY]]);
        $this->makeDepartmentWorkflow($this->accountingDept, [
            ['label' => 'Dept Head', 'employee' => $acctHead],
            ['label' => 'RC', 'employee' => $empY],
        ]);

        $this->assertTrue(StationManagementAccess::canManageEmployee($userY, $tagumOps));
        $this->assertTrue(StationManagementAccess::canManageEmployee($userY, $macoAcct));
        $this->assertFalse(StationManagementAccess::canManageEmployee($userY, $nabOps));
    }

    /**
     * Case 8, Case 9, Case 10: Partial Removal, Full Removal, and Workflow Deactivation
     */
    public function test_case_8_9_10_partial_removal_full_removal_and_workflow_deactivation(): void
    {
        [$userB, $empB] = $this->makeEmployee('Bob', 'Dynamic', $this->tagum);
        [$tagumStaffUser, $tagumStaff] = $this->makeEmployee('Tom', 'Tagum', $this->tagum);
        [$macoStaffUser, $macoStaff] = $this->makeEmployee('Mike', 'Maco', $this->maco);

        $tagumFlow = $this->makeBranchWorkflow($this->tagum, [['label' => 'SIC', 'employee' => $empB]]);
        $macoFlow = $this->makeBranchWorkflow($this->maco, [['label' => 'SIC', 'employee' => $empB]]);

        // Initially manages both Tagum and Maco
        $this->assertEqualsCanonicalizing([$this->tagum->id, $this->maco->id], StationManagementAccess::getManagedBranchIds($userB));

        // Case 8: Partial removal from Maco workflow -> Tagum remains, Maco removed
        LeaveApprovalLevel::query()->where('workflow_id', $macoFlow->id)->delete();
        $this->assertTrue(StationManagementAccess::canAccessStationManagement($userB));
        $this->assertSame([$this->tagum->id], StationManagementAccess::getManagedBranchIds($userB));
        $this->assertTrue(StationManagementAccess::canManageEmployee($userB, $tagumStaff));
        $this->assertFalse(StationManagementAccess::canManageEmployee($userB, $macoStaff));

        // Case 10: Deactivate Tagum workflow -> loses access
        $tagumFlow->update(['is_active' => false, 'active_scope_key' => null]);
        $this->assertFalse(StationManagementAccess::canAccessStationManagement($userB));
        $this->assertEmpty(StationManagementAccess::getManagedBranchIds($userB));

        // Re-activate Tagum workflow, then test Case 9: Full removal from all workflows
        $tagumFlow->update(['is_active' => true, 'active_scope_key' => 'branch:'.$this->tagum->id]);
        $this->assertTrue(StationManagementAccess::canAccessStationManagement($userB));

        LeaveApprovalLevel::query()->where('workflow_id', $tagumFlow->id)->delete();
        $this->assertFalse(StationManagementAccess::canAccessStationManagement($userB));

        $this->actingAs($userB);
        $this->assertFalse(StationOverview::canAccess());
        $this->assertFalse(ManageStationDtr::canAccess());
        $this->assertFalse(ReviewDtrChangeRequests::canAccess());
        $this->assertFalse(StationChangeRequestHistory::canAccess());
        $this->assertFalse(StationEmployees::canAccess());
        $this->assertFalse(LeaveApprovals::canAccess());
        $this->get('/employee/station/overview')->assertForbidden();
        $this->get('/employee/station/dtr')->assertForbidden();
        $this->get('/employee/station/change-requests')->assertForbidden();
    }

    /**
     * Case 11: Unassigned Station Access Attempt
     * Tagum approver tries to open Maco station employees page or print Maco employee DTR.
     * Expected: 403 Forbidden.
     */
    public function test_case_11_unassigned_station_access_attempt_is_forbidden(): void
    {
        [$userA, $empA] = $this->makeEmployee('Alice', 'TagumOnly', $this->tagum);
        [$macoUser, $macoEmp] = $this->makeEmployee('Mike', 'Maco', $this->maco);

        $this->makeBranchWorkflow($this->tagum, [['label' => 'SIC', 'employee' => $empA]]);

        $this->actingAs($userA);

        $this->get('/employee/station/employees?branchId='.$this->maco->publicKey().'&periodId='.$this->period->publicKey())
            ->assertForbidden();

        $this->get(route('sicrc_tools.dtr.print', [
            'period' => $this->period->id,
            'branch' => $this->maco->id,
            'employee' => $macoEmp->id,
        ]))->assertForbidden();
    }

    /**
     * Case 13: DTR Change Request Sequential Approval (L1 edits -> L2 approves -> L3 approves -> Finalized)
     */
    public function test_case_13_dtr_change_request_sequential_approval_across_three_levels(): void
    {
        [$userL1, $empL1] = $this->makeEmployee('L1', 'Approver', $this->tagum);
        [$userL2, $empL2] = $this->makeEmployee('L2', 'Approver', $this->tagum);
        [$userL3, $empL3] = $this->makeEmployee('L3', 'Approver', $this->tagum);
        [$staffUser, $staffEmp] = $this->makeEmployee('Worker', 'One', $this->tagum);

        $this->makeBranchWorkflow($this->tagum, [
            ['label' => 'SIC', 'employee' => $empL1],
            ['label' => 'RC', 'employee' => $empL2],
            ['label' => 'RM', 'employee' => $empL3],
        ]);

        $dtr = $this->makeDtr($staffEmp, '2026-10-02', ['time_out' => '04:30 PM']);
        $service = app(DtrChangeRequestService::class);

        $cr = $service->submitFromDtr($staffEmp, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_TIME_DISCREPANCY,
            'explanation' => 'Biometric clock drift on Oct 2.',
        ]);

        // Level 1 edits time_out to 05:05 PM and approves
        $this->assertTrue($cr->canBeEditedBy($empL1));
        $this->assertFalse($cr->canBeEditedBy($empL2));
        $cr = $service->saveCorrections($cr, $empL1, [
            'date_in' => '2026-10-02',
            'time_in' => '08:00 AM',
            'date_out' => '2026-10-02',
            'time_out' => '05:05 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
        ]);
        $cr = $service->approve($cr, $empL1, 'Adjusted to 05:05 PM');
        $this->assertSame('Pending', $cr->status);
        $this->assertSame(2, $cr->current_level);

        // Level 2 approves
        $cr = $service->approve($cr, $empL2, 'L2 verified');
        $this->assertSame('Pending', $cr->status);
        $this->assertSame(3, $cr->current_level);

        // Level 3 approves -> final level applies changes and marks Approved
        $cr = $service->approve($cr, $empL3, 'L3 finalized');
        $this->assertSame('Approved', $cr->status);
        $this->assertSame('05:05 PM', $dtr->fresh()->time_out);
    }

    /**
     * Case 14: Overtime / Early OT Approval Across Multiple Stations
     * Multi-station approver can manage OT/Early OT for Tagum and Maco, blocked for Nabunturan.
     */
    public function test_case_14_overtime_and_early_ot_scope_across_multiple_stations(): void
    {
        [$approverUser, $approverEmp] = $this->makeEmployee('Olivia', 'MultiApprover', $this->tagum);
        [$tagumUser, $tagumEmp] = $this->makeEmployee('Tom', 'Tagum', $this->tagum);
        [$macoUser, $macoEmp] = $this->makeEmployee('Mike', 'Maco', $this->maco);
        [$nabUser, $nabEmp] = $this->makeEmployee('Ned', 'Nab', $this->nabunturan);

        $this->makeBranchWorkflow($this->tagum, [['label' => 'SIC', 'employee' => $approverEmp]]);
        $this->makeBranchWorkflow($this->maco, [['label' => 'RC', 'employee' => $approverEmp]]);

        $this->assertTrue(StationManagementAccess::canManageEmployee($approverUser, $tagumEmp));
        $this->assertTrue(StationManagementAccess::canManageEmployee($approverUser, $macoEmp));
        $this->assertFalse(StationManagementAccess::canManageEmployee($approverUser, $nabEmp));
        $this->assertTrue(StationManagementAccess::canManageBranch($approverUser, $this->tagum->id));
        $this->assertTrue(StationManagementAccess::canManageBranch($approverUser, $this->maco->id));
        $this->assertFalse(StationManagementAccess::canManageBranch($approverUser, $this->nabunturan->id));
    }

    /**
     * Case 15: Leave Request Sequential Approval Across Multiple Workflows
     */
    public function test_case_15_leave_request_sequential_approval_across_multiple_workflows(): void
    {
        [$tagumSicUser, $tagumSic] = $this->makeEmployee('Tina', 'TagumSic', $this->tagum);
        [$macoSicUser, $macoSic] = $this->makeEmployee('Marco', 'MacoSic', $this->maco);
        [$sharedRcUser, $sharedRc] = $this->makeEmployee('Ryan', 'SharedRc', $this->tagum);
        [$tagumStaffUser, $tagumStaff] = $this->makeEmployee('Tom', 'Worker', $this->tagum);

        $this->makeBranchWorkflow($this->tagum, [
            ['label' => 'SIC', 'employee' => $tagumSic],
            ['label' => 'RC', 'employee' => $sharedRc],
        ]);
        $this->makeBranchWorkflow($this->maco, [
            ['label' => 'SIC', 'employee' => $macoSic],
            ['label' => 'RC', 'employee' => $sharedRc],
        ]);

        $leaveService = app(LeaveApprovalService::class);
        $leave = $leaveService->submit($tagumStaff, [
            'leave_type' => 'Leave With Pay',
            'leave_from' => '2026-10-08',
            'leave_to' => '2026-10-08',
            'reason' => 'Personal errand',
        ], $tagumStaffUser);

        $step1 = $leave->approvalSteps()->where('sequence', 1)->firstOrFail();
        $this->assertSame('Pending', $step1->status);
        $this->assertSame($tagumSic->id, (int) $step1->approver_employee_id);

        // Maco SIC cannot approve Tagum leave
        try {
            $leaveService->decide($leave, $step1->id, $macoSicUser, true, 'Unauthorized');
            $this->fail('Maco SIC should not be able to approve Tagum leave');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // Tagum SIC approves Level 1 -> advances to Shared RC at Level 2
        $leaveService->decide($leave, $step1->id, $tagumSicUser, true, 'Tagum SIC approved');
        $step2 = $leave->fresh()->approvalSteps()->where('sequence', 2)->firstOrFail();
        $this->assertSame('Pending', $step2->status);
        $this->assertSame($sharedRc->id, (int) $step2->approver_employee_id);

        // Shared RC approves Level 2 -> advances to HR
        $leaveService->decide($leave->fresh(), $step2->id, $sharedRcUser, true, 'Shared RC approved');
        $this->assertSame('hr', $leave->fresh()->approval_phase);
    }

    /**
     * Case 16: Missing DTR Entries and Delete Time Entry Requests
     * Follow workflow scope and level order; unassigned approver is blocked.
     */
    public function test_case_16_missing_dtr_and_delete_time_entry_follow_workflow_scope_and_levels(): void
    {
        [$tagumSicUser, $tagumSic] = $this->makeEmployee('Tina', 'TagumSic', $this->tagum);
        [$macoSicUser, $macoSic] = $this->makeEmployee('Marco', 'MacoSic', $this->maco);
        [$staffUser, $staffEmp] = $this->makeEmployee('Sam', 'TagumStaff', $this->tagum);

        $this->makeBranchWorkflow($this->tagum, [['label' => 'SIC', 'employee' => $tagumSic]]);
        $this->makeBranchWorkflow($this->maco, [['label' => 'SIC', 'employee' => $macoSic]]);

        $attachmentPath = UploadedFile::fake()->image('proof.png', 400, 400)->store('dtr-change-requests', 'local');
        $service = app(DtrChangeRequestService::class);
        $missingReq = $service->submitMissingDtrEntries($staffEmp, [
            'payroll_period_id' => $this->period->id,
            'entries' => [
                ['date_in' => '2026-10-05', 'time_in' => '08:00 AM', 'date_out' => '2026-10-05', 'time_out' => '05:00 PM', 'schedule_type' => 'Regular'],
            ],
            'explanation' => 'Biometric offline during power outage.',
            'attachment_path' => $attachmentPath,
            'attachment_original_name' => 'proof.png',
        ]);

        $this->assertFalse($missingReq->canBeReviewedBy($macoSic));
        $this->assertTrue($missingReq->canBeReviewedBy($tagumSic));

        $missingReq = $service->approve($missingReq, $tagumSic, 'Approved missing entry');
        $this->assertSame('Approved', $missingReq->status);
        $createdDtr = Dtr::query()->where('fingerprint_id', $staffEmp->fingerprint_id)->whereDate('date_in', '2026-10-05')->firstOrFail();

        // Now submit a Delete Time Entry request for that DTR
        $deleteReq = $service->submitFromDtr($staffEmp, $createdDtr, [
            'request_type' => DtrChangeRequest::TYPE_DELETE_TIME_ENTRY,
            'explanation' => 'Duplicate entry submitted by mistake.',
        ]);
        $this->assertFalse($deleteReq->canBeReviewedBy($macoSic));
        $this->assertTrue($deleteReq->canBeReviewedBy($tagumSic));

        $deleteReq = $service->approve($deleteReq, $tagumSic, 'Approved permanent deletion');
        $this->assertSame('Approved', $deleteReq->status);
        $this->assertNull(Dtr::withTrashed()->find($createdDtr->id));
    }

    /**
     * Case 17 & Case 18: Designation Alone or Old Station Manager Flag Without Workflow Does Not Grant Access
     */
    public function test_case_17_and_18_designation_or_legacy_station_manager_flag_without_workflow_denies_access(): void
    {
        // Case 17: Has SIC designation and RC designation, but not in any workflow
        [$sicUser] = $this->makeEmployee('Solo', 'Sic', $this->tagum, $this->sicDesignation);
        [$rcUser] = $this->makeEmployee('Solo', 'Rc', $this->tagum, $this->rcDesignation);

        // Case 18: Has legacy is_station_manager = true and managed_branches = [tagum, maco], but not in any workflow
        [$legacyManagerUser] = $this->makeEmployee('Legacy', 'Manager', $this->tagum, $this->sicDesignation, null, [
            'is_station_manager' => true,
            'managed_branches' => [$this->tagum->id, $this->maco->id],
        ]);

        $this->assertFalse(StationManagementAccess::canAccessStationManagement($sicUser));
        $this->assertFalse(StationManagementAccess::canAccessStationManagement($rcUser));
        $this->assertFalse(StationManagementAccess::canAccessStationManagement($legacyManagerUser));
        $this->assertEmpty(StationManagementAccess::getManagedBranchIds($legacyManagerUser));
        $this->assertFalse(LeaveApprovalAccess::inbox($legacyManagerUser));
    }

    /**
     * Case 19: Disabled or Inactive Approver Account
     * Approver account is disabled or employment has ended -> no Station Management access and cannot approve.
     */
    public function test_case_19_disabled_or_ended_employment_approver_loses_station_management_access(): void
    {
        [$approverUser, $approverEmp] = $this->makeEmployee('Active', 'Approver', $this->tagum);
        $this->makeBranchWorkflow($this->tagum, [['label' => 'SIC', 'employee' => $approverEmp]]);

        $this->assertTrue(StationManagementAccess::canAccessStationManagement($approverUser));

        // Disable user account
        $approverUser->update(['is_disabled' => true]);
        $this->assertFalse(StationManagementAccess::canAccessStationManagement($approverUser->fresh()));

        // Re-enable user account, then end employment
        $approverUser->update(['is_disabled' => false]);
        $this->assertTrue(StationManagementAccess::canAccessStationManagement($approverUser->fresh()));

        $approverEmp->update([
            'employment_type' => 'Resigned',
            'end_status_date' => now()->subDay()->toDateString(),
        ]);
        $this->assertFalse(StationManagementAccess::canAccessStationManagement($approverUser->fresh()));
    }

    /**
     * Case 20: Historical Records Remain Intact After Approver Removal
     * Employee A approves a request, is later removed from the workflow.
     * Expected: Past approved request still keeps Employee A's approval history, while Employee A loses Station Management access.
     */
    public function test_case_20_historical_records_remain_intact_after_approver_removal(): void
    {
        [$userA, $empA] = $this->makeEmployee('Alice', 'FormerApprover', $this->tagum);
        [$staffUser, $staffEmp] = $this->makeEmployee('Sam', 'Staff', $this->tagum);

        $workflow = $this->makeBranchWorkflow($this->tagum, [['label' => 'SIC', 'employee' => $empA]]);

        $dtr = $this->makeDtr($staffEmp, '2026-10-02', ['time_out' => null]);
        $service = app(DtrChangeRequestService::class);
        $cr = $service->submitFromDtr($staffEmp, $dtr, [
            'request_type' => DtrChangeRequest::TYPE_FORGOT_TO_PUNCH,
            'explanation' => 'Forgot punch out on Oct 2.',
        ]);
        $cr = $service->saveCorrections($cr, $empA, [
            'date_in' => '2026-10-02',
            'time_in' => '08:00 AM',
            'date_out' => '2026-10-02',
            'time_out' => '05:00 PM',
            'schedule_start' => '08:00 AM',
            'schedule_end' => '05:00 PM',
            'schedule_type' => 'Regular',
        ]);
        $cr = $service->approve($cr, $empA, 'Approved by Alice');

        $this->assertSame('Approved', $cr->status);
        $this->assertSame($empA->id, (int) $cr->reviewed_by_employee_id);

        // Remove Alice from the workflow
        LeaveApprovalLevel::query()->where('workflow_id', $workflow->id)->delete();

        // Alice loses Station Management access
        $this->assertFalse(StationManagementAccess::canAccessStationManagement($userA->fresh()));

        // Historical record still preserves Alice's approval metadata and step history
        $crFresh = $cr->fresh();
        $this->assertSame('Approved', $crFresh->status);
        $this->assertSame($empA->id, (int) $crFresh->reviewed_by_employee_id);
        $this->assertSame('Approved by Alice', $crFresh->reviewer_remarks);
        $this->assertCount(1, $crFresh->approvals_history);
        $this->assertSame($empA->id, (int) $crFresh->approvals_history[0]['actor_id']);
        $this->assertSame('Approved', $crFresh->approvals_history[0]['action']);
    }
}
