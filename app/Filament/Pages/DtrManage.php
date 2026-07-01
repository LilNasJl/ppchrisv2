<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DtrManageTable;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\PayrollPeriod;
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

    public ?int $employeeId = null;

    public ?int $branchId = null;

    public ?int $periodId = null;

    public function mount(): void
    {
        $this->employeeId = Employee::resolvePublicId(request()->query('employeeId'));
        $this->branchId = Branch::resolvePublicId(request()->query('branchId'));
        $this->periodId = PayrollPeriod::resolvePublicId(request()->query('periodId'));
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
                    'periodId' => PayrollPeriod::query()->find($this->periodId)?->publicKey(),
                    'branchId' => Branch::query()->find($this->branchId)?->publicKey(),
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
