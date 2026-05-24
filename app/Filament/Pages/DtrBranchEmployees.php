<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DtrBranchEmployeeTable;
use App\Models\Branch;
use App\Models\PayrollPeriod;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class DtrBranchEmployees extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.dtr-branch-employees';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Branch D.T.R';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    public ?int $periodId = null;

    public ?int $branchId = null;

    public ?PayrollPeriod $period = null;

    public ?Branch $branch = null;

    public function mount(): void
    {
        $this->periodId = (int) request()->query('periodId');
        $this->branchId = (int) request()->query('branchId');
        $this->period = PayrollPeriod::query()->find($this->periodId);
        $this->branch = Branch::query()->find($this->branchId);
    }

    public function getTitle(): string
    {
        return 'D.T.R - '.($this->branch?->branch_name ?: 'Branch').' - '.($this->period?->title ?: 'No Period');
    }

    public function getWidgetData(): array
    {
        return [
            'periodId' => $this->periodId,
            'branchId' => $this->branchId,
        ];
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => DtrPeriodBranches::getUrl(['periodId' => $this->periodId])),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            DtrBranchEmployeeTable::class,
        ];
    }
}
