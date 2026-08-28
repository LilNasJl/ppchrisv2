<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollPeriodEmployeeAdjustment;
use App\Services\PayrollCalculator;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\On;
use Override;

class EmployeePayroll extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.pages.employee-payroll';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Employee Payroll';

    public ?int $employeeId = null;

    public ?int $branchId = null;

    public ?int $period_id = null;

    public ?array $adjustments = [];

    public function mount(): void
    {
        $this->employeeId = Employee::resolvePublicId(request()->query('employeeId'));
        $this->branchId = Branch::resolvePublicId(request()->query('branchId'));
        $this->period_id = PayrollPeriod::resolvePublicId(request()->query('periodId')) ?: app(PayrollCalculator::class)->defaultPeriod()?->id;

        $this->fillAdjustmentForm();
    }

    protected function getFormStatePath(): ?string
    {
        return 'adjustments';
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Period Adjustments')
                ->schema([
                    TextInput::make('salary_adjustment')
                        ->label('Salary Adjustment')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(function (mixed $state): void {
                            $this->saveAdjustment('salary_adjustment', $state);
                        }),

                    TextInput::make('shortages')
                        ->label('Shortages')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(function (mixed $state): void {
                            $this->saveAdjustment('shortages', $state);
                        }),
                ])
                ->columns(2),
        ];
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

        if ($calculator->isEmployeePayrollExcluded($this->selectedPeriod, $this->employee)) {
            return null;
        }

        return $calculator->employeeRow($this->employee, $this->selectedPeriod);
    }

    public function saveAdjustment(string $field, mixed $state): void
    {
        if (! in_array($field, ['salary_adjustment', 'shortages'], true) || $this->isLocked()) {
            $this->fillAdjustmentForm();

            return;
        }

        if (! is_numeric($state) || (float) $state < 0) {
            Notification::make()
                ->title('Invalid payroll adjustment')
                ->body('Enter a value of zero or higher.')
                ->danger()
                ->send();

            $this->fillAdjustmentForm();

            return;
        }

        $adjustment = $this->adjustmentRecord();

        if (! $adjustment) {
            return;
        }

        $value = round((float) $state, 2);

        $adjustment->update([$field => $value]);
        $this->adjustments[$field] = $value;
        $this->dispatch('payroll-adjustment-updated');
    }

    #[On('payroll-adjustment-updated')]
    public function refreshPayrollRows(): void
    {
        //
    }

    protected function fillAdjustmentForm(): void
    {
        $adjustment = $this->adjustmentRecord();
        $row = $this->payrollRow;

        $this->form->fill([
            'salary_adjustment' => $adjustment?->salary_adjustment ?? ($row['salary_adjustment'] ?? 0),
            'shortages' => $adjustment?->shortages ?? ($row['shortages'] ?? 0),
        ]);
    }

    protected function adjustmentRecord(): ?PayrollPeriodEmployeeAdjustment
    {
        if (! $this->employee || ! $this->selectedPeriod) {
            return null;
        }

        if (app(PayrollCalculator::class)->isEmployeePayrollExcluded($this->selectedPeriod, $this->employee)) {
            return null;
        }

        $attributes = [
            'payroll_period_id' => $this->selectedPeriod->id,
            'employee_id' => $this->employee->id,
        ];

        $query = PayrollPeriodEmployeeAdjustment::query()->where($attributes);

        if ($this->isLocked()) {
            return $query->first();
        }

        return PayrollPeriodEmployeeAdjustment::query()->firstOrCreate($attributes, [
            'salary_adjustment' => 0,
            'shortages' => 0,
        ]);
    }

    protected function isLocked(): bool
    {
        return (bool) $this->selectedPeriod?->is_locked;
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('printPayslip')
                ->label('Print / PDF Payslip')
                ->icon(Heroicon::Printer)
                ->url(fn (): ?string => $this->selectedPeriod && $this->employee
                    ? route('payroll.payslip.print', [
                        'period' => $this->selectedPeriod->publicKey(),
                        'employee' => $this->employee->publicKey(),
                    ])
                    : null)
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled($this->selectedPeriod) && filled($this->employee) && filled($this->payrollRow)),

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
