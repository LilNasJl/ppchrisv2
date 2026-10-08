<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CompanyDeductionsTable;
use App\Filament\Widgets\OtherDeductionsTable;
use App\Filament\Widgets\RemittanceDeductionsTable;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Override;

class ManageDeductions extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.manage-deductions';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Manage Deductions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    #[Override]
    public function getHeading(): string
    {
        return '';
    }

    #[Override]
    public function getBreadcrumbs(): array
    {
        return [];
    }

    #[Override]
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
