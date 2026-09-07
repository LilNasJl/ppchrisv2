<?php

namespace Tests\Feature;

use App\Filament\Auth\Login;
use App\Models\SicRcAccount;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class UnifiedPortalLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('hr'));
    }

    public function test_hr_account_can_sign_in_through_the_hris_portal(): void
    {
        $user = User::factory()->create([
            'username' => 'shared-hr-user',
            'password' => Hash::make('secret-pass'),
            'role' => 'hr',
            'is_disabled' => false,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'shared-hr-user',
                'password' => 'secret-pass',
            ])
            ->call('authenticate')
            ->assertRedirect(url('/hr'));

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('sicrc');
    }

    public function test_sicrc_account_can_sign_in_through_the_same_hris_portal(): void
    {
        $account = SicRcAccount::query()->create([
            'username' => 'shared-sicrc-user',
            'password' => 'secret-pass',
            'is_active' => true,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'shared-sicrc-user',
                'password' => 'secret-pass',
            ])
            ->call('authenticate')
            ->assertRedirect(url('/sicrc'));

        $this->assertGuest('web');
        $this->assertAuthenticatedAs($account, 'sicrc');
    }

    public function test_employee_and_disabled_sicrc_accounts_are_rejected(): void
    {
        User::factory()->create([
            'username' => 'employee-only-user',
            'password' => Hash::make('secret-pass'),
            'role' => 'employee',
            'is_disabled' => false,
        ]);
        SicRcAccount::query()->create([
            'username' => 'disabled-sicrc-user',
            'password' => 'secret-pass',
            'is_active' => false,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'employee-only-user',
                'password' => 'secret-pass',
            ])
            ->call('authenticate')
            ->assertHasErrors(['data.username']);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'disabled-sicrc-user',
                'password' => 'secret-pass',
            ])
            ->call('authenticate')
            ->assertHasErrors(['data.username']);

        $this->assertGuest('web');
        $this->assertGuest('sicrc');
    }
}
