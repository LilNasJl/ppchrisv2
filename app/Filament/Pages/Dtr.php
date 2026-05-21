<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DtrPageTable;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;
use SebastianBergmann\CodeCoverage\Report\Xml\Unit;
use UnitEnum;

class Dtr extends Page
{
    use HasPageShield;
    protected string $view = 'filament.pages.dtr';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::QueueList;
    protected static string|UnitEnum|null $navigationGroup = 'Payroll Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'D.T.R';
    protected static ?string $title = 'D.T.R Management';

    protected function getHeaderWidgets(): array
    {
        return [
            DtrPageTable::class
        ];
    }
}
