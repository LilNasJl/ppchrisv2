<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->favicon(url('ppclogo.png'))
            ->databaseNotifications()
            ->login(Login::class)
            ->sidebarFullyCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Employee Management'),
                NavigationGroup::make('Payroll Management'),
                NavigationGroup::make('Updates and Activities'),
                NavigationGroup::make('Reports and Documents'),
                NavigationGroup::make('Organizational Setup'),
                NavigationGroup::make('Compliance & Benefits'),

                // NavigationGroup::make()
                //     ->label('Employee Management'),

                // NavigationGroup::make()
                //     ->label('Payroll Management'),

                // NavigationGroup::make()
                //     ->label('Leave'),

                // NavigationGroup::make()
                //     ->label('Organizational Setup'),

                // NavigationGroup::make()
                //     ->label('Compliance & Benefits'),
            ])
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([

            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
