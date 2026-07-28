<?php

namespace App\Filament\Kpi\Pages;

use App\Models\KpiAccount;
use App\Models\KpiRatingCycle;
use App\Models\KpiRatingTarget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Dashboard extends Page
{
    protected string $view = 'filament.kpi.pages.dashboard';

    protected static ?string $title = 'Dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static ?int $navigationSort = 1;

    protected function getViewData(): array
    {
        /** @var KpiAccount $account */
        $account = auth('kpi')->user();

        return [
            'account' => $account->loadMissing(['branches', 'departments', 'employees']),
            'cycleCount' => KpiRatingCycle::query()
                ->where('kpi_account_id', $account->getKey())
                ->count(),
            'targetCount' => KpiRatingTarget::query()
                ->whereHas('cycle', fn ($query) => $query->where('kpi_account_id', $account->getKey()))
                ->count(),
            'pendingCount' => KpiRatingTarget::query()
                ->whereHas('cycle', fn ($query) => $query->where('kpi_account_id', $account->getKey()))
                ->where('status', 'pending')
                ->count(),
        ];
    }
}
