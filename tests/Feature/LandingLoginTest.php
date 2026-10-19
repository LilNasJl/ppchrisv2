<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LandingLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_account_signs_in_from_landing_and_reaches_hr_panel(): void
    {
        $user = User::factory()->create([
            'username' => 'hr-user',
            'password' => Hash::make('secure-pass'),
            'role' => 'hr',
        ]);

        $this->post(route('landing.login'), [
            'username' => 'hr-user',
            'password' => 'secure-pass',
            'remember' => '1',
        ])->assertRedirect(url('/hr'));

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_configured_master_admin_can_use_the_same_form(): void
    {
        config()->set('auth.master_admin.username', 'masteradmin');
        config()->set('auth.master_admin.password', 'configured-secret');
        config()->set('auth.master_admin.email', 'masteradmin@example.test');

        $this->post(route('landing.login'), [
            'username' => 'MASTERADMIN',
            'password' => 'configured-secret',
        ])->assertRedirect(url('/hr'));

        $this->assertAuthenticatedAs(User::query()->where('username', 'masteradmin')->firstOrFail(), 'web');
    }

    public function test_employee_company_id_signs_in_and_reaches_self_service(): void
    {
        $user = User::factory()->create([
            'username' => 'PF0999',
            'password' => Hash::make('employee-pass'),
            'role' => 'employee',
        ]);
        $this->employeeFor($user, '0999');

        $this->post(route('landing.login'), [
            'username' => 'PF-0999',
            'password' => 'employee-pass',
        ])->assertRedirect(url('/employee'));

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_invalid_password_and_disabled_hr_account_cannot_sign_in(): void
    {
        User::factory()->create([
            'username' => 'disabled-hr',
            'password' => Hash::make('secure-pass'),
            'role' => 'hr',
            'is_disabled' => true,
        ]);

        $this->from('/')->post(route('landing.login'), [
            'username' => 'disabled-hr',
            'password' => 'secure-pass',
        ])->assertRedirect('/')
            ->assertSessionHasErrors(['login']);

        $this->from('/')->post(route('landing.login'), [
            'username' => 'disabled-hr',
            'password' => 'wrong-pass',
        ])->assertRedirect('/')
            ->assertSessionHasErrors(['login']);

        $this->assertGuest('web');
    }

    public function test_employee_with_ended_employment_cannot_sign_in(): void
    {
        $user = User::factory()->create([
            'username' => 'PF0998',
            'password' => Hash::make('employee-pass'),
            'role' => 'employee',
        ]);
        $this->employeeFor($user, '0998', 'Terminated');

        $this->from('/')->post(route('landing.login'), [
            'username' => '0998',
            'password' => 'employee-pass',
        ])->assertRedirect('/')
            ->assertSessionHasErrors(['login']);

        $this->assertGuest('web');
    }

    public function test_repeated_failed_attempts_are_throttled(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('landing.login'), [
                'username' => 'unknown-user',
                'password' => 'wrong-pass',
            ])->assertSessionHasErrors(['login']);
        }

        $this->post(route('landing.login'), [
            'username' => 'unknown-user',
            'password' => 'wrong-pass',
        ])->assertSessionHasErrors(['login']);

        $this->assertStringStartsWith('Too many sign-in attempts.', session('errors')->first('login'));

        $this->assertGuest('web');
    }

    private function employeeFor(User $user, string $uid, string $employmentType = 'Permanent'): void
    {
        Employee::query()->create([
            'user_id' => $user->id,
            'uid' => $uid,
            'firstname' => 'Portal',
            'lastname' => 'Employee',
            'hired_date' => '2026-01-01',
            'employment_type' => $employmentType,
            'rate_type' => 'monthly',
            'monthly_rate' => 26000,
        ]);
    }
}
