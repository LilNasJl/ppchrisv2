<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ComplianceBenefits extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.compliance-benefits';

    protected static ?string $title = 'Compliance and Benefits';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Employee Management';

    protected static ?int $navigationSort = 2;
}
