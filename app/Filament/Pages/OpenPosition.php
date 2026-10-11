<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class OpenPosition extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.under-construction';

    protected static ?string $title = 'Open Position';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Briefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';

    protected static ?int $navigationSort = 1;
}
