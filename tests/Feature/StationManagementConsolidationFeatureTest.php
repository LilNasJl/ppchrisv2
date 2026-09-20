<?php

namespace Tests\Feature;

use App\Filament\Auth\EmployeeLogin;
use App\Filament\Employee\Pages\Station\StationEmployees;
use App\Filament\Employee\Widgets\StationBranchAttendanceChart;
use App\Models\Branch;
use App\Models\EmployeeVisibleDtr;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class StationManagementConsolidationFeatureTest extends TestCase
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

        Schema::create('leave_request_approvals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('approver_employee_id');
            $table->timestamps();
        });

        Schema::create('leave_approval_workflows', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Default Workflow');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leave_approval_levels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('approver_employee_id')->nullable();
            $table->unsignedBigInteger('alternate_employee_id')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Test User');
            $table->string('email')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->default('secret');
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
            $table->boolean('is_locked')->default(false);
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

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('uid', 20)->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('company_id', 20)->nullable();
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
            $table->unsignedBigInteger('employee_id');
            $table->string('fingerprint_id')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('assigned_employee_id')->nullable();
            $table->unsignedBigInteger('reviewed_by_employee_id')->nullable();
            $table->date('date_from');
            $table->date('date_to');
            $table->string('request_type');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->text('reviewer_remarks')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('employee_seen_at')->nullable();
            $table->string('employee_name_snapshot')->nullable();
            $table->string('employee_company_id_snapshot')->nullable();
            $table->string('branch_name_snapshot')->nullable();
            $table->string('payroll_period_title_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::create('dtr_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('submitted_by_employee_id')->nullable();
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->string('submission_type')->default('dtr');
            $table->date('date_in')->nullable();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status')->default('pending');
            $table->text('comments')->nullable();
            $table->text('description')->nullable();
            $table->text('review_remarks')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('employee_company_id_snapshot')->nullable();
            $table->string('branch_name_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_visible_dtrs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('fingerprint_id')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->date('date_in')->nullable();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();
            $table->string('schedule_type')->nullable();
            $table->boolean('is_imported')->default(false);
            $table->integer('credited_overtime')->default(0);
            $table->integer('undertime')->default(0);
            $table->integer('late')->default(0);
            $table->integer('early_clock_in')->default(0);
            $table->integer('credited_early_clock_in')->default(0);
            $table->boolean('overtime_approved')->default(false);
            $table->boolean('early_clock_in_approved')->default(false);
            $table->boolean('is_manually_edited')->default(false);
            $table->unsignedBigInteger('manual_edited_by_employee_id')->nullable();
            $table->timestamp('manual_edited_at')->nullable();
            $table->boolean('needs_review')->default(false);
            $table->text('review_reason')->nullable();
            $table->string('dtr_source')->nullable();
            $table->unsignedBigInteger('dtr_source_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('sic_rc_dtr_imports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('imported_by_employee_id')->nullable();
            $table->string('batch_id')->nullable();
            $table->string('import_name')->nullable();
            $table->string('source_filename')->nullable();
            $table->integer('imported_rows')->default(0);
            $table->integer('skipped_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->string('status')->default('completed');
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_regular_employee_is_forbidden_from_station_management_routes(): void
    {
        [$regularUser, , $branch, $period] = $this->setupUsers();

        $this->actingAs($regularUser, 'web');

        $this->get('/employee/station/overview')->assertForbidden();
        $this->get('/employee/station/dtr')->assertForbidden();
        $this->get('/employee/station/change-requests')->assertForbidden();
        $this->get('/employee/station/biometrics')->assertForbidden();
        $this->get('/employee/station/employees?branchId='.$branch->publicKey().'&periodId='.$period->publicKey())->assertForbidden();
    }

    public function test_authorized_station_manager_can_access_station_management_routes(): void
    {
        [, $managerUser, $branch, $period] = $this->setupUsers();

        $this->actingAs($managerUser, 'web');

        $this->get('/employee/station/overview')->assertOk();
        $this->get('/employee/station/dtr')->assertOk();
        $this->get('/employee/station/change-requests')->assertOk();
        $this->get('/employee/station/biometrics')->assertOk();
        $this->get('/employee/station/employees?branchId='.$branch->publicKey().'&periodId='.$period->publicKey())->assertOk();
    }

    public function test_station_manager_cannot_access_unassigned_branch(): void
    {
        [, $managerUser, , $period] = $this->setupUsers();

        $unassignedBranch = Branch::query()->create([
            'branch_name' => 'Unassigned Branch',
            'branch_address' => 'Another Address',
            'mobile_no' => '09999999999',
            'employee_id' => 0,
            'no_of_shifts' => 1,
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '17:00:00',
            'is_24hrs' => false,
            'has_broken_time' => false,
        ]);

        $this->actingAs($managerUser, 'web');

        $this->get('/employee/station/employees?branchId='.$unassignedBranch->publicKey().'&periodId='.$period->publicKey())
            ->assertForbidden();
    }

    public function test_station_manager_logs_in_via_employee_portal_with_company_id(): void
    {
        [, $managerUser] = $this->setupUsers();

        Livewire::test(EmployeeLogin::class)
            ->fillForm([
                'username' => 'PF0999',
                'password' => 'secret-pass',
            ])
            ->call('authenticate')
            ->assertRedirect(url('/employee'));

        $this->assertAuthenticatedAs($managerUser, 'web');
    }

    public function test_legacy_sicrc_routes_are_completely_removed(): void
    {
        $this->get('/sicrc')->assertNotFound();
        $this->get('/sicrc/login')->assertNotFound();
    }

    public function test_station_branch_attendance_chart_renders_cleanly_and_aggregates_metrics(): void
    {
        [, $managerUser, $branch, $period] = $this->setupUsers();

        EmployeeVisibleDtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $managerUser->employee->id,
            'date_in' => '2026-09-02',
            'time_in' => '07:30:00',
            'date_out' => '2026-09-02',
            'time_out' => '18:30:00',
            'credited_overtime' => 90,
            'undertime' => 15,
            'late' => 10,
            'early_clock_in' => 30,
            'credited_early_clock_in' => 30,
            'overtime_approved' => true,
            'early_clock_in_approved' => true,
        ]);

        $this->actingAs($managerUser, 'web');

        $component = Livewire::test(StationBranchAttendanceChart::class);
        $component->assertSuccessful();

        $method = new \ReflectionMethod(StationBranchAttendanceChart::class, 'getData');
        $data = $method->invoke($component->instance());

        $this->assertNotEmpty($data['datasets']);
        $this->assertEquals(['Tagum Station'], $data['labels']);

        // Approved Overtime: 90 - 30 = 60
        $this->assertEquals([60], $data['datasets'][0]['data']);
        // Undertime: 15
        $this->assertEquals([15], $data['datasets'][1]['data']);
        // Late: 10
        $this->assertEquals([10], $data['datasets'][2]['data']);
        // Approved Early Overtime: 30
        $this->assertEquals([30], $data['datasets'][3]['data']);
    }

    public function test_download_dtr_action_notifies_when_no_records_exist(): void
    {
        [, $managerUser, $branch, $period] = $this->setupUsers();

        $this->actingAs($managerUser, 'web');

        Livewire::withQueryParams([
            'branchId' => $branch->id,
            'periodId' => $period->id,
        ])
            ->test(StationEmployees::class)
            ->callAction('downloadDtr')
            ->assertNotified('No D.T.R Records Found');
    }

    public function test_download_dtr_action_streams_file_when_records_exist(): void
    {
        [, $managerUser, $branch, $period] = $this->setupUsers();

        EmployeeVisibleDtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $managerUser->employee->id,
            'fingerprint_id' => '1001',
            'date_in' => '2026-09-02',
            'time_in' => '08:00:00',
            'date_out' => '2026-09-02',
            'time_out' => '17:00:00',
        ]);

        $this->actingAs($managerUser, 'web');

        $component = Livewire::withQueryParams([
            'branchId' => $branch->id,
            'periodId' => $period->id,
        ])
            ->test(StationEmployees::class)
            ->callAction('downloadDtr');

        $component->assertSuccessful();
    }

    public function test_export_controller_redirects_with_notification_when_no_records_exist(): void
    {
        [, $managerUser, $branch, $period] = $this->setupUsers();

        $this->actingAs($managerUser, 'web');

        $response = $this->get('/sicrc-tools/export/dtr-preview.bin?period_id='.$period->id.'&branch_id='.$branch->id);

        $response->assertRedirect();
    }

    public function test_export_controller_streams_bin_file_when_records_exist(): void
    {
        [, $managerUser, $branch, $period] = $this->setupUsers();

        EmployeeVisibleDtr::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'employee_id' => $managerUser->employee->id,
            'fingerprint_id' => '1001',
            'date_in' => '2026-09-02',
            'time_in' => '08:00:00',
            'date_out' => '2026-09-02',
            'time_out' => '17:00:00',
        ]);

        $this->actingAs($managerUser, 'web');

        $response = $this->get('/sicrc-tools/export/dtr-preview.bin?period_id='.$period->id.'&branch_id='.$branch->id);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/octet-stream');
    }

    /**
     * @return array{0: User, 1: User, 2: Branch, 3: PayrollPeriod}
     */
    private function setupUsers(): array
    {
        $branch = Branch::query()->create([
            'branch_name' => 'Tagum Station',
            'branch_address' => 'Tagum City',
            'mobile_no' => '09123456789',
            'employee_id' => 0,
            'no_of_shifts' => 1,
            'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '17:00:00',
            'is_24hrs' => false,
            'has_broken_time' => false,
        ]);

        $period = PayrollPeriod::query()->create([
            'title' => 'Sep 01 - 15, 2026',
            'date_start' => '2026-09-01',
            'date_end' => '2026-09-15',
            'date_payout' => '2026-09-20',
            'description' => 'Test Period',
            'is_locked' => false,
        ]);

        // Regular employee
        $regularEmp = Employee::query()->create([
            'uid' => '0001',
            'company_id' => 'PF-0001',
            'firstname' => 'Juan',
            'lastname' => 'Dela Cruz',
            'branch_id' => $branch->id,
            'is_station_manager' => false,
            'employment_type' => 'Permanent',
            'rate_type' => 'daily',
            'daily_rate' => 500,
        ]);
        $regularUser = User::query()->create([
            'name' => 'Juan Dela Cruz',
            'username' => 'PF0001',
            'password' => Hash::make('secret-pass'),
            'role' => 'employee',
            'employee_id' => $regularEmp->id,
            'is_disabled' => false,
        ]);
        $regularEmp->user_id = $regularUser->id;
        $regularEmp->save();
        $regularEmp->setRelation('user', $regularUser);

        // Station manager employee
        $managerEmp = Employee::query()->create([
            'uid' => '0999',
            'company_id' => 'PF-0999',
            'firstname' => 'Camille',
            'lastname' => 'Manager',
            'branch_id' => $branch->id,
            'is_station_manager' => true,
            'managed_branches' => [['branch_id' => $branch->id, 'branch_name' => 'Tagum Station']],
            'employment_type' => 'Permanent',
            'rate_type' => 'daily',
            'daily_rate' => 800,
        ]);
        $managerUser = User::query()->create([
            'name' => 'Camille Manager',
            'username' => 'PF0999',
            'password' => Hash::make('secret-pass'),
            'role' => 'employee',
            'employee_id' => $managerEmp->id,
            'is_disabled' => false,
        ]);
        $managerEmp->user_id = $managerUser->id;
        $managerEmp->save();
        $managerEmp->setRelation('user', $managerUser);

        return [$regularUser, $managerUser, $branch, $period];
    }
}
