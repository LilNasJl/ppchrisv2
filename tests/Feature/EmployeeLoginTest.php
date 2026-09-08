<?php

namespace Tests\Feature;

use App\Filament\Auth\EmployeeLogin;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class EmployeeLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('employee'));
    }

    public function test_employee_can_sign_in_using_company_id_and_password(): void
    {
        $employeeAccount = User::factory()->create([
            'username' => 'PF0999',
            'password' => Hash::make('employee-pass'),
            'role' => 'employee',
            'is_disabled' => false,
        ]);

        Livewire::test(EmployeeLogin::class)
            ->fillForm([
                'username' => 'PF-0999',
                'password' => 'employee-pass',
            ])
            ->call('authenticate')
            ->assertRedirect(url('/employee'));

        $this->assertAuthenticatedAs($employeeAccount, 'web');
    }

    public function test_hr_and_disabled_employee_accounts_cannot_use_self_service(): void
    {
        User::factory()->create([
            'username' => 'PF0998',
            'password' => Hash::make('employee-pass'),
            'role' => 'hr',
            'is_disabled' => false,
        ]);
        User::factory()->create([
            'username' => 'PF0997',
            'password' => Hash::make('employee-pass'),
            'role' => 'employee',
            'is_disabled' => true,
        ]);

        Livewire::test(EmployeeLogin::class)
            ->fillForm([
                'username' => 'PF0998',
                'password' => 'employee-pass',
            ])
            ->call('authenticate')
            ->assertHasErrors(['data.username']);

        Livewire::test(EmployeeLogin::class)
            ->fillForm([
                'username' => 'PF0997',
                'password' => 'employee-pass',
            ])
            ->call('authenticate')
            ->assertHasErrors(['data.username']);

        $this->assertGuest('web');
    }
}
