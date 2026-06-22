<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CompanyDeductionsTable;
use App\Filament\Widgets\OtherDeductionsTable;
use App\Filament\Widgets\RemittanceDeductionsTable;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
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
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(DeductionsManagement::getUrl()),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            CompanyDeductionsTable::class,
            RemittanceDeductionsTable::class,
            OtherDeductionsTable::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
        ];
    }
}
