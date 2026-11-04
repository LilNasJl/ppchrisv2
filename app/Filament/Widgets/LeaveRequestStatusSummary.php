<?php

namespace App\Filament\Widgets;

use App\Models\Leave;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LeaveRequestStatusSummary extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int|array
    {
        return [
            'default' => 2,
            'sm' => 2,
            'md' => 4,
            'lg' => 4,
        ];
    }

    protected function getStats(): array
    {
        $counts = Leave::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->whereIn('status', ['Pending', 'Approved', 'Rejected', 'Cancelled'])
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            Stat::make('Pending', number_format((int) ($counts['Pending'] ?? 0)))
                ->icon(Heroicon::Clock)
                ->color('warning'),
            Stat::make('Approved', number_format((int) ($counts['Approved'] ?? 0)))
                ->icon(Heroicon::CheckCircle)
                ->color('success'),
            Stat::make('Rejected', number_format((int) ($counts['Rejected'] ?? 0)))
                ->icon(Heroicon::XCircle)
                ->color('danger'),
            Stat::make('Cancelled', number_format((int) ($counts['Cancelled'] ?? 0)))
                ->icon(Heroicon::NoSymbol)
                ->color('gray'),
        ];
    }
}
