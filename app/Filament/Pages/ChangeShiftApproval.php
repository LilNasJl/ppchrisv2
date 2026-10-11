<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ChangeShiftApproval extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.under-construction';

    protected static ?string $title = 'Change Shift Approval';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'Employee Request & Tracking';

    protected static ?int $navigationSort = 5;
}
