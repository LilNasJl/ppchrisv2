<?php

namespace Tests\Feature;

use App\Filament\Pages\PayrollProcessingSummary;
use App\Models\Branch;
use App\Models\Department;
use App\Models\PayrollPeriod;
use App\Models\PayrollSnapshot;
use App\Models\PayrollSummaryScope;
use App\Models\User;
use App\Services\PayrollCalculator;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_payroll_can_be_selected_by_branch_department_or_all(): void
    {
        [$period, $main, $station, $operations] = $this->snapshotScenario();
        $calculator = app(PayrollCalculator::class);

        $this->assertCount(2, $calculator->rowsForScope($period, 'all'));
        $this->assertCount(1, $calculator->rowsForScope($period, 'branch', $main->id));
        $this->assertCount(2, $calculator->rowsForScope($period, 'department', $operations->id));
        $this->assertCount(0, $calculator->rowsForScope($period, 'branch', 999));
        $this->assertSame(300.0, (float) $calculator->rowsForScope($period, 'all')->sum('net_pay'));
    }

    public function test_older_locked_snapshots_resolve_an_unambiguous_department_name_without_changing_pay(): void
    {
        [$period, , , $operations] = $this->snapshotScenario();
        foreach (PayrollSnapshot::query()->where('payroll_period_id', $period->id)->get() as $snapshot) {
            $data = $snapshot->data;
            unset($data['department_id']);
            $snapshot->update(['data' => $data]);
        }

        $rows = app(PayrollCalculator::class)->rowsForScope($period, 'department', $operations->id);
        $this->assertCount(2, $rows);
        $this->assertSame(300.0, (float) $rows->sum('net_pay'));
        $this->assertSame(300.0, (float) app(PayrollCalculator::class)
            ->summaryRows($period, 'department', [$operations->id])->sole()['net_pay']);
        foreach (PayrollSnapshot::query()->where('payroll_period_id', $period->id)->get() as $snapshot) {
            $this->assertArrayNotHasKey('department_id', $snapshot->data);
        }
    }

    public function test_summary_inclusion_uses_group_ids_and_saved_period_settings(): void
    {
        [$period, $main, $station, $operations] = $this->snapshotScenario();
        PayrollSummaryScope::query()->create([
            'payroll_period_id' => $period->id,
            'group_by' => 'branch',
            'included_ids' => [$main->id],
        ]);
        $scope = PayrollSummaryScope::query()->where('payroll_period_id', $period->id)->firstOrFail();
        $calculator = app(PayrollCalculator::class);

        $this->assertCount(1, $calculator->summaryRows($period, $scope->group_by, $scope->included_ids));
        $this->assertSame('Main Office', $calculator->summaryRows($period, 'branch', [$main->id])->sole()['branch']);
        $this->assertSame(2, $calculator->summaryRows($period, 'department', [$operations->id])->sole()['workers']);
        $this->assertSame('DEPARTMENTS', $calculator->summaryHeaders('department')['branch']);
    }

    public function test_payroll_pages_and_print_routes_render_for_department_scope(): void
    {
        [$period, $main, $station, $operations] = $this->snapshotScenario();
        $period->update(['is_locked' => false]);
        $user = User::query()->where('username', 'masteradmin')->first()
            ?? User::withoutEvents(fn (): User => User::query()->create([
                'name' => 'Master Admin', 'username' => 'masteradmin',
                'email' => 'masteradmin@example.test', 'password' => 'password',
                'role' => 'admin',
            ]));
        $this->actingAs($user);

        $this->get('/hr/payroll?periodId='.$period->publicKey())
            ->assertSuccessful()
            ->assertSee('Payroll Branch/Department')
            ->assertSee('/hr/payroll-processing-summary');
        $this->get('/hr/payroll-by-branch?scope=department&departmentId='.$operations->id)
            ->assertSuccessful();
        $this->get('/hr/payroll-summary?periodId='.$period->publicKey())
            ->assertSuccessful()
            ->assertSee('hr-under-construction.html')
            ->assertDontSee('Summary Filter Settings');
        $this->get('/hr/payroll-processing-summary?periodId='.$period->publicKey())
            ->assertSuccessful()
            ->assertSee('Summary Filter Settings')
            ->assertSee('Return')
            ->assertSee('/hr/payroll');
        $this->get(route('hr_tools.payroll_by_branch.print', [
            'period_id' => $period->id,
            'scope' => 'department',
            'department_id' => $operations->id,
        ]))->assertSuccessful()->assertSee('Operations');
        $this->get(route('hr_tools.payroll_by_branch.print', [
            'period_id' => $period->id,
            'scope' => 'branch',
            'branch_id' => $main->id,
        ]))->assertSuccessful()->assertSee('Main Office');
        $this->get(route('hr_tools.payroll_by_branch.print', [
            'period_id' => $period->id,
            'scope' => 'all',
        ]))->assertSuccessful()->assertSee('All Branches');
        $this->get(route('hr_tools.payroll_summary.print', ['period_id' => $period->id]))
            ->assertSuccessful();
    }

    public function test_summary_settings_can_be_saved_for_an_open_period(): void
    {
        [$period, $main, $station, $operations] = $this->snapshotScenario();
        $period->update(['is_locked' => false]);
        $user = User::query()->where('username', 'masteradmin')->firstOrFail();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('hr'));

        Livewire::withQueryParams(['periodId' => $period->publicKey()])
            ->test(PayrollProcessingSummary::class)
            ->set('group_by', 'department')
            ->set('included_ids', [$operations->id])
            ->call('saveSummarySettings')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payroll_summary_scopes', [
            'payroll_period_id' => $period->id,
            'group_by' => 'department',
        ]);
        $this->assertSame([$operations->id], PayrollSummaryScope::query()
            ->where('payroll_period_id', $period->id)->firstOrFail()->included_ids);
        $this->get(route('hr_tools.payroll_summary.print', ['period_id' => $period->id]))
            ->assertSuccessful()->assertSee('DEPARTMENTS');
    }

    public function test_simultaneous_branch_and_department_selection_prioritizes_department_and_prevents_duplicates(): void
    {
        $period = PayrollPeriod::query()->create([
            'title' => 'Nov 1 - 15, 2026',
            'date_start' => '2026-11-01',
            'date_end' => '2026-11-15',
            'date_payout' => '2026-11-20',
            'is_locked' => true,
        ]);

        $branchA = $this->branch('Branch A');
        $branchB = $this->branch('Branch B');
        $deptB = Department::query()->create(['name' => 'Department B']);
        $deptC = Department::query()->create(['name' => 'Department C']);

        // Employee 101: Assigned to BOTH Branch A and Department B (overlap!)
        PayrollSnapshot::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branchA->id,
            'row_number' => 1,
            'data' => [
                'number' => 1,
                'employee_id' => 101,
                'name' => 'Same Name',
                'branch_id' => $branchA->id,
                'branch' => 'Branch A',
                'department_id' => $deptB->id,
                'department' => 'Department B',
                'gross_pay' => 1000.0,
                'total_deductions' => 100.0,
                'net_pay' => 900.0,
            ],
        ]);

        // Accidental duplicate snapshot row for Employee 101 to verify unique employee_id enforcement
        PayrollSnapshot::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branchA->id,
            'row_number' => 2,
            'data' => [
                'number' => 2,
                'employee_id' => 101,
                'name' => 'Same Name',
                'branch_id' => $branchA->id,
                'branch' => 'Branch A',
                'department_id' => $deptB->id,
                'department' => 'Department B',
                'gross_pay' => 1000.0,
                'total_deductions' => 100.0,
                'net_pay' => 900.0,
            ],
        ]);

        // Employee 102: Assigned to Branch A and Department C (not in selected Dept B, so stays under Branch A)
        // Also has the exact same display name to verify deduplication uses employee_id, NOT name.
        PayrollSnapshot::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branchA->id,
            'row_number' => 3,
            'data' => [
                'number' => 3,
                'employee_id' => 102,
                'name' => 'Same Name',
                'branch_id' => $branchA->id,
                'branch' => 'Branch A',
                'department_id' => $deptC->id,
                'department' => 'Department C',
                'gross_pay' => 2000.0,
                'total_deductions' => 200.0,
                'net_pay' => 1800.0,
            ],
        ]);

        // Employee 103: Assigned to Branch B and Department B (matches selected Dept B)
        PayrollSnapshot::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branchB->id,
            'row_number' => 4,
            'data' => [
                'number' => 4,
                'employee_id' => 103,
                'name' => 'Employee Three',
                'branch_id' => $branchB->id,
                'branch' => 'Branch B',
                'department_id' => $deptB->id,
                'department' => 'Department B',
                'gross_pay' => 1500.0,
                'total_deductions' => 150.0,
                'net_pay' => 1350.0,
            ],
        ]);

        $calculator = app(PayrollCalculator::class);

        // Select Branch A + Department B simultaneously
        $summary = $calculator->summaryRows(
            $period,
            'branch',
            [],
            [$branchA->id],
            [$deptB->id],
        );

        // Should have 2 groups: Department B first, then Branch A
        $this->assertCount(2, $summary);

        $deptGroup = $summary->firstWhere('group_type', 'department');
        $branchGroup = $summary->firstWhere('group_type', 'branch');

        $this->assertNotNull($deptGroup);
        $this->assertNotNull($branchGroup);

        // Department B includes Employee 101 (once!) + Employee 103 = 2 workers, net pay 900 + 1350 = 2250
        $this->assertSame('Department B', $deptGroup['branch']);
        $this->assertSame(2, $deptGroup['workers']);
        $this->assertSame(2500.0, $deptGroup['gross_pay']);
        $this->assertSame(250.0, $deptGroup['total_deductions']);
        $this->assertSame(2250.0, $deptGroup['net_pay']);

        // Branch A excludes Employee 101 (already prioritized to Department B) and keeps only Employee 102
        $this->assertSame('Branch A', $branchGroup['branch']);
        $this->assertSame(1, $branchGroup['workers']);
        $this->assertSame(2000.0, $branchGroup['gross_pay']);
        $this->assertSame(200.0, $branchGroup['total_deductions']);
        $this->assertSame(1800.0, $branchGroup['net_pay']);

        // Grand totals across the summary equal the 3 unique employees (101, 102, 103)
        $this->assertSame(3, $summary->sum('workers'));
        $this->assertSame(4500.0, (float) $summary->sum('gross_pay'));
        $this->assertSame(450.0, (float) $summary->sum('total_deductions'));
        $this->assertSame(4050.0, (float) $summary->sum('net_pay'));
    }

    public function test_combined_branch_and_department_settings_can_be_saved_and_printed(): void
    {
        [$period, $main, $station, $operations] = $this->snapshotScenario();
        $period->update(['is_locked' => false]);
        $user = User::query()->where('username', 'masteradmin')->first()
            ?? User::withoutEvents(fn (): User => User::query()->create([
                'name' => 'Master Admin', 'username' => 'masteradmin',
                'email' => 'masteradmin@example.test', 'password' => 'password',
                'role' => 'admin',
            ]));
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('hr'));

        Livewire::withQueryParams(['periodId' => $period->publicKey()])
            ->test(PayrollProcessingSummary::class)
            ->set('branch_ids', [$main->id])
            ->set('department_ids', [$operations->id])
            ->call('saveSummarySettings')
            ->assertHasNoErrors();

        $scope = PayrollSummaryScope::query()->where('payroll_period_id', $period->id)->firstOrFail();
        $this->assertSame([$main->id], $scope->resolvedBranchIds());
        $this->assertSame([$operations->id], $scope->resolvedDepartmentIds());

        $period->update(['is_locked' => true]);

        $this->get(route('hr_tools.payroll_summary.print', ['period_id' => $period->id]))
            ->assertSuccessful()
            ->assertSee('DEPARTMENTS / BRANCHES')
            ->assertSee('Operations');
    }

    public function test_selected_branch_and_department_items_are_excluded_from_dropdown_options(): void
    {
        [$period, $main, $station, $operations] = $this->snapshotScenario();
        $period->update(['is_locked' => false]);
        $finance = Department::query()->create(['name' => 'Finance']);

        $user = User::query()->where('username', 'masteradmin')->firstOrFail();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('hr'));

        $testable = Livewire::withQueryParams(['periodId' => $period->publicKey()])
            ->test(PayrollProcessingSummary::class);

        /** @var PayrollProcessingSummary $page */
        $page = $testable->instance();

        $branchField = $page->getForm('form')->getComponent('branch_ids');
        $departmentField = $page->getForm('form')->getComponent('department_ids');

        // Initially without selection, all branches and departments are available
        $this->assertArrayHasKey((string) $main->id, $branchField->getOptions());
        $this->assertArrayHasKey((string) $station->id, $branchField->getOptions());
        $this->assertArrayHasKey((string) $operations->id, $departmentField->getOptions());
        $this->assertArrayHasKey((string) $finance->id, $departmentField->getOptions());

        // When $main branch is selected, it must NOT be available in the branch dropdown options
        $testable->set('branch_ids', [(string) $main->id]);
        $branchOptions = $testable->instance()->getForm('form')->getComponent('branch_ids')->getOptions();
        $this->assertArrayNotHasKey((string) $main->id, $branchOptions);
        $this->assertArrayHasKey((string) $station->id, $branchOptions);

        // When $operations department is selected, it must NOT be available in the department dropdown options
        $testable->set('department_ids', [(string) $operations->id]);
        $departmentOptions = $testable->instance()->getForm('form')->getComponent('department_ids')->getOptions();
        $this->assertArrayNotHasKey((string) $operations->id, $departmentOptions);
        $this->assertArrayHasKey((string) $finance->id, $departmentOptions);

        // When selections are cleared, both items become available in the dropdown lists again
        $testable->call('clearFilterSelection');
        $this->assertArrayHasKey((string) $main->id, $testable->instance()->getForm('form')->getComponent('branch_ids')->getOptions());
        $this->assertArrayHasKey((string) $operations->id, $testable->instance()->getForm('form')->getComponent('department_ids')->getOptions());
    }

    public function test_summary_rows_include_employee_breakdown_and_render_in_dropdown_subtable(): void
    {
        [$period, $main, , $operations] = $this->snapshotScenario();
        $calculator = app(PayrollCalculator::class);

        $rows = $calculator->summaryRows($period, 'branch', [$main->id]);
        $mainRow = $rows->firstWhere('branch', 'Main Office');

        $this->assertNotNull($mainRow);
        $this->assertArrayHasKey('employees', $mainRow);
        $this->assertCount(1, $mainRow['employees']);
        $this->assertSame(100.0, (float) $mainRow['employees'][0]['gross_pay']);
        $this->assertSame(100.0, (float) $mainRow['employees'][0]['net_pay']);

        $user = User::query()->where('username', 'masteradmin')->firstOrFail();
        $this->actingAs($user);

        $response = $this->get('/hr/payroll-processing-summary?periodId='.$period->publicKey());
        $response->assertSuccessful();
        $response->assertSee('summary-clickable-group');
        $response->assertSee('summary-employee-subtable');
        $response->assertSee('Employees in');
    }

    private function snapshotScenario(): array
    {
        $period = PayrollPeriod::query()->create([
            'title' => 'Oct 11 - 25, 2026', 'date_start' => '2026-10-11',
            'date_end' => '2026-10-25', 'date_payout' => '2026-10-30',
            'is_locked' => true,
        ]);
        $main = $this->branch('Main Office');
        $station = $this->branch('Maco');
        $operations = Department::query()->create(['name' => 'Operations']);
        foreach ([[$main, 100.0], [$station, 200.0]] as $index => [$branch, $net]) {
            PayrollSnapshot::query()->create([
                'payroll_period_id' => $period->id,
                'branch_id' => $branch->id,
                'row_number' => $index + 1,
                'data' => [
                    'number' => $index + 1, 'branch_id' => $branch->id,
                    'branch' => $branch->branch_name, 'department_id' => $operations->id,
                    'department' => 'Operations', 'net_pay' => $net,
                    'gross_pay' => $net, 'total_deductions' => 0,
                ],
            ]);
        }

        return [$period, $main, $station, $operations];
    }

    private function branch(string $name): Branch
    {
        return Branch::query()->create([
            'branch_name' => $name, 'branch_address' => $name,
            'mobile_no' => '09123456789', 'employee_id' => 0,
            'no_of_shifts' => 1, 'reg_sched_start' => '08:00:00',
            'reg_sched_end' => '18:00:00', 'is_24hrs' => false,
            'has_broken_time' => false,
        ]);
    }
}
