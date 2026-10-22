<?php

namespace Tests\Feature;

use App\Filament\Widgets\EmployeeDetailsTable;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeDetailsDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_account_can_be_soft_deleted_and_restored_from_master_data(): void
    {
        [$user, $employee] = $this->employeeAccount();
        Filament::setCurrentPanel(Filament::getPanel('hr'));
        $admin = User::query()->where('username', 'masteradmin')->firstOrFail();

        $table = Livewire::actingAs($admin)->test(EmployeeDetailsTable::class)
            ->assertTableActionExists('delete')
            ->callTableAction('delete', $user)
            ->assertHasNoErrors();

        $this->assertTrue(User::withTrashed()->findOrFail($user->id)->trashed());
        $this->assertTrue(Employee::withTrashed()->findOrFail($employee->id)->trashed());

        $table->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$user])
            ->callTableAction('restore', $user)
            ->assertHasNoErrors();

        $this->assertFalse(User::withTrashed()->findOrFail($user->id)->trashed());
        $this->assertFalse(Employee::withTrashed()->findOrFail($employee->id)->trashed());
    }

    public function test_force_delete_is_available_for_a_trashed_employee_account(): void
    {
        [$user, $employee] = $this->employeeAccount();
        Filament::setCurrentPanel(Filament::getPanel('hr'));
        $admin = User::query()->where('username', 'masteradmin')->firstOrFail();

        Livewire::actingAs($admin)->test(EmployeeDetailsTable::class)
            ->callTableAction('delete', $user)
            ->filterTable('trashed', false)
            ->callTableAction('forceDelete', $user)
            ->assertHasNoErrors();

        $this->assertNull(User::withTrashed()->find($user->id));
        $this->assertNull(Employee::withTrashed()->find($employee->id));
    }

    public function test_employee_delete_actions_require_user_permissions(): void
    {
        [$user, $employee] = $this->employeeAccount();
        Filament::setCurrentPanel(Filament::getPanel('hr'));
        $hr = User::factory()->create(['role' => 'hr']);

        Livewire::actingAs($hr)->test(EmployeeDetailsTable::class)
            ->assertTableActionHidden('delete', $user)
            ->assertTableActionHidden('forceDelete', $user);

        $this->assertFalse(User::withTrashed()->findOrFail($user->id)->trashed());
        $this->assertFalse(Employee::withTrashed()->findOrFail($employee->id)->trashed());
    }

    /** @return array{User, Employee} */
    private function employeeAccount(): array
    {
        $branch = Branch::query()->create([
            'branch_name' => 'Deletion test branch', 'branch_address' => 'Test',
            'mobile_no' => '09123456789', 'employee_id' => 0, 'no_of_shifts' => 1,
            'reg_sched_start' => '08:00:00', 'reg_sched_end' => '18:00:00',
            'is_24hrs' => false, 'has_broken_time' => false,
        ]);
        $user = User::factory()->create(['role' => 'employee']);
        $employee = Employee::query()->create([
            'user_id' => $user->id, 'uid' => 'DELETION-1',
            'firstname' => 'Delete', 'lastname' => 'Test',
            'branch_id' => $branch->id, 'fingerprint_id' => 'DEL-1001',
            'hired_date' => '2026-01-01', 'employment_type' => 'Permanent',
            'rate_type' => 'monthly', 'monthly_rate' => 20800,
        ]);

        return [$user, $employee];
    }
}
