<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardCard extends StatsOverviewWidget
{
    use HasWidgetShield;
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int|array
    {
        return [
            'default' => 2,
            'md' => 5,
        ];
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Employee', Employee::query()
                ->activeEmployment()
                ->whereHas('user', fn ($query) => $query->where('role', 'employee'))
                ->count())
                ->icon('heroicon-s-user-group')
                ->color('primary'),

            Stat::make('Department', Department::count())
                ->icon('heroicon-s-building-office-2')
                ->color('info'),

            Stat::make('Male',
                Employee::where('gender', 'male')
                    ->activeEmployment()
                    ->whereHas('user', function ($query) {
                        $query->where('role', 'employee');
                    })
                    ->count())
                ->icon('heroicon-s-user')
                ->color('success'),

            Stat::make('Female',
                Employee::where('gender', 'female')
                    ->activeEmployment()
                    ->whereHas('user', function ($query) {
                        $query->where('role', 'employee');
                    })
                    ->count())
                ->icon('heroicon-s-user')
                ->color('danger'),

            Stat::make('Leave Request', Leave::query()
                ->whereHas('employee', fn ($query) => $query->activeEmployment())
                ->where('status', 'Pending')
                ->count())
                ->icon('heroicon-s-calendar-days')
                ->color('warning'),

        ];
    }
}
