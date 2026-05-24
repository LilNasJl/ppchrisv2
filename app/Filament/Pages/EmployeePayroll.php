<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\PayrollCalculator;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class EmployeePayroll extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.employee-payroll';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Employee Payroll';

    public ?string $employeeId = null;

    public ?string $branchId = null;

    public ?string $period_id = null;

    public function mount(): void
    {
        $this->employeeId = request()->query('employeeId');
        $this->branchId = request()->query('branchId');
        $this->period_id = request()->query('periodId') ?: (string) app(PayrollCalculator::class)->defaultPeriod()?->id;
    }

    public function getEmployeeProperty(): ?Employee
    {
        if (blank($this->employeeId)) {
            return null;
        }

        return Employee::query()
            ->with(['designation', 'department', 'branch', 'employeeDeductions.deduction'])
            ->activeEmployment()
            ->find($this->employeeId);
    }

    public function getSelectedPeriodProperty(): ?PayrollPeriod
    {
        if (blank($this->period_id)) {
            return null;
        }

        return PayrollPeriod::query()->find($this->period_id);
    }

    public function getPayrollRowProperty(): ?array
    {
        if (! $this->employee || ! $this->selectedPeriod) {
            return null;
        }

        return app(PayrollCalculator::class)->row($this->employee, $this->selectedPeriod);
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => BranchPayrollEmployees::getUrl([
                    'periodId' => $this->period_id,
                    'branchId' => $this->branchId,
                ])),
        ];
    }
}
