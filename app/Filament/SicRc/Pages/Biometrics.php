<?php

namespace App\Filament\SicRc\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class Biometrics extends Page
{
    protected string $view = 'filament.sicrc.pages.biometrics';

    protected static ?string $title = 'Biometrics';

    protected static ?string $navigationLabel = 'Biometrics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::FingerPrint;

    protected static ?int $navigationSort = 4;

    public string $biometricsUrl = 'https://www.biometrics.philfumes.net';

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
