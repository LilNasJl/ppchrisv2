<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ComplianceBenefitsEmployeeTable;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;
use UnitEnum;

class ComplianceBenefits extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.compliance-benefits';

    protected static ?string $title = 'Compliance and Benefits';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Employee Management';

    protected static ?int $navigationSort = 2;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageDeductions')
                ->label('Manage Deductions')
                ->icon(Heroicon::Cog6Tooth)
                ->url(ManageDeductions::getUrl()),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            ComplianceBenefitsEmployeeTable::class,
        ];
    }
}
