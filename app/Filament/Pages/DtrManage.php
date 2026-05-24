<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DtrManageTable;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class DtrManage extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.dtr-manage';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Manage D.T.R';

    public ?string $employeeId = null;

    public ?string $branchId = null;

    public ?string $periodId = null;

    public function mount(): void
    {
        $this->employeeId = request()->query('employeeId');
        $this->branchId = request()->query('branchId');
        $this->periodId = request()->query('periodId');
    }

    public function getWidgetData(): array
    {
        return [
            'employeeId' => $this->employeeId,
            'branchId' => $this->branchId,
            'periodId' => $this->periodId,
        ];
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => DtrBranchEmployees::getUrl([
                    'periodId' => $this->periodId,
                    'branchId' => $this->branchId,
                ])),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            DtrManageTable::class,
        ];
    }
}
