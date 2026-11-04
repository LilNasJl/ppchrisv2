<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\Ticket;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EmployeeStatusSummary extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int|array
    {
        return [
            'default' => 2,
            'sm' => 2,
            'md' => 4,
            'lg' => 4,
            'xl' => 4,
        ];
    }

    protected function getStats(): array
    {
        $employees = $this->employeeAccounts();
        $employmentType = DB::raw('UPPER(TRIM(employment_type))');

        $activeEmployees = (clone $employees)
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('employment_type')
                ->orWhereNotIn($employmentType, $this->endedEmploymentTypes()))
            ->count();

        $probationaryEmployees = (clone $employees)
            ->where($employmentType, 'PROBATIONARY')
            ->count();

        $resignedEmployees = (clone $employees)
            ->whereIn($employmentType, $this->endedEmploymentTypes())
            ->count();

        $pendingTickets = Ticket::query()
            ->where('status', Ticket::STATUS_PENDING)
            ->count();

        return [
            Stat::make('Active Employees', number_format($activeEmployees))
                ->icon(Heroicon::Users)
                ->color('success'),

            Stat::make('Probationary', number_format($probationaryEmployees))
                ->icon(Heroicon::Clock)
                ->color('warning'),

            Stat::make('Resigned', number_format($resignedEmployees))
                ->icon(Heroicon::UserMinus)
                ->color('danger'),

            Stat::make('Pending Tickets', number_format($pendingTickets))
                ->icon(Heroicon::ChatBubbleLeftRight)
                ->color('warning'),
        ];
    }

    protected function employeeAccounts(): Builder
    {
        return Employee::query()
            ->whereHas('user', fn (Builder $query): Builder => $query->where('role', 'employee'));
    }

    private function endedEmploymentTypes(): array
    {
        return array_values(array_unique([
            ...array_map('strtoupper', Employee::ENDED_EMPLOYMENT_TYPES),
            'CONTRACT ENDED',
        ]));
    }
}
