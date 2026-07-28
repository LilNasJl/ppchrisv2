<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CompanyHandbook extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.under-construction';

    protected static ?string $title = 'Company Handbook';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Performance Management';

    protected static ?int $navigationSort = 2;
}
