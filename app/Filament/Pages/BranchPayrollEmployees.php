<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BranchPayrollEmployeeTable;
use App\Models\Branch;
use App\Models\PayrollPeriod;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class BranchPayrollEmployees extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.branch-payroll-employees';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Branch Payroll';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    public ?int $branchId = null;

    public ?int $periodId = null;

    public ?Branch $branch = null;

    public ?PayrollPeriod $period = null;

    public function mount(): void
    {
        $this->branchId = Branch::resolvePublicId(request()->query('branchId'));
        $this->periodId = PayrollPeriod::resolvePublicId(request()->query('periodId'));
        $this->branch = filled($this->branchId) ? Branch::query()->find($this->branchId) : null;
        $this->period = filled($this->periodId) ? PayrollPeriod::query()->find($this->periodId) : null;
    }

    public function getTitle(): string
    {
        return $this->branch?->branch_name
            ? 'Payroll - '.$this->branch->branch_name.' - '.($this->period?->title ?? 'No Period')
            : 'Branch Payroll';
    }

    public function getWidgetData(): array
    {
        return [
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
                ->url(fn (): string => PayrollPeriodBranches::getUrl(['periodId' => $this->period?->publicKey()])),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            BranchPayrollEmployeeTable::class,
        ];
    }
}
