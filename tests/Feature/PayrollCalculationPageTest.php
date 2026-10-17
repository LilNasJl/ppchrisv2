<?php

namespace Tests\Feature;

use App\Filament\Pages\PayrollCalculation;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCalculationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_retired_payroll_calculation_page_cannot_be_opened_even_by_master_admin(): void
    {
        $user = User::query()->where('username', 'masteradmin')->first()
            ?? User::withoutEvents(fn (): User => User::query()->create([
                'name' => 'Master Admin',
                'username' => 'masteradmin',
                'email' => 'masteradmin@example.test',
                'password' => 'password',
                'role' => 'admin',
            ]));
        $period = PayrollPeriod::query()->create([
            'title' => 'Oct 11 - 25, 2026',
            'date_start' => '2026-10-11',
            'date_end' => '2026-10-25',
            'date_payout' => '2026-10-30',
            'is_locked' => false,
        ]);

        $this->assertFalse(PayrollCalculation::canAccess());
        $this->actingAs($user)
            ->get('/hr/payroll-calculation?periodId='.$period->publicKey())
            ->assertForbidden();
    }
}
