<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageBiometrics extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.manage-biometrics';

    protected static ?string $title = 'Manage Biometrics';

    protected static ?string $navigationLabel = 'Manage Biometrics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::FingerPrint;

    protected static string|UnitEnum|null $navigationGroup = 'Compensation and Benefits Management';

    protected static ?int $navigationSort = 3;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getViewData(): array
    {
        return [
            'biometricsUrl' => 'https://biometrics.philfumes.net',
        ];
    }
}
