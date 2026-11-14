<?php

namespace Tests\Feature;

use App\Filament\Employee\Pages\Payroll as EmployeePayroll;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeePayrollPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('employee'));
    }

    public function test_employee_payroll_page_renders_unified_topbar_and_table_header(): void
    {
        $branch = Branch::query()->create(['branch_name' => 'Petrofil Main', 'branch_address' => 'Station Road']);
        $dept = Department::query()->create(['name' => 'Operations']);
        $desig = Designation::query()->create(['title' => 'Station Attendant']);

        $employee = Employee::query()->create([
            'firstname' => 'Juan',
            'lastname' => 'Dela Cruz',
            'branch_id' => $branch->id,
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'daily_rate' => 500,
            'schedule_type' => 'regular',
            'employment_type' => 'Permanent',
            'employment_status' => 'Regular',
        ]);

        $user = User::query()->create([
            'name' => 'Juan Dela Cruz',
            'role' => 'employee',
            'employee_id' => $employee->id,
            'can_view_payroll' => true,
            'password' => 'secret',
        ]);
        $employee->update(['user_id' => $user->id]);

        $period = PayrollPeriod::query()->create([
            'title' => 'Nov 11 - 25, 2026',
            'date_start' => '2026-11-11',
            'date_end' => '2026-11-25',
            'date_payout' => '2026-11-30',
            'is_locked' => true,
        ]);

        $this->actingAs($user, 'web');

        Livewire::withQueryParams(['periodId' => $period->publicKey()])
            ->test(EmployeePayroll::class)
            ->assertSuccessful()
            ->assertSee('WORKFORCE RECORDS')
            ->assertSee('Payroll Summary')
            ->assertDontSee('View your finalized payroll and compensation breakdown by period.')
            ->assertDontSee('Employee Information Summary')
            ->assertSee('Nov 11 - 25, 2026');
    }
}
