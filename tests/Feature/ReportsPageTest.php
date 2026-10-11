<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('role');
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('branch_name');
            $table->softDeletes();
        });

        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });

        Schema::create('designations', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->softDeletes();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->unsignedInteger('uid');
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('lastname');
            $table->string('gender')->nullable();
            $table->date('birthdate')->nullable();
            $table->date('hired_date')->nullable();
            $table->string('employment_type')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->foreignId('designation_id')->nullable();
            $table->string('fingerprint_id')->nullable();
            $table->softDeletes();
        });

        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->date('date_start');
            $table->date('date_end');
            $table->softDeletes();
        });

        Schema::create('dtrs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_period_id');
            $table->foreignId('branch_id');
            $table->string('fingerprint_id');
            $table->string('schedule_type')->nullable();
            $table->boolean('is_imported')->default(false);
            $table->date('date_in')->nullable();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();
            $table->unsignedInteger('late')->default(0);
            $table->unsignedInteger('undertime')->default(0);
            $table->unsignedInteger('credited_overtime')->default(0);
            $table->unsignedInteger('early_clock_in')->default(0);
            $table->softDeletes();
        });

        Schema::create('memos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activities', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('description')->nullable();
            $table->date('date_from');
            $table->date('date_to');
            $table->softDeletes();
        });

        DB::table('users')->insert([
            ['id' => 1, 'role' => 'employee'],
            ['id' => 2, 'role' => 'employee'],
            ['id' => 3, 'role' => 'employee'],
            ['id' => 4, 'role' => 'hr'],
        ]);
        DB::table('branches')->insert([
            ['id' => 1, 'branch_name' => 'North'],
            ['id' => 2, 'branch_name' => 'South'],
        ]);
        DB::table('departments')->insert(['id' => 1, 'name' => 'Operations']);
        DB::table('designations')->insert(['id' => 1, 'title' => 'Attendant']);
        DB::table('employees')->insert([
            ['id' => 1, 'user_id' => 1, 'uid' => 1, 'firstname' => 'Ana', 'lastname' => 'Able', 'employment_type' => 'Permanent', 'branch_id' => 1, 'department_id' => 1, 'designation_id' => 1, 'fingerprint_id' => '100'],
            ['id' => 2, 'user_id' => 2, 'uid' => 2, 'firstname' => 'Ben', 'lastname' => 'Baker', 'employment_type' => 'Permanent', 'branch_id' => 2, 'department_id' => 1, 'designation_id' => 1, 'fingerprint_id' => '200'],
            ['id' => 3, 'user_id' => 3, 'uid' => 3, 'firstname' => 'Cara', 'lastname' => 'Cruz', 'employment_type' => 'Resigned', 'branch_id' => 1, 'department_id' => 1, 'designation_id' => 1, 'fingerprint_id' => '300'],
        ]);
        DB::table('payroll_periods')->insert(['id' => 1, 'title' => 'October 2026', 'date_start' => '2026-10-01', 'date_end' => '2026-10-15']);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_headcount_and_employee_rows_respect_active_employment_and_branch(): void
    {
        $report = app(Reports::class);
        $report->report_type = 'employee_headcount_branch';

        $this->assertEqualsCanonicalizing([['North', 1], ['South', 1]], $report->getReportRowsProperty()->all());

        $report->branch_id = '1';
        $this->assertSame([['North', 1]], $report->getReportRowsProperty()->all());

        $report->report_type = 'employee_all';
        $this->assertCount(1, $report->getReportRowsProperty());

        $report->branch_id = 'all';
        $report->employee_id = '2';
        $this->assertSame('Baker, Ben', $report->getReportRowsProperty()->first()[1]);

        $report->employee_id = null;
        DB::table('branches')->where('id', 2)->update(['deleted_at' => now()]);
        $report->report_type = 'employee_headcount_branch';
        $this->assertEqualsCanonicalizing([['North', 1], ['No Branch', 1]], $report->getReportRowsProperty()->all());
    }

    public function test_dtr_rankings_aggregate_finalized_entries_without_per_employee_queries(): void
    {
        DB::table('dtrs')->insert([
            ['payroll_period_id' => 1, 'branch_id' => 1, 'fingerprint_id' => '100', 'late' => 10, 'schedule_type' => null, 'is_imported' => 0, 'date_in' => '2026-10-02'],
            ['payroll_period_id' => 1, 'branch_id' => 1, 'fingerprint_id' => '100', 'late' => 5, 'schedule_type' => null, 'is_imported' => 0, 'date_in' => '2026-10-03'],
            ['payroll_period_id' => 1, 'branch_id' => 2, 'fingerprint_id' => '200', 'late' => 7, 'schedule_type' => null, 'is_imported' => 0, 'date_in' => '2026-10-02'],
            ['payroll_period_id' => 1, 'branch_id' => 1, 'fingerprint_id' => '100', 'late' => 100, 'schedule_type' => 'Forgot to punch', 'is_imported' => 0, 'date_in' => '2026-10-02'],
            ['payroll_period_id' => 1, 'branch_id' => 1, 'fingerprint_id' => '100', 'late' => 90, 'schedule_type' => null, 'is_imported' => 1, 'date_in' => '2026-10-02'],
            ['payroll_period_id' => 1, 'branch_id' => 1, 'fingerprint_id' => '300', 'late' => 50, 'schedule_type' => null, 'is_imported' => 0, 'date_in' => '2026-10-02'],
        ]);

        $report = app(Reports::class);
        $report->report_type = 'dtr_late';
        $report->payroll_period_id = '1';

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertSame([[1, 'Able, Ana', 'North', 15.0], [2, 'Baker, Ben', 'South', 7.0]], $report->getReportRowsProperty()->all());
        $this->assertLessThanOrEqual(4, count(DB::getQueryLog()));

        $report->branch_id = '2';
        $this->assertSame([[1, 'Baker, Ben', 'South', 7.0]], $report->getReportRowsProperty()->all());

        $report->branch_id = 'all';
        $report->employee_id = '1';
        $report->date_from = '2026-10-03';
        $report->date_to = '2026-10-03';
        $this->assertSame([[1, 'Able, Ana', 'North', 5.0]], $report->getReportRowsProperty()->all());
    }

    public function test_memo_and_activity_month_filters_and_empty_results(): void
    {
        DB::table('memos')->insert([
            ['employee_id' => 1, 'created_at' => '2026-10-04 12:00:00', 'updated_at' => '2026-10-04 12:00:00'],
            ['employee_id' => 1, 'created_at' => '2026-10-05 12:00:00', 'updated_at' => '2026-10-05 12:00:00'],
            ['employee_id' => 2, 'created_at' => '2026-10-05 12:00:00', 'updated_at' => '2026-10-05 12:00:00'],
            ['employee_id' => 3, 'created_at' => '2026-10-05 12:00:00', 'updated_at' => '2026-10-05 12:00:00'],
        ]);
        DB::table('activities')->insert(['title' => 'Workshop', 'date_from' => '2026-09-30', 'date_to' => '2026-10-02']);

        $report = app(Reports::class);
        $report->month = '2026-10';
        $report->report_type = 'memo_month';
        $this->assertSame([[1, 'Able, Ana', 2], [2, 'Baker, Ben', 1]], $report->getReportRowsProperty()->all());

        $report->report_type = 'activities_month';
        $this->assertCount(1, $report->getReportRowsProperty());

        $report->month = '2020-01';
        $this->assertSame([], $report->getReportRowsProperty()->all());
    }

    public function test_preview_is_generated_once_and_invalidated_when_filters_change(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('hr'));

        Livewire::test(Reports::class)
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertSet('previewCount', 2)
            ->assertSee('North')
            ->set('branch_id', '1')
            ->assertSee('Filters changed')
            ->call('generateReport')
            ->assertSet('previewCount', 1);
    }

    public function test_large_employee_preview_is_limited_but_download_rows_are_complete(): void
    {
        $users = [];
        $employees = [];

        for ($id = 5; $id <= 109; $id++) {
            $users[] = ['id' => $id, 'role' => 'employee'];
            $employees[] = [
                'id' => $id,
                'user_id' => $id,
                'uid' => $id,
                'firstname' => 'Test',
                'lastname' => 'Worker '.$id,
                'employment_type' => 'Permanent',
                'branch_id' => 1,
                'department_id' => 1,
                'designation_id' => 1,
                'fingerprint_id' => (string) $id,
            ];
        }

        DB::table('users')->insert($users);
        DB::table('employees')->insert($employees);
        Filament::setCurrentPanel(Filament::getPanel('hr'));

        Livewire::test(Reports::class)
            ->set('report_type', 'employee_all')
            ->call('generateReport')
            ->assertHasNoErrors()
            ->assertSet('previewCount', 107)
            ->assertSet('previewRows', fn (array $rows): bool => count($rows) === 100);

        $report = app(Reports::class);
        $report->report_type = 'employee_all';
        $this->assertSame($report->getReportRowsProperty()->all(), $report->downloadRows()->all());
    }

    public function test_print_route_uses_the_same_branch_and_employee_filters(): void
    {
        $this->actingAs(User::query()->findOrFail(4));

        $this->get(route('hr_tools.reports.print', [
            'report_type' => 'employee_all',
            'branch_id' => '2',
            'employee_id' => '2',
        ]))
            ->assertOk()
            ->assertSee('Baker, Ben')
            ->assertDontSee('Able, Ana');
    }

    public function test_excel_export_downloads_only_after_generation(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('hr'));

        Livewire::test(Reports::class)
            ->call('generateReport')
            ->call('exportExcel')
            ->assertFileDownloaded();
    }
}
