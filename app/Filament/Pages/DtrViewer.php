<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\PayrollPeriod;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class DtrViewer extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.dtr-viewer';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Manage D.T.R';

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function getPayrollPeriodOptions(): array
    {
        return PayrollPeriod::query()
            ->where('is_locked', false)
            ->newestFirst()
            ->get(['id', 'title'])
            ->map(fn (PayrollPeriod $period): array => [
                'value' => (string) $period->getKey(),
                'label' => (string) $period->title,
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function getBranchOptions(): array
    {
        return Branch::query()
            ->orderBy('branch_name')
            ->get(['id', 'branch_name'])
            ->map(fn (Branch $branch): array => [
                'value' => (string) $branch->getKey(),
                'label' => (string) $branch->branch_name,
            ])
            ->all();
    }

    #[Override]
    public function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(Dtr::getUrl()),

            Action::make('dtrImportHistory')
                ->label('D.T.R Import History')
                ->icon(Heroicon::Clock)
                ->url(DtrImport::getUrl()),
        ];
    }
}
