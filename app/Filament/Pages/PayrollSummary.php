<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PayrollSummary extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.under-construction';

    protected static ?string $title = 'Payroll Summary';

    protected static ?string $navigationLabel = 'Consolidated Report for Payroll';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Analytics & Reports';

    protected static ?int $navigationSort = 1;
}
