<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Trainings extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.under-construction';

    protected static ?string $title = 'Trainings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::AcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Employees Relation & Performance Mngt';

    protected static ?int $navigationSort = 4;
}
