<?php

namespace App\Filament\Resources\PayrollPeriods\Widgets;

use App\Models\PayrollPeriod;
use Filament\Widgets\Widget;

class PayrollPeriodStatsOverview extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.resources.payroll-periods.widgets.payroll-period-stats-overview';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<int, array{label: string, value: string, sub: string, icon: string, tone: string}>
     */
    public function getMetrics(): array
    {
        $total = PayrollPeriod::query()->count();
        $openCount = PayrollPeriod::query()->where('is_locked', false)->count();
        $lockedCount = PayrollPeriod::query()->where('is_locked', true)->count();
        $activePeriod = PayrollPeriod::query()
            ->where('is_locked', false)
            ->orderBy('date_start', 'desc')
            ->first();

        return [
            [
                'label' => 'Total Periods',
                'value' => number_format($total),
                'sub' => 'All cutoff cycles',
                'icon' => 'heroicon-o-calendar-days',
                'tone' => 'primary',
            ],
            [
                'label' => 'Currently Running',
                'value' => number_format($openCount),
                'sub' => 'Open for DTR & edits',
                'icon' => 'heroicon-o-lock-open',
                'tone' => 'success',
            ],
            [
                'label' => 'Locked Periods',
                'value' => number_format($lockedCount),
                'sub' => 'Not currently running',
                'icon' => 'heroicon-o-lock-closed',
                'tone' => 'slate',
            ],
            [
                'label' => 'Current Cycle',
                'value' => $activePeriod?->title ?: 'None Running',
                'sub' => $activePeriod?->date_payout ? 'Payout: ' . $activePeriod->date_payout->format('M d, Y') : 'No active period',
                'icon' => 'heroicon-o-banknotes',
                'tone' => 'info',
            ],
        ];
    }
}
