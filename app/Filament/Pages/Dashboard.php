<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardCard;
use App\Filament\Widgets\EmployeeTenureTable;
use App\Filament\Widgets\EmploymentTypeHeadCount;
use App\Filament\Widgets\HeadCountPerDept;
use App\Filament\Widgets\UpcomingActivitiesTable;
use App\Filament\Widgets\UpcomingHolidaysTable;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Dashboard extends Page
{
    protected string $view = 'filament.pages.dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    // protected function getColumns(): int | array
    // {
    //     return 2;
    // }

    protected function getHeaderWidgets(): array
    {
        return [
            DashboardCard::class,
            HeadCountPerDept::class,
            EmploymentTypeHeadCount::class,
            EmployeeTenureTable::class,
            UpcomingHolidaysTable::class,
            UpcomingActivitiesTable::class,

        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }
}
