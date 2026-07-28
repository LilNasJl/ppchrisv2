<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DtrPageTable;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Dtr extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.dtr';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::QueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Compensation and Benefits Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Daily Time Record';

    protected static ?string $title = 'D.T.R Management';

    protected function getHeaderWidgets(): array
    {
        return [
            DtrPageTable::class,
        ];
    }
}
