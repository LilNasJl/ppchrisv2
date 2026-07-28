<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AgeGroupSummary;
use App\Filament\Widgets\BirthdayGreetingCard;
use App\Filament\Widgets\DashboardCard;
use App\Filament\Widgets\EducationLevelSummary;
use App\Filament\Widgets\EmploymentTypeHeadCount;
use App\Filament\Widgets\HeadCountPerDept;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Dashboard extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Analytic and Reporting';

    protected static ?int $navigationSort = 1;

    protected function getHeaderWidgets(): array
    {
        return [
            DashboardCard::class,
            BirthdayGreetingCard::class,
            HeadCountPerDept::class,
            EmploymentTypeHeadCount::class,
            EducationLevelSummary::class,
            AgeGroupSummary::class,
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
