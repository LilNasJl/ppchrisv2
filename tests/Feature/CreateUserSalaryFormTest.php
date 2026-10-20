<?php

namespace Tests\Feature;

use App\Filament\Pages\EmployeeDetails;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateUserSalaryFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::query()->where('username', 'masteradmin')->first()
            ?? User::withoutEvents(fn (): User => User::query()->create([
                'name' => 'Master Admin',
                'username' => 'masteradmin',
                'email' => 'masteradmin@example.test',
                'password' => 'password',
                'role' => 'admin',
            ]));

        Filament::setCurrentPanel(Filament::getPanel('hr'));
        Filament::auth()->setUser($admin);
        $this->actingAs($admin);
    }

    public function test_monthly_salary_and_optional_government_ids_are_saved(): void
    {
        Livewire::test(CreateUser::class)
            ->assertSee('Return')
            ->fillForm($this->formData([
                'rate_type' => 'monthly',
                'payment_type' => 'atm',
                'monthly_rate' => 26000,
                'allowance' => 1200,
                'gsis' => 'GSIS A-1',
                'philhealth' => 'PH-77',
                'pagibig' => 'MID 123',
                'tin' => 'TIN/A',
                'sss' => 'SSS 9',
            ]))
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(EmployeeDetails::getUrl());

        $employee = Employee::query()->where('uid', '9001')->firstOrFail();

        $this->assertSame('regular', $employee->schedule_type);
        $this->assertSame('monthly', $employee->rate_type);
        $this->assertSame('atm', $employee->payment_type);
        $this->assertSame('26000.00', $employee->monthly_rate);
        $this->assertSame('1000.00', $employee->daily_rate);
        $this->assertSame('1200.00', $employee->allowance);
        $this->assertSame('GSIS A-1', $employee->gsis);
        $this->assertSame('PH-77', $employee->philhealth);
        $this->assertSame('MID 123', $employee->pagibig);
        $this->assertSame('TIN/A', $employee->tin);
        $this->assertSame('SSS 9', $employee->sss);
    }

    public function test_daily_salary_can_be_saved_without_government_ids(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm($this->formData([
                'rate_type' => 'daily',
                'payment_type' => 'cash',
                'daily_rate' => 850,
            ]))
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(EmployeeDetails::getUrl());

        $employee = Employee::query()->where('uid', '9001')->firstOrFail();

        $this->assertSame('daily', $employee->schedule_type);
        $this->assertSame('850.00', $employee->daily_rate);
        $this->assertNull($employee->monthly_rate);
        $this->assertSame('0.00', $employee->allowance);
        $this->assertNull($employee->gsis);
        $this->assertNull($employee->philhealth);
    }

    public function test_legacy_account_list_redirects_to_master_data(): void
    {
        $this->get('/hr/users')->assertRedirect(EmployeeDetails::getUrl());
    }

    public function test_create_user_page_renders_with_enhanced_layout_and_return_url(): void
    {
        $customReturn = url('/hr/employee-details?branchId=br-123');

        $this->get('/hr/users/create?returnUrl='.urlencode($customReturn))
            ->assertOk()
            ->assertSee('New Employee Account')
            ->assertSee('Personal Information')
            ->assertSee('Work Assignment & Designation')
            ->assertSee('Compensation & Payroll')
            ->assertSee('Statutory & Government Identification')
            ->assertSee('Account Credentials')
            ->assertSee('Create Employee Account');

        Livewire::withQueryParams(['returnUrl' => $customReturn])
            ->test(CreateUser::class)
            ->fillForm($this->formData([
                'rate_type' => 'daily',
                'payment_type' => 'cash',
                'daily_rate' => 750,
            ]))
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect($customReturn);
    }

    public function test_edit_user_page_renders_with_enhanced_layout(): void
    {
        $branch = Branch::query()->create([
            'branch_name' => 'Edit Branch',
            'branch_address' => 'Test',
            'mobile_no' => '0',
            'employee_id' => 0,
        ]);
        $department = Department::query()->create(['name' => 'Edit Department']);
        $designation = Designation::query()->create(['title' => 'Edit Designation']);

        $user = User::factory()->create([
            'name' => 'Existing User',
            'username' => 'PF9002',
            'role' => 'employee',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'uid' => '9002',
            'firstname' => 'Existing',
            'lastname' => 'Employee',
            'gender' => 'male',
            'birthdate' => '1990-05-10',
            'hired_date' => '2025-01-01',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'rate_type' => 'monthly',
            'monthly_rate' => 30000,
            'daily_rate' => 1153.85,
            'payment_type' => 'atm',
        ]);

        $this->get('/hr/users/'.$user->uuid.'/edit')
            ->assertOk()
            ->assertSee('Personal Information')
            ->assertSee('Work Assignment & Designation')
            ->assertSee('Compensation & Payroll');
    }

    private function formData(array $salaryAndIds): array
    {
        $branch = Branch::query()->create([
            'branch_name' => 'Test Branch',
            'branch_address' => 'Test',
            'mobile_no' => '0',
            'employee_id' => 0,
        ]);
        $department = Department::query()->create(['name' => 'Test Department']);
        $designation = Designation::query()->create(['title' => 'Test Designation']);

        return [
            'employee' => array_merge([
                'uid' => '9001',
                'firstname' => 'Sample',
                'lastname' => 'Employee',
                'birthdate' => '1995-01-01',
                'hired_date' => '2026-01-01',
                'designation_id' => $designation->id,
                'branch_id' => $branch->id,
                'department_id' => $department->id,
            ], $salaryAndIds),
        ];
    }
}
