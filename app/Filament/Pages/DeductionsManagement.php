<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ComplianceBenefitsEmployeeTable;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class DeductionsManagement extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.deductions-management';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Deductions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::MinusCircle;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageDeductions')
                ->label('Manage Deductions')
                ->icon(Heroicon::Cog6Tooth)
                ->url(ManageDeductions::getUrl()),

            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(ComplianceBenefits::getUrl()),
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
