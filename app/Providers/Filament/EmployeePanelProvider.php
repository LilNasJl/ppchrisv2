<?php

namespace App\Providers\Filament;

use App\Filament\Auth\EmployeeLogin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class EmployeePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('employee')
            ->path('employee')
            // ->brandLogo(asset('ppclogo.png'))
            // ->brandLogoHeight('48px')
            // ->collapsedSidebarWidth(true)
            ->sidebarCollapsibleOnDesktop(true)
            ->brandName('HRMS: SELF SERVICE')
            ->globalSearch(false)
            ->maxContentWidth(Width::Full)
            ->databaseNotifications()
            ->favicon(asset('ppclogo.png').'?v=20260724')
            ->login(EmployeeLogin::class)
            ->navigationGroups([
                NavigationGroup::make('My Workspace'),
                NavigationGroup::make('My Profile'),
                NavigationGroup::make('Reports & Updates'),
            ])
            ->colors([
                'primary' => Color::Blue,
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.employee-panel-styles'),
            )
            ->discoverPages(in: app_path('Filament/Employee/Pages'), for: 'App\Filament\Employee\Pages')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
