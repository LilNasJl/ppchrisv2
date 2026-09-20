<?php

namespace Tests\Feature;

use Tests\TestCase;

class PortalRoutingTest extends TestCase
{
    public function test_landing_page_links_to_hr_and_employee_portals(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('filament.hr.auth.login').'"', false)
            ->assertSee('href="'.route('filament.employee.auth.login').'"', false)
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

    public function test_portal_login_pages_render_correctly(): void
    {
        $this->get(route('filament.hr.auth.login'))
            ->assertOk()
            ->assertSee('HRIS Portal');

        $this->get(route('filament.employee.auth.login'))
            ->assertOk()
            ->assertSee('HRIS: SELF SERVICE');

        $this->get(route('filament.kpi.auth.login'))->assertOk();
    }
}
