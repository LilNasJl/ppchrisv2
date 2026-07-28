<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class KpiConfiguration extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.kpi-configuration';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'KPI Configuration';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::AdjustmentsHorizontal;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(Kpi::getUrl()),
        ];
    }
}
