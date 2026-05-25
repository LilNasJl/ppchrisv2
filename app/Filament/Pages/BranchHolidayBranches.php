<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BranchHolidayBranchTable;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class BranchHolidayBranches extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.branch-holiday-branches';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Branch Holidays';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingStorefront;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(HolidayCalendar::getUrl()),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            BranchHolidayBranchTable::class,
        ];
    }
}
