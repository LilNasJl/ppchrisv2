<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class BirthdayGreetingCard extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return static::birthdayEmployees()->exists();
    }

    protected function getStats(): array
    {
        $names = static::birthdayEmployees()
            ->get()
            ->map(fn (Employee $employee): string => $employee->full_name)
            ->implode(', ');

        return [
            Stat::make('HAPPY BIRTHDAY', $names)
                ->description('Birthday today')
                ->icon('heroicon-s-cake')
                ->color('warning'),
        ];
    }

    protected static function birthdayEmployees(): Builder
    {
        $today = now();

        return Employee::query()
            ->with('user')
            ->activeEmployment()
            ->whereHas('user', fn (Builder $query) => $query->where('role', 'employee'))
            ->whereMonth('birthdate', $today->month)
            ->whereDay('birthdate', $today->day)
            ->orderBy('lastname')
            ->orderBy('firstname');
    }
}
