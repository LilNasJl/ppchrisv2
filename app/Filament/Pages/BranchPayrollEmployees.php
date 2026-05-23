<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BranchPayrollEmployeeTable;
use App\Models\Branch;
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

    public ?Branch $branch = null;

    public function mount(): void
    {
        $this->branchId = (int) request()->query('branchId');
        $this->branch = Branch::query()->find($this->branchId);
    }

    public function getTitle(): string
    {
        return $this->branch?->branch_name
            ? 'Payroll - '.$this->branch->branch_name
            : 'Branch Payroll';
    }

    public function getWidgetData(): array
    {
        return [
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
                ->url(Payroll::getUrl()),
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
