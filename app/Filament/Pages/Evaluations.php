<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Evaluations extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.under-construction';

    protected static ?string $title = 'Evaluations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Employees Relation & Performance Mngt';

    protected static ?int $navigationSort = 3;
}
