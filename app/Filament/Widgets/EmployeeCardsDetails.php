<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmployeeCardsDetails extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected function getColumns(): int|array|null
    {
        return [
            'default' => 2, // mobile
            'sm' => 2,
            'md' => 4,
            'lg' => 4,
            'xl' => 4,
        ];
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Employee', Employee::query()
                ->activeEmployment()
                ->whereHas('user', fn ($query) => $query->where('role', 'employee'))
                ->count())
                ->icon('heroicon-s-user-group'),

                
            Stat::make('Department', Department::count())
                ->icon('heroicon-s-building-office-2'),


            Stat::make('Male', 
                    Employee::where('gender', 'male')
                        ->activeEmployment()
                        ->whereHas('user', function ($query) {
                            $query->where('role', 'employee');
                        })
                        ->count())
                ->icon('heroicon-s-user'),

            Stat::make('Female', 
                    Employee::where('gender', 'female')
                        ->activeEmployment()
                        ->whereHas('user', function ($query) {
                            $query->where('role', 'employee');
                        })
                        ->count())
                ->icon('heroicon-s-user'),

        ];
    }
}
