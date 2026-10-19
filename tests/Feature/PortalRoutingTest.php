<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalRoutingTest extends TestCase
{
    use RefreshDatabase;
    public function test_landing_page_has_one_login_form_for_both_portals(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('action="'.route('landing.login').'"', false)
            ->assertSee('name="username"', false)
            ->assertSee('name="password"', false)
            ->assertDontSee('href="'.route('filament.hr.auth.login').'"', false)
            ->assertDontSee('href="'.route('filament.employee.auth.login').'"', false)
            ->assertDontSee('href="'.route('filament.kpi.auth.login').'"', false);
    }

    public function test_all_portal_login_routes_are_distinct(): void
    {
        $this->assertSame(url('/hr/login'), route('filament.hr.auth.login'));
        $this->assertSame(url('/employee/login'), route('filament.employee.auth.login'));
        $this->assertSame(url('/kpi/login'), route('filament.kpi.auth.login'));
    }

    public function test_guest_station_tool_request_redirects_to_employee_login(): void
    {
        $this->get(route('sicrc_tools.export.dtr_preview'))
            ->assertRedirect(route('filament.employee.auth.login'));
    }

    public function test_portal_login_routes_redirect_to_landing_page(): void
    {
        $this->get(route('filament.hr.auth.login'))
            ->assertRedirect(route('landing'));

        $this->get(route('filament.employee.auth.login'))
            ->assertRedirect(route('landing'));

        $this->get(route('filament.kpi.auth.login'))
            ->assertRedirect(route('landing'));
    }

    public function test_unauthenticated_panel_access_redirects_to_landing(): void
    {
        $this->get('/hr')->assertRedirect(route('landing'));
        $this->get('/employee')->assertRedirect(route('landing'));
        $this->get('/kpi')->assertRedirect(route('landing'));
    }

    public function test_logging_out_from_hr_and_employee_redirects_to_landing(): void
    {
        $hrUser = \App\Models\User::factory()->create(['role' => 'hr']);
        $this->actingAs($hrUser, 'web')
            ->post('/hr/logout')
            ->assertRedirect(route('landing'));
        $this->assertGuest('web');

        $empUser = \App\Models\User::factory()->create(['role' => 'employee']);
        $this->actingAs($empUser, 'web')
            ->post('/employee/logout')
            ->assertRedirect(route('landing'));
        $this->assertGuest('web');
    }
}
