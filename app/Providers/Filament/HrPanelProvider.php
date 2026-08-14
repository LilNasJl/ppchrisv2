<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
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
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class HrPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('hr')
            ->path('hr')
            // ->brandLogo(asset('ppclogo.png'))
            // ->brandLogoHeight('48px')
            ->brandName('Human Resource Management System')
            ->globalSearch(false)
            ->favicon(asset('ppclogo.png').'?v=20260724')
            ->maxContentWidth(Width::Full)
            ->databaseNotifications()
            ->login(Login::class)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Analytic and Reporting'),
                NavigationGroup::make('Employee Management'),
                NavigationGroup::make('Compensation and Benefits Management'),
                NavigationGroup::make('Performance Management'),
                NavigationGroup::make('Labor Management'),
                NavigationGroup::make('Updates and Activities'),
                NavigationGroup::make('Organizational Set Up'),
                NavigationGroup::make('Settings'),
            ])
            ->colors([
                'primary' => Color::Blue,
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.hr-panel-styles'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([

            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('Settings')
                    ->navigationLabel('Shield Roles')
                    ->navigationSort(2),
            ])
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
            ->resourceEditPageRedirect('index')
            ->resourceCreatePageRedirect('index')
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
