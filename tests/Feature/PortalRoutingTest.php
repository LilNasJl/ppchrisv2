<?php

namespace Tests\Feature;

use Tests\TestCase;

class PortalRoutingTest extends TestCase
{
    public function test_landing_page_uses_one_hris_login_for_hr_and_sicrc_accounts(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('filament.hr.auth.login').'"', false)
            ->assertSee('href="'.route('filament.employee.auth.login').'"', false)
            ->assertDontSee('href="'.route('filament.kpi.auth.login').'"', false)
            ->assertSee('HRIS Portal')
            ->assertDontSee('href="'.route('filament.sicrc.auth.login').'"', false);
    }

    public function test_all_portal_login_routes_are_distinct(): void
    {
        $this->assertSame(url('/hr/login'), route('filament.hr.auth.login'));
        $this->assertSame(url('/employee/login'), route('filament.employee.auth.login'));
        $this->assertSame(url('/kpi/login'), route('filament.kpi.auth.login'));
        $this->assertSame(url('/sicrc/login'), route('filament.sicrc.auth.login'));
    }

    public function test_guest_sicrc_tool_request_redirects_to_sicrc_login(): void
    {
        $this->get(route('sicrc_tools.export.dtr_preview'))
            ->assertRedirect(route('filament.sicrc.auth.login'));
    }

    public function test_hris_login_page_is_canonical_for_hr_and_sicrc(): void
    {
        $this->get(route('filament.hr.auth.login'))
            ->assertOk()
            ->assertSee('HRIS Portal');

        $this->get(route('filament.sicrc.auth.login'))
            ->assertRedirect(route('filament.hr.auth.login'));

        $this->get(route('filament.employee.auth.login'))->assertOk();
        $this->get(route('filament.kpi.auth.login'))->assertOk();
    }
}
