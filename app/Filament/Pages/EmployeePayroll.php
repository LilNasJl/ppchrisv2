<?php

namespace App\Filament\Pages;

use App\Models\Branch;
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

    public ?int $employeeId = null;

    public ?int $branchId = null;

    public ?int $period_id = null;

    public function mount(): void
    {
        $this->employeeId = Employee::resolvePublicId(request()->query('employeeId'));
        $this->branchId = Branch::resolvePublicId(request()->query('branchId'));
        $this->period_id = PayrollPeriod::resolvePublicId(request()->query('periodId')) ?: app(PayrollCalculator::class)->defaultPeriod()?->id;
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

        $calculator = app(PayrollCalculator::class);

        if (
            $calculator->isBranchExcluded($this->selectedPeriod, $this->employee->branch_id)
            || $calculator->isEmployeeExcluded($this->selectedPeriod, $this->employee->id)
        ) {
            return null;
        }

        return $calculator->row($this->employee, $this->selectedPeriod);
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => BranchPayrollEmployees::getUrl([
                    'periodId' => PayrollPeriod::query()->find($this->period_id)?->publicKey(),
                    'branchId' => Branch::query()->find($this->branchId)?->publicKey(),
                ])),
        ];
    }
}
