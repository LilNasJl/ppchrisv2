<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\HolidayExclusionTable;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class HolidayExclusions extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.branch-holiday-branches';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Holiday Exclusions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserMinus;

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
            HolidayExclusionTable::class,
        ];
    }
}
