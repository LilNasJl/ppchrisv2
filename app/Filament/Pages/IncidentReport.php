<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class IncidentReport extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.under-construction';

    protected static ?string $title = 'Incident Report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Labor Management';

    protected static ?int $navigationSort = 1;
}
