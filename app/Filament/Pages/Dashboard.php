<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BirthdayGreetingCard;
use App\Filament\Widgets\DashboardCard;
use App\Filament\Widgets\EmployeeTenureTable;
use App\Filament\Widgets\EmploymentTypeHeadCount;
use App\Filament\Widgets\HeadCountPerDept;
use App\Filament\Widgets\UpcomingActivitiesTable;
use App\Filament\Widgets\UpcomingBirthdaysTable;
use App\Filament\Widgets\UpcomingHolidaysTable;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Dashboard extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected function getHeaderWidgets(): array
    {
        return [
            DashboardCard::class,
            BirthdayGreetingCard::class,
            HeadCountPerDept::class,
            EmploymentTypeHeadCount::class,
            EmployeeTenureTable::class,
            UpcomingHolidaysTable::class,
            UpcomingActivitiesTable::class,
            UpcomingBirthdaysTable::class,
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
