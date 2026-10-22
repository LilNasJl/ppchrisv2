<?php

namespace Tests\Feature;

use App\Filament\Employee\Pages\Dtr as EmployeeDtr;
use App\Filament\Employee\Widgets\StationDtrManageTable;
use App\Filament\Pages\Dtr as HrDtr;
use App\Filament\Pages\DtrManage;
use App\Filament\Pages\SaturdayWfhPolicy as SaturdayWfhPolicyPage;
use App\Filament\Widgets\DtrManageTable;
use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\Holiday;
use App\Models\HolidayType;
use App\Models\Leave;
use App\Models\PayrollPeriod;
use App\Models\SaturdayOnSiteExemption;
use App\Models\User;
use App\Services\DtrAttendanceUnitService;
use App\Services\Imports\DtrImportService;
use App\Services\Imports\EmployeeVisibleDtrImportService;
use App\Services\PayrollCalculator;
use App\Services\SaturdayWfhDtrService;
use App\Services\SaturdayWfhPolicyService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SaturdayWfhPolicyTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Employee $employee;

    private PayrollPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-09 09:00:00', 'Asia/Manila'));
        $this->branch = Branch::query()->create([
            'branch_name' => 'Saturday WFH test', 'branch_address' => 'Test', 'mobile_no' => '09123456789',
            'employee_id' => 0, 'no_of_shifts' => 1, 'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '18:00:00', 'is_24hrs' => false, 'has_broken_time' => false,
        ]);
        $this->employee = Employee::query()->create([
            'uid' => '0024', 'firstname' => 'Saturday', 'lastname' => 'Employee',
            'branch_id' => $this->branch->id, 'fingerprint_id' => '654321',
            'hired_date' => '2026-09-01', 'employment_type' => 'Permanent',
            'rate_type' => 'monthly', 'monthly_rate' => 20800,
        ]);
        $this->period = PayrollPeriod::query()->create([
            'title' => 'October WFH test', 'date_start' => '2026-10-01',
            'date_end' => '2026-10-15', 'date_payout' => '2026-10-20', 'is_locked' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function importers(): array
    {
        return ['HR' => [DtrImportService::class], 'SIC/RC' => [EmployeeVisibleDtrImportService::class]];
    }

    public function test_policy_is_managed_on_a_dedicated_hr_dtr_page(): void
    {
        $master = User::query()->where('username', 'masteradmin')->firstOrFail();

        $this->actingAs($master)->get(HrDtr::getUrl())
            ->assertOk()
            ->assertSee('Saturday WFH Policy');
        $page = Livewire::actingAs($master)->test(SaturdayWfhPolicyPage::class)
            ->assertSuccessful()
            ->assertDontSee('Date Effective')
            ->assertSee('Saturday WFH Settings')
            ->assertSee('WFH Disabled')
            ->assertSee('Add Saturday On-site')
            ->assertSee('No employees assigned on-site Saturdays')
            ->assertSee('Return to DTR')
            ->callAction('policySettings', [
                'enabled' => true,
                'password' => (string) (config('auth.master_admin.password') ?: 'masteradmin'),
            ])
            ->assertHasNoErrors();

        $this->assertSame(1, substr_count(strip_tags($page->html()), 'Return to DTR'));
        $this->assertSame(1, substr_count(strip_tags($page->html()), 'Add Saturday On-site'));

        $this->assertSame('2026-10-09', app(SaturdayWfhPolicyService::class)->effectiveFrom());
        app(SaturdayWfhPolicyService::class)->addOnSiteExemption($this->employee, '2026-10-10', null, 'Station duty');
        Livewire::actingAs($master)->test(SaturdayWfhPolicyPage::class)
            ->assertSuccessful()
            ->assertTableColumnExists('row_number')
            ->assertSee('WFH Enabled')
            ->assertSee('Employee, Saturday')
            ->assertDontSee('On-site assignment')
            ->assertDontSee('Reason')
            ->assertSee('Permanent')
            ->assertSee('Return to WFH');
    }

    public function test_enabling_and_disabling_policy_require_the_current_account_password(): void
    {
        $master = User::query()->where('username', 'masteradmin')->firstOrFail();
        $password = (string) (config('auth.master_admin.password') ?: 'masteradmin');

        $page = Livewire::actingAs($master)->test(SaturdayWfhPolicyPage::class);
        $page->callAction('policySettings', ['enabled' => true, 'password' => 'incorrect']);
        $this->assertNull(app(SaturdayWfhPolicyService::class)->effectiveFrom());

        $page = Livewire::actingAs($master)->test(SaturdayWfhPolicyPage::class);
        $page->callAction('policySettings', ['enabled' => true, 'password' => $password])->assertHasNoErrors();
        $this->assertSame('2026-10-09', app(SaturdayWfhPolicyService::class)->effectiveFrom());
        app(SaturdayWfhPolicyService::class)->addOnSiteExemption($this->employee, '2026-10-10', null, null);
        $page = Livewire::actingAs($master)->test(SaturdayWfhPolicyPage::class);
        $page->callAction('policySettings', ['enabled' => false, 'password' => 'incorrect']);
        $this->assertTrue(app(SaturdayWfhPolicyService::class)->isEnabled());

        $page = Livewire::actingAs($master)->test(SaturdayWfhPolicyPage::class);
        $page->callAction('policySettings', ['enabled' => false, 'password' => $password])->assertHasNoErrors();
        $this->assertTrue(app(SaturdayWfhPolicyService::class)->isStopped());
        $page->assertSee('Saturday WFH is disabled')->assertSee('Disabled');
    }

    public function test_stop_and_restart_preserve_historical_saturday_wfh_classification(): void
    {
        $policy = app(SaturdayWfhPolicyService::class);
        $policy->activate('2026-10-10');
        Carbon::setTestNow(Carbon::parse('2026-10-21 09:00:00', 'Asia/Manila'));
        $policy->stop();

        $this->assertTrue($policy->isApprovedWfh($this->employee, '2026-10-10'));
        $this->assertTrue($policy->isApprovedWfh($this->employee, '2026-10-17'));
        $this->assertFalse($policy->isApprovedWfh($this->employee, '2026-10-24'));
        $this->assertTrue($policy->requiresPunch($this->employee, '2026-10-24'));
        $this->assertSame(['2026-10-10'], $policy->completedWfhDates($this->employee, $this->period, collect()));

        $policy->activate('2026-10-31');
        $this->assertFalse($policy->isStopped());
        $this->assertFalse($policy->isApprovedWfh($this->employee, '2026-10-24'));
        $this->assertTrue($policy->isApprovedWfh($this->employee, '2026-10-31'));
    }

    public function test_internal_policy_period_change_keeps_prior_saturdays_unchanged(): void
    {
        $policy = app(SaturdayWfhPolicyService::class);
        $policy->activate('2026-10-10');
        Carbon::setTestNow(Carbon::parse('2026-10-21 09:00:00', 'Asia/Manila'));

        $policy->activate('2026-10-31');

        $this->assertSame('2026-10-31', $policy->effectiveFrom());
        $this->assertTrue($policy->isApprovedWfh($this->employee, '2026-10-17'));
        $this->assertFalse($policy->isApprovedWfh($this->employee, '2026-10-24'));
        $this->assertTrue($policy->isApprovedWfh($this->employee, '2026-10-31'));
    }

    public function test_stopped_policy_disables_new_on_site_assignments(): void
    {
        $policy = app(SaturdayWfhPolicyService::class);
        $policy->activate('2026-10-10');
        $policy->stop();

        $this->expectException(InvalidArgumentException::class);
        $policy->addOnSiteExemption($this->employee, '2026-10-09', null, null);
    }

    public function test_enabling_after_saturday_attendance_closes_does_not_change_that_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));
        $policy = app(SaturdayWfhPolicyService::class);

        $policy->enable();

        $this->assertTrue($policy->isEnabled());
        $this->assertFalse($policy->isApprovedWfh($this->employee, '2026-10-10'));
        $this->assertTrue($policy->isApprovedWfh($this->employee, '2026-10-17'));
    }

    public function test_stopped_policy_no_longer_skips_future_saturday_absence_imports(): void
    {
        $policy = app(SaturdayWfhPolicyService::class);
        $policy->activate('2026-10-10');
        Carbon::setTestNow(Carbon::parse('2026-10-21 09:00:00', 'Asia/Manila'));
        $policy->stop();
        Carbon::setTestNow(Carbon::parse('2026-10-24 11:01:00', 'Asia/Manila'));
        $this->period->update(['date_end' => '2026-10-25']);
        $row = $this->candidate();
        $row['date_in'] = '2026-10-24';
        $row['date_out'] = '2026-10-24';

        $result = app(DtrImportService::class)->importRows([$row], 'Stopped Saturday WFH');

        $this->assertSame(1, $result['successful'], json_encode($result));
        $this->assertSame(180, Dtr::query()->whereDate('date_in', '2026-10-24')->sole()->absence_minutes);
    }

    public function test_hr_can_assign_multiple_employees_today_and_return_one_to_wfh_today(): void
    {
        $master = User::query()->where('username', 'masteradmin')->firstOrFail();
        app(SaturdayWfhPolicyService::class)->activate('2026-10-10');
        $other = Employee::query()->create([
            'uid' => '0025', 'firstname' => 'Second', 'lastname' => 'Employee',
            'branch_id' => $this->branch->id, 'hired_date' => '2026-09-01',
            'employment_type' => 'Permanent', 'rate_type' => 'monthly', 'monthly_rate' => 20800,
        ]);

        $page = Livewire::actingAs($master)->test(SaturdayWfhPolicyPage::class)
            ->callAction('addOnSite', [
                'employee_ids' => [$this->employee->id, $other->id],
            ])->assertHasNoErrors();

        $this->assertSame(2, SaturdayOnSiteExemption::query()->count());
        $assignment = SaturdayOnSiteExemption::query()->where('employee_id', $this->employee->id)->sole();
        $this->assertSame('2026-10-09', $assignment->effective_from->toDateString());
        $this->assertNull($assignment->effective_to);
        $page->assertSee('PF-0024')->assertSee('PF-0025');
        $page->callTableAction('returnToWfh', $assignment)
            ->assertHasNoErrors();
        $this->assertFalse(SaturdayOnSiteExemption::query()->whereKey($assignment->id)->exists());
        $this->assertSame(1, SaturdayOnSiteExemption::query()->where('employee_id', $other->id)->count());
    }

    public function test_employee_cannot_open_hr_saturday_wfh_setup(): void
    {
        $employeeUser = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employeeUser)->get(SaturdayWfhPolicyPage::getUrl())->assertForbidden();
    }

    public function test_multi_employee_assignment_is_atomic_when_one_employee_already_has_an_assignment(): void
    {
        $master = User::query()->where('username', 'masteradmin')->firstOrFail();
        app(SaturdayWfhPolicyService::class)->enable();
        $other = Employee::query()->create([
            'uid' => '0025', 'firstname' => 'Second', 'lastname' => 'Employee',
            'branch_id' => $this->branch->id, 'hired_date' => '2026-09-01',
            'employment_type' => 'Permanent', 'rate_type' => 'monthly', 'monthly_rate' => 20800,
        ]);
        app(SaturdayWfhPolicyService::class)->addOnSiteExemption($other, '2026-10-09', null, null);

        Livewire::actingAs($master)->test(SaturdayWfhPolicyPage::class)
            ->callAction('addOnSite', ['employee_ids' => [$this->employee->id, $other->id]])
            ->assertHasNoErrors();

        $this->assertSame(1, SaturdayOnSiteExemption::query()->count());
        $this->assertFalse(SaturdayOnSiteExemption::query()->where('employee_id', $this->employee->id)->exists());
    }

    public function test_return_to_wfh_today_ends_an_existing_assignment_yesterday(): void
    {
        $master = User::query()->where('username', 'masteradmin')->firstOrFail();
        app(SaturdayWfhPolicyService::class)->enable();
        $assignmentId = app(SaturdayWfhPolicyService::class)
            ->addOnSiteExemption($this->employee, '2026-10-09', null, null);
        Carbon::setTestNow(Carbon::parse('2026-10-11 09:00:00', 'Asia/Manila'));

        Livewire::actingAs($master)->test(SaturdayWfhPolicyPage::class)
            ->callTableAction('returnToWfh', SaturdayOnSiteExemption::query()->findOrFail($assignmentId))
            ->assertHasNoErrors();

        $this->assertSame('2026-10-10', SaturdayOnSiteExemption::query()->findOrFail($assignmentId)->effective_to->toDateString());
    }

    #[DataProvider('importers')]
    public function test_saturday_wfh_candidate_creates_an_editable_dtr(string $importer): void
    {
        app(SaturdayWfhPolicyService::class)->activate('2026-10-10');
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));

        $result = app($importer)->importRows([$this->candidate()], 'Saturday WFH');

        $this->assertSame(0, $result['successful'], json_encode($result));
        $this->assertSame(1, $result['skipped'], json_encode($result));
        $this->assertStringContainsString('Saturday WFH', json_encode($result['notices']));
        $this->assertSame(1, $result['wfh_created']);
        $this->assertSame(1, Dtr::query()->count());
        $this->assertSame(1, EmployeeVisibleDtr::query()->count());
        $this->assertEquals(0, app(PayrollCalculator::class)->row($this->employee, $this->period)['absent']);

        $page = new DtrManage;
        $page->employeeId = $this->employee->id;
        $page->branchId = $this->branch->id;
        $page->periodId = $this->period->id;
        $summary = $page->getSummaryMetrics();
        $this->assertSame(1, $summary['saturday_wfh_days']);
        $this->assertSame(0, $summary['saturday_absent_hours']);
        $this->assertEquals(0.5, $summary['total_days_work']);
        $this->assertSame(180, $summary['credited_work_hrs']);

        $record = Dtr::query()->sole();
        $this->assertSame('2026-10-10', $record->date_in);
        $this->assertSame('WFH', $record->schedule_type);
        $this->assertSame(SaturdayWfhDtrService::SOURCE, $record->entry_source);
        $this->assertSame('08:00:00', $record->schedule_start);
        $this->assertSame('11:00:00', $record->schedule_end);
        $this->assertSame('08:00:00', $record->time_in);
        $this->assertSame('11:00:00', $record->time_out);
        $this->assertEquals(180, $record->credited_work_hrs);
        $this->assertTrue($record->exists);

        $master = User::query()->where('username', 'masteradmin')->firstOrFail();
        $table = Livewire::actingAs($master)->test(DtrManageTable::class, [
            'employeeId' => $this->employee->id,
            'branchId' => $this->branch->id,
            'periodId' => $this->period->id,
        ]);
        $this->assertSame('WFH', $table->instance()->getTableRecords()->first()->schedule_type);
    }

    #[DataProvider('importers')]
    public function test_permanent_on_site_exemption_retains_three_hour_absence(string $importer): void
    {
        $policy = app(SaturdayWfhPolicyService::class);
        $policy->activate('2026-10-10');
        $policy->addOnSiteExemption($this->employee, '2026-10-10', null, 'Station duty');
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));

        $result = app($importer)->importRows([$this->candidate()], 'On-site Saturday');
        $this->assertSame(1, $result['successful'], json_encode($result));
        $this->assertSame(180, Dtr::query()->sole()->absence_minutes);
        $this->assertEquals(300, app(PayrollCalculator::class)->row($this->employee, $this->period)['absent']);
    }

    public function test_date_range_and_permanent_return_to_wfh_do_not_change_past_dates(): void
    {
        $policy = app(SaturdayWfhPolicyService::class);
        $policy->activate('2026-10-10');
        $id = $policy->addOnSiteExemption($this->employee, '2026-10-10', null, 'Station duty');
        $this->assertTrue($policy->requiresPunch($this->employee, '2026-10-10'));
        $policy->endOnSiteExemption($this->employee, $id, '2026-10-18');
        $this->assertTrue($policy->requiresPunch($this->employee, '2026-10-17'));
        $this->assertTrue($policy->isApprovedWfh($this->employee, '2026-10-24'));
        $this->assertSame('2026-10-17', $policy->exemptions($this->employee)[0]->effective_to);
    }

    public function test_overlapping_exemptions_are_rejected(): void
    {
        $policy = app(SaturdayWfhPolicyService::class);
        $policy->activate('2026-10-10');
        $policy->addOnSiteExemption($this->employee, '2026-10-10', '2026-10-17', 'Station duty');
        $this->expectException(InvalidArgumentException::class);
        $policy->addOnSiteExemption($this->employee, '2026-10-17', null, 'Duplicate');
    }

    public function test_policy_cannot_reclassify_a_completed_saturday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));

        $this->expectException(InvalidArgumentException::class);
        app(SaturdayWfhPolicyService::class)->activate('2026-10-10');
    }

    public function test_on_site_exemption_cannot_reclassify_a_completed_saturday(): void
    {
        app(SaturdayWfhPolicyService::class)->activate('2026-10-10');
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));

        $this->expectException(InvalidArgumentException::class);
        app(SaturdayWfhPolicyService::class)->addOnSiteExemption($this->employee, '2026-10-10', null, 'Late change');
    }

    public function test_actual_saturday_punch_prevents_duplicate_wfh_credit(): void
    {
        app(SaturdayWfhPolicyService::class)->activate('2026-10-10');
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));
        $actual = $this->candidate();
        $actual['schedule_type'] = 'Saturday';
        $actual['time_in'] = '08:00:00';
        $actual['time_out'] = '11:00:00';
        unset($actual['absence_candidate'], $actual['entry_source']);
        $this->assertSame(1, app(DtrImportService::class)->importRows([$actual], 'Actual scan')['successful']);

        $page = new DtrManage;
        $page->employeeId = $this->employee->id;
        $page->branchId = $this->branch->id;
        $page->periodId = $this->period->id;
        $summary = $page->getSummaryMetrics();
        $this->assertSame(0, $summary['saturday_wfh_days']);
        $this->assertEquals(0.5, $summary['total_days_work']);
        $this->assertSame(1, Dtr::query()->count());
        $this->assertSame('Saturday', Dtr::query()->sole()->schedule_type);
    }

    #[DataProvider('importers')]
    public function test_repeat_sync_does_not_duplicate_saturday_wfh(string $importer): void
    {
        app(SaturdayWfhPolicyService::class)->activate('2026-10-10');
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));

        app($importer)->importRows([$this->candidate()], 'First Saturday sync');
        $result = app($importer)->importRows([$this->candidate()], 'Repeat Saturday sync');

        $this->assertSame(0, $result['wfh_created']);
        $this->assertSame(1, Dtr::query()->where('entry_source', SaturdayWfhDtrService::SOURCE)->count());
        $this->assertSame(1, EmployeeVisibleDtr::query()->where('entry_source', SaturdayWfhDtrService::SOURCE)->count());
    }

    #[DataProvider('importers')]
    public function test_later_actual_saturday_punch_supersedes_generated_wfh(string $importer): void
    {
        app(SaturdayWfhPolicyService::class)->activate('2026-10-10');
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));
        app($importer)->importRows([$this->candidate()], 'WFH before actual scan');

        $actual = $this->candidate();
        $actual['schedule_type'] = 'Saturday';
        $actual['time_in'] = '08:00:00';
        $actual['time_out'] = '11:00:00';
        unset($actual['absence_candidate'], $actual['entry_source']);
        $result = app($importer)->importRows([$actual], 'Late arriving Saturday scan');

        $this->assertSame(1, $result['successful'], json_encode($result));
        $this->assertSame(1, Dtr::query()->count());
        $this->assertSame(1, EmployeeVisibleDtr::query()->count());
        $this->assertSame('Saturday', Dtr::query()->sole()->schedule_type);
        $this->assertSame(0, Dtr::query()->where('entry_source', SaturdayWfhDtrService::SOURCE)->count());
    }

    public function test_locked_period_does_not_generate_saturday_wfh(): void
    {
        app(SaturdayWfhPolicyService::class)->activate('2026-10-10');
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));
        $this->period->update(['is_locked' => true]);

        $this->assertSame(0, app(SaturdayWfhDtrService::class)->materializeScope($this->period->id, $this->branch->id));
        $this->assertSame(0, Dtr::query()->count());
    }

    #[DataProvider('importers')]
    public function test_locked_wfh_entry_is_not_replaced_by_later_device_punch(string $importer): void
    {
        app(SaturdayWfhPolicyService::class)->activate('2026-10-10');
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));
        app($importer)->importRows([$this->candidate()], 'Saturday WFH');
        DB::table('dtrs')->update(['is_locked' => true]);
        DB::table('employee_visible_dtrs')->update(['is_locked' => true]);

        $actual = $this->candidate();
        $actual['schedule_type'] = 'Saturday';
        $actual['time_in'] = '08:00:00';
        $actual['time_out'] = '11:00:00';
        unset($actual['absence_candidate'], $actual['entry_source']);
        $result = app($importer)->importRows([$actual], 'Saturday scan after lock');

        $this->assertSame(0, $result['successful'], json_encode($result));
        $this->assertSame(1, Dtr::query()->count());
        $this->assertSame(1, EmployeeVisibleDtr::query()->count());
        $this->assertSame(SaturdayWfhDtrService::SOURCE, Dtr::query()->sole()->entry_source);
        $this->assertSame(SaturdayWfhDtrService::SOURCE, EmployeeVisibleDtr::query()->sole()->entry_source);
    }

    #[DataProvider('importers')]
    public function test_weekday_sync_creates_wfh_for_all_eligible_branch_employees(string $importer): void
    {
        app(SaturdayWfhPolicyService::class)->activate('2026-10-10');
        Carbon::setTestNow(Carbon::parse('2026-10-12 12:00:00', 'Asia/Manila'));
        $second = Employee::query()->create([
            'uid' => '0025', 'firstname' => 'Second', 'lastname' => 'Employee',
            'branch_id' => $this->branch->id, 'fingerprint_id' => '654322',
            'hired_date' => '2026-09-01', 'employment_type' => 'Permanent',
            'rate_type' => 'monthly', 'monthly_rate' => 20800,
        ]);
        $daily = Employee::query()->create([
            'uid' => '0026', 'firstname' => 'Daily', 'lastname' => 'Employee',
            'branch_id' => $this->branch->id, 'fingerprint_id' => '654323',
            'hired_date' => '2026-09-01', 'employment_type' => 'Permanent',
            'rate_type' => 'daily', 'daily_rate' => 800,
        ]);

        $weekday = $this->candidate();
        $weekday['date_in'] = '2026-10-12';
        $weekday['date_out'] = '2026-10-12';
        $weekday['time_in'] = '08:00:00';
        $weekday['time_out'] = '18:00:00';
        $weekday['schedule_type'] = 'Regular';
        $weekday['schedule_end'] = '18:00:00';
        $weekday['source_session_id'] = 'WEEKDAY-654321-20261012';
        unset($weekday['absence_candidate'], $weekday['entry_source']);

        $result = app($importer)->importRows([$weekday], 'Weekday sync');

        $this->assertSame(1, $result['successful'], json_encode($result));
        $this->assertSame(2, $result['wfh_created']);
        $this->assertSame(2, Dtr::query()->where('entry_source', SaturdayWfhDtrService::SOURCE)->count());
        $this->assertSame(2, EmployeeVisibleDtr::query()->where('entry_source', SaturdayWfhDtrService::SOURCE)->count());
        $this->assertEquals(0.5, app(DtrAttendanceUnitService::class)->attendanceDays(
            Dtr::query()->where('employee_id', $second->id)->get()
        ));
        $this->assertSame(0, Dtr::query()->where('employee_id', $daily->id)->count());
    }

    public function test_hr_can_edit_wfh_times_without_losing_wfh_label(): void
    {
        app(SaturdayWfhPolicyService::class)->activate('2026-10-10');
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));
        app(DtrImportService::class)->importRows([$this->candidate()], 'Saturday WFH');

        $master = User::query()->where('username', 'masteradmin')->firstOrFail();
        Livewire::actingAs($master)->test(DtrManageTable::class, [
            'employeeId' => $this->employee->id,
            'branchId' => $this->branch->id,
            'periodId' => $this->period->id,
        ])->callTableAction('editDtr', Dtr::query()->sole(), [
            'dtr_type' => 'regular',
            'date_in' => '2026-10-10',
            'time_in' => '08:15:00',
            'date_out' => '2026-10-10',
            'time_out' => '11:00:00',
            'saturday_schedule_start' => '08:00:00',
            'saturday_schedule_end' => '11:00:00',
        ])->assertHasNoErrors();

        $record = Dtr::query()->sole();
        $this->assertSame(SaturdayWfhDtrService::SOURCE, $record->entry_source);
        $this->assertSame('WFH', $record->schedule_type);
        $this->assertSame('08:15:00', $record->time_in);
        $this->assertSame(15, (int) $record->late);
        $this->assertSame(165, (int) $record->credited_work_hrs);
        $this->assertSame('08:15:00', EmployeeVisibleDtr::query()->sole()->time_in);
    }

    public function test_employee_dtr_table_shows_saved_saturday_wfh(): void
    {
        app(SaturdayWfhPolicyService::class)->activate('2026-10-10');
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));
        app(DtrImportService::class)->importRows([$this->candidate()], 'Saturday WFH');
        $user = User::withoutEvents(fn (): User => User::query()->create([
            'name' => 'Saturday employee', 'username' => 'saturday-employee',
            'email' => 'saturday@example.test', 'password' => 'password', 'role' => 'employee',
        ]));
        $this->employee->update(['user_id' => $user->id]);
        Filament::setCurrentPanel(Filament::getPanel('employee'));

        $page = Livewire::actingAs($user)->test(EmployeeDtr::class);
        $this->assertSame('WFH', $page->instance()->getTableRecords()->first()->schedule_type);
        $this->assertSame('WFH', $page->instance()->attendanceStatus($page->instance()->getTableRecords()->first()));
        $page->assertSee('WFH')->assertSee('08:00 AM')->assertSee('11:00 AM');
        $this->assertSame(1, Dtr::query()->count());
    }

    public function test_station_dtr_table_shows_saved_saturday_wfh(): void
    {
        app(SaturdayWfhPolicyService::class)->activate('2026-10-10');
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));
        app(EmployeeVisibleDtrImportService::class)->importRows([$this->candidate()], 'Saturday WFH');
        $user = User::withoutEvents(fn (): User => User::query()->create([
            'name' => 'Station manager', 'username' => 'station-manager',
            'email' => 'station-manager@example.test', 'password' => 'password', 'role' => 'employee',
        ]));
        $this->employee->update(['user_id' => $user->id]);
        Filament::setCurrentPanel(Filament::getPanel('employee'));

        $table = Livewire::actingAs($user)->test(StationDtrManageTable::class, [
            'employeeId' => $this->employee->id,
            'branchId' => $this->branch->id,
            'periodId' => $this->period->id,
        ]);
        $record = $table->instance()->getTableRecords()->first();
        $this->assertSame(SaturdayWfhDtrService::SOURCE, $record->entry_source);
        $this->assertSame('WFH', $record->schedule_type);
        $this->assertTrue($record->exists);
        $this->assertSame(1, EmployeeVisibleDtr::query()->count());
    }

    public function test_overtime_only_does_not_cancel_saturday_wfh_credit(): void
    {
        $policy = app(SaturdayWfhPolicyService::class);
        $policy->activate('2026-10-10');
        Carbon::setTestNow(Carbon::parse('2026-10-10 11:01:00', 'Asia/Manila'));

        $overtime = new Dtr(['date_in' => '2026-10-10', 'schedule_type' => 'Overtime']);
        $this->assertSame(['2026-10-10'], $policy->completedWfhDates($this->employee, $this->period, collect([$overtime])));
    }

    public function test_approved_leave_and_holiday_do_not_gain_duplicate_wfh_credit(): void
    {
        $policy = app(SaturdayWfhPolicyService::class);
        $policy->activate('2026-10-10');
        $this->period->update(['date_end' => '2026-10-25']);
        Leave::query()->create([
            'employee_id' => $this->employee->id, 'leave_type' => 'Vacation Leave',
            'leave_from' => '2026-10-10', 'leave_to' => '2026-10-10',
            'reason' => 'Approved leave', 'status' => 'Approved',
        ]);
        $type = HolidayType::query()->create(['title' => 'Regular Holiday', 'type' => 'regular', 'rate' => 2]);
        Holiday::query()->create([
            'branch_id' => $this->branch->id, 'holiday_type_id' => $type->id,
            'date' => '2026-10-17', 'title' => 'Holiday', 'is_recurring' => false,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-10-24 11:01:00', 'Asia/Manila'));

        $this->assertSame(['2026-10-24'], $policy->completedWfhDates($this->employee, $this->period, collect()));
    }

    public function test_non_regular_employee_cannot_receive_saturday_wfh_exemption(): void
    {
        $this->employee->update(['rate_type' => 'daily', 'daily_rate' => 800]);
        $this->expectException(InvalidArgumentException::class);
        app(SaturdayWfhPolicyService::class)->addOnSiteExemption($this->employee, '2026-10-10', null, 'Irrelevant');
    }

    private function candidate(): array
    {
        return [
            'batch_id' => 'SATURDAY-WFH', 'payroll_period_id' => $this->period->id,
            'branch_id' => $this->branch->id, 'fingerprint_id' => '654321',
            'date_in' => '2026-10-10', 'date_out' => '2026-10-10',
            'time_in' => '', 'time_out' => '', 'schedule_type' => 'Absent',
            'schedule_start' => '08:00:00', 'schedule_end' => '11:00:00',
            'day_part' => 'whole_day', 'entry_source' => 'auto_absence',
            'absence_candidate' => true, 'regular_schedule' => 'Regular Sched 1',
            'source_session_id' => 'ABS-654321-20261010-w',
        ];
    }
}
