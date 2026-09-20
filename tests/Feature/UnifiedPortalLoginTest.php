<?php

namespace Tests\Feature;

use App\Filament\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class UnifiedPortalLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTables();
        Filament::setCurrentPanel(Filament::getPanel('hr'));
    }

    protected function createTestTables(): void
    {
        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Test User');
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->default('secret');
            $table->string('remember_token', 100)->nullable();
            $table->string('role', 30)->default('employee');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->boolean('is_disabled')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
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
    }

    public function test_employee_and_disabled_accounts_are_rejected_from_hr_portal(): void
    {
        User::factory()->create([
            'username' => 'employee-only-user',
            'password' => Hash::make('secret-pass'),
            'role' => 'employee',
            'is_disabled' => false,
        ]);

        User::factory()->create([
            'username' => 'disabled-hr-user',
            'password' => Hash::make('secret-pass'),
            'role' => 'hr',
            'is_disabled' => true,
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
                'username' => 'disabled-hr-user',
                'password' => 'secret-pass',
            ])
            ->call('authenticate')
            ->assertHasErrors(['data.username']);

        $this->assertGuest('web');
    }
}
