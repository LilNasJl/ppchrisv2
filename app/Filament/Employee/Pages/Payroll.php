<?php

namespace App\Filament\Employee\Pages;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\PayrollCalculator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class Payroll extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.employee.pages.payroll';

    protected static ?string $slug = 'payroll';

    protected static ?string $title = 'Payroll';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'My Workspace';

    protected static ?int $navigationSort = 1;

    public ?string $period_id = null;

    public static function canAccess(): bool
    {
        return parent::canAccess() && (bool) auth()->user()?->can_view_payroll;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->can_view_payroll;
    }

    public function mount(): void
    {
        $employee = $this->employee;

        $this->period_id = (string) $this->availablePayrollPeriodsQuery($employee)
            ->newestFirst()
            ->value('id');

        $this->form->fill([
            'period_id' => $this->period_id,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printPayslip')
                ->label('Print / PDF Payslip')
                ->icon(Heroicon::Printer)
                ->url(fn (): ?string => $this->selectedPeriod
                    ? route('payroll.payslip.print', [
                        'period' => $this->selectedPeriod->publicKey(),
                    ])
                    : null)
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled($this->selectedPeriod) && filled($this->payrollRow)),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('period_id')
                ->label('Payroll Period')
                ->options(fn (): array => $this->availablePayrollPeriodsQuery()
                    ->newestFirst()
                    ->pluck('title', 'id')
                    ->all())
                ->searchable()
                ->reactive()
                ->placeholder('No payroll summary available yet'),
        ];
    }

    public function getEmployeeProperty(): ?Employee
    {
        return auth()->user()
            ?->employee()
            ->with(['designation', 'department', 'branch', 'employeeDeductions.deduction'])
            ->first();
    }

    public function getSelectedPeriodProperty(): ?PayrollPeriod
    {
        return filled($this->period_id)
            ? $this->availablePayrollPeriodsQuery()->find($this->period_id)
            : null;
    }

    public function getPayrollRowProperty(): ?array
    {
        if (! $this->employee || ! $this->selectedPeriod) {
            return null;
        }

        $calculator = app(PayrollCalculator::class);

        if ($calculator->isEmployeePayrollExcluded($this->selectedPeriod, $this->employee)) {
            return null;
        }

        return $calculator->employeeRow($this->employee, $this->selectedPeriod);
    }

    protected function availablePayrollPeriodsQuery(?Employee $employee = null): Builder
    {
        $employee ??= $this->employee;

        $query = PayrollPeriod::query()
            ->where('is_locked', true);

        if (! $employee) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereDoesntHave('branchExclusions', fn (Builder $query) => $query->where('branch_id', $employee->branch_id))
            ->whereDoesntHave('employeeExclusions', fn (Builder $query) => $query->where('employee_id', $employee->id));
    }
}
