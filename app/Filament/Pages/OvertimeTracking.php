<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class OvertimeTracking extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.under-construction';

    protected static ?string $title = 'Overtime Tracking';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clock;

    protected static string|UnitEnum|null $navigationGroup = 'Employee Request & Tracking';

    protected static ?int $navigationSort = 4;
}
