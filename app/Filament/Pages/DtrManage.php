<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DtrManageTable;
use Filament\Actions\Action;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class DtrManage extends Page
{
    use HasPageShield;
    protected string $view = 'filament.pages.dtr-manage';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = "Manage D.T.R";

    public ?string $employeeId = null;

    public ?string $branchId = null;

    public function mount(): void
    {
        $this->employeeId = request()->query('employeeId');
        $this->branchId = request()->query('branchId');
    }

    // ✅ Must be public to match Filament's parent signature
    public function getWidgetData(): array
    {
        return [
            'employeeId' => $this->employeeId,
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
                ->url(fn () => url()->previous())
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
