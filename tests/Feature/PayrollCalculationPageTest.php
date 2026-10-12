<?php

namespace Tests\Feature;

use App\Filament\Pages\PayrollCalculation;
use App\Models\PayrollCalculationSetting;
use App\Models\PayrollPeriod;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PayrollCalculationPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::query()->where('username', 'masteradmin')->first()
            ?? User::withoutEvents(fn (): User => User::query()->create([
                'name' => 'Master Admin',
                'username' => 'masteradmin',
                'email' => 'masteradmin@example.test',
                'password' => 'password',
                'role' => 'admin',
            ]));

        Filament::setCurrentPanel(Filament::getPanel('hr'));
        Filament::auth()->setUser($this->user);
        auth()->setUser($this->user);
    }

    public function test_payroll_calculation_page_renders_successfully_for_open_period(): void
    {
        $period = PayrollPeriod::query()->create([
            'title' => 'Oct 11 – 25, 2026',
            'date_start' => '2026-10-11',
            'date_end' => '2026-10-25',
            'date_payout' => '2026-10-30',
            'is_locked' => false,
        ]);

        $this->actingAs($this->user);

        $response = $this->get('/hr/payroll-calculation?periodId=' . $period->publicKey());

        $response->assertSuccessful();
        $response->assertSee('Payroll Calculation Settings');
        $response->assertSee('Oct 11 – 25, 2026');
        $response->assertSee('Open Period (Editable Rules)');
        $response->assertSee('Return to Payroll');
        $response->assertSee('Work Schedule &amp; Days Basis', false);
        $response->assertSee('Attendance &amp; Grace Periods', false);
        $response->assertSee('Overtime &amp; Differential Multipliers', false);
        $response->assertSee('Holiday Rates &amp; Policy Controls', false);
        $response->assertSee('Live Simulator');
        $response->assertSee('Calculation Formulas &amp; Statutory Policy Reference Deck', false);
    }

    public function test_payroll_calculation_page_renders_read_only_badge_for_locked_period(): void
    {
        $period = PayrollPeriod::query()->create([
            'title' => 'Past Locked Period',
            'date_start' => '2026-09-11',
            'date_end' => '2026-09-25',
            'date_payout' => '2026-09-30',
            'is_locked' => true,
        ]);

        $this->actingAs($this->user);

        $response = $this->get('/hr/payroll-calculation?periodId=' . $period->publicKey());

        $response->assertSuccessful();
        $response->assertSee('Locked Period (Read Only)');
        $response->assertSee('This period is locked. Modifications cannot be saved.');
    }

    public function test_all_ten_calculation_formula_fields_are_editable_and_can_be_saved(): void
    {
        $period = PayrollPeriod::query()->create([
            'title' => 'Test Open Period',
            'date_start' => '2026-10-11',
            'date_end' => '2026-10-25',
            'date_payout' => '2026-10-30',
            'is_locked' => false,
        ]);

        $this->actingAs($this->user);

        Livewire::withQueryParams(['periodId' => $period->publicKey()])
            ->test(PayrollCalculation::class)
            ->assertSuccessful()
            ->fillForm([
                'regular_work_days_per_month' => '25',
                'regular_half_month_days' => '12.5',
                'work_hours_per_day' => '8',
                'late_grace_minutes' => '15',
                'half_day_work_day_value' => '0.5',
                'overtime_rate_multiplier' => '1.30',
                'regular_holiday_rate' => '120',
                'special_holiday_rate' => '35',
                'holiday_overtime_premium_rate' => '30',
                'unworked_regular_holiday_pay_enabled' => false,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $saved = PayrollCalculationSetting::forPeriod($period);
        $this->assertEquals(25.0, (float) $saved->regular_work_days_per_month);
        $this->assertEquals(12.5, (float) $saved->regular_half_month_days);
        $this->assertEquals(8.0, (float) $saved->work_hours_per_day);
        $this->assertEquals(15.0, (float) $saved->late_grace_minutes);
        $this->assertEquals(0.5, (float) $saved->half_day_work_day_value);
        $this->assertEquals(1.30, (float) $saved->overtime_rate_multiplier);
        $this->assertEquals(120.0, (float) $saved->regular_holiday_rate);
        $this->assertEquals(35.0, (float) $saved->special_holiday_rate);
        $this->assertEquals(30.0, (float) $saved->holiday_overtime_premium_rate);
        $this->assertFalse((bool) $saved->unworked_regular_holiday_pay_enabled);
    }

    public function test_validation_fails_when_cutoff_days_exceed_monthly_work_days(): void
    {
        $period = PayrollPeriod::query()->create([
            'title' => 'Test Period Validation',
            'date_start' => '2026-10-11',
            'date_end' => '2026-10-25',
            'date_payout' => '2026-10-30',
            'is_locked' => false,
        ]);

        $this->actingAs($this->user);

        Livewire::withQueryParams(['periodId' => $period->publicKey()])
            ->test(PayrollCalculation::class)
            ->fillForm([
                'regular_work_days_per_month' => '10',
                'regular_half_month_days' => '15',
            ])
            ->call('save')
            ->assertHasErrors(['regular_half_month_days']);
    }

    public function test_live_calculation_simulator_computes_breakdown_correctly(): void
    {
        $period = PayrollPeriod::query()->create([
            'title' => 'Simulator Test Period',
            'date_start' => '2026-10-11',
            'date_end' => '2026-10-25',
            'date_payout' => '2026-10-30',
            'is_locked' => false,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::withQueryParams(['periodId' => $period->publicKey()])
            ->test(PayrollCalculation::class)
            ->fillForm([
                'regular_work_days_per_month' => '26',
                'work_hours_per_day' => '8',
                'overtime_rate_multiplier' => '1.25',
            ])
            ->set('simRateType', 'monthly')
            ->set('simSalary', 26000)
            ->set('simDaysWorked', 13)
            ->set('simOvertimeHours', 4);

        $breakdown = $component->get('simulatedBreakdown');
        // Daily: 26000 / 26 = 1000
        $this->assertEquals(1000.0, (float) $breakdown['daily_rate']);
        // Hourly: 1000 / 8 = 125
        $this->assertEquals(125.0, (float) $breakdown['hourly_rate']);
        // Base pay: 26000 * (13 / 26) = 13000
        $this->assertEquals(13000.0, (float) $breakdown['base_pay']);
        // OT pay: 4 * 125 * 1.25 = 625
        $this->assertEquals(625.0, (float) $breakdown['overtime_pay']);
        // Gross pay: 13000 + 625 = 13625
        $this->assertEquals(13625.0, (float) $breakdown['gross_pay']);
    }

    public function test_locked_payroll_period_cannot_be_saved(): void
    {
        $period = PayrollPeriod::query()->create([
            'title' => 'Locked Period',
            'date_start' => '2026-09-11',
            'date_end' => '2026-09-25',
            'date_payout' => '2026-09-30',
            'is_locked' => true,
        ]);

        $this->actingAs($this->user);

        Livewire::withQueryParams(['periodId' => $period->publicKey()])
            ->test(PayrollCalculation::class)
            ->fillForm([
                'regular_work_days_per_month' => '24',
            ])
            ->call('save')
            ->assertHasErrors(['period_display']);
    }
}
