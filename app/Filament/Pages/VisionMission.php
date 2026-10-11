<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class VisionMission extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.under-construction';

    protected static ?string $title = 'Vision & Mission';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Eye;

    protected static string|UnitEnum|null $navigationGroup = 'Company Compliance';

    protected static ?int $navigationSort = 5;
}
