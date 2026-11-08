<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\LoanType;
use App\Models\PayrollPeriod;
use App\Models\PayrollPeriodEmployeeAdjustment;
use App\Services\LoanPaymentBreakdown;
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
        $sys = fn (string $key, bool $isDays = false): string => $this->systemValueLabel($key, $isDays);

        return [
            Section::make('Editable Payroll Calculation Values & Adjustments')
                ->description('Modify any calculated value below to override the automatic DTR/master figure for this payroll period. Formulas for Gross Pay, Total Deductions, and Net Pay remain locked and recalculate automatically. Leave an override blank to use the automatic system value.')
                ->compact()
                ->collapsible()
                ->schema([
                    TextInput::make('days_worked_override')
                        ->label('Days Worked')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(31)
                        ->placeholder(fn (): string => $sys('days_worked', true))
                        ->helperText(fn (): string => 'Auto: '.$sys('days_worked', true).' days')
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('days_worked_override', $state)),

                    TextInput::make('salary_adjustment')
                        ->label('Salary Adjustment (+)')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->helperText('Additional period earnings adjustment')
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('salary_adjustment', $state)),

                    TextInput::make('allowance_override')
                        ->label('Allowance')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('allowance'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('allowance'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('allowance_override', $state)),

                    TextInput::make('overtime_amount_override')
                        ->label('Overtime Amount')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('overtime_amount'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('overtime_amount'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('overtime_amount_override', $state)),

                    TextInput::make('shift3_override')
                        ->label('Shift 3 Premium (10%)')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('shift3_premium'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('shift3_premium'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('shift3_override', $state)),

                    TextInput::make('regular_holiday_override')
                        ->label('Regular Holiday Pay')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('regular_holiday'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('regular_holiday'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('regular_holiday_override', $state)),

                    TextInput::make('special_holiday_override')
                        ->label('Special Holiday Pay')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('special_holiday'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('special_holiday'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('special_holiday_override', $state)),

                    TextInput::make('late_override')
                        ->label('Late Deduction')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('late'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('late'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('late_override', $state)),

                    TextInput::make('undertime_amount_override')
                        ->label('Undertime Deduction')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('undertime_amount'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('undertime_amount'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('undertime_amount_override', $state)),

                    TextInput::make('absent_override')
                        ->label('Absent Deduction')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('absent'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('absent'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('absent_override', $state)),

                    TextInput::make('halfday_override')
                        ->label('Half-Day Deduction')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('halfday'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('halfday'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('halfday_override', $state)),

                    TextInput::make('shortages')
                        ->label('Shortages (-)')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->helperText('Period shortage deduction')
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('shortages', $state)),

                    TextInput::make('uniform_override')
                        ->label('Uniform Deduction')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('uniform'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('uniform'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('uniform_override', $state)),

                    TextInput::make('other_deductions_override')
                        ->label('Other Deductions')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('other_deductions'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('other_deductions'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('other_deductions_override', $state)),

                    TextInput::make('sss_loan_override')
                        ->label('SSS Loan')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('sss_loan'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('sss_loan'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('sss_loan_override', $state)),

                    TextInput::make('sss_ee_override')
                        ->label('SSS EE')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('sss_ee'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('sss_ee'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('sss_ee_override', $state)),

                    TextInput::make('hdmf_loan_override')
                        ->label('HDMF Loan')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('hdmf_loan'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('hdmf_loan'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('hdmf_loan_override', $state)),

                    TextInput::make('hdmf_ee_override')
                        ->label('HDMF EE')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('hdmf_ee'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('hdmf_ee'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('hdmf_ee_override', $state)),

                    TextInput::make('phic_ee_override')
                        ->label('PHIC EE')
                        ->prefix('₱')
                        ->numeric()
                        ->minValue(0)
                        ->placeholder(fn (): string => $sys('phic_ee'))
                        ->helperText(fn (): string => 'Auto: ₱'.$sys('phic_ee'))
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('phic_ee_override', $state)),

                    TextInput::make('notes')
                        ->label('Correction / Audit Reason')
                        ->placeholder('Optional note explaining why values were adjusted...')
                        ->live(onBlur: true)
                        ->disabled(fn (): bool => $this->isLocked())
                        ->afterStateUpdated(fn (mixed $state) => $this->saveAdjustment('notes', $state)),
                ])
                ->columns(4),
        ];
    }

    protected function systemValueLabel(string $key, bool $isDays = false): string
    {
        $row = $this->payrollRow;
        $val = $row['system_values'][$key] ?? ($row[$key] ?? 0);

        if ($isDays) {
            return rtrim(rtrim(number_format((float) $val, 2, '.', ''), '0'), '.') ?: '0';
        }

        return number_format((float) $val, 2);
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

        return app(LoanPaymentBreakdown::class)->addColumns(
            $this->selectedPeriod,
            collect([$calculator->employeeRow($this->employee, $this->selectedPeriod)]),
            $this->loanTypeColumns(),
        )->first();
    }

    public function loanTypeColumns(): array
    {
        return LoanType::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function saveAdjustment(string $field, mixed $state): void
    {
        $overrideColumns = array_values(PayrollPeriodEmployeeAdjustment::OVERRIDABLE_FIELDS);
        $allowedFields = array_merge(['salary_adjustment', 'shortages', 'notes'], $overrideColumns);

        if (! in_array($field, $allowedFields, true) || $this->isLocked()) {
            $this->fillAdjustmentForm();

            return;
        }

        $adjustment = $this->adjustmentRecord();

        if (! $adjustment) {
            return;
        }

        if ($field === 'notes') {
            $value = filled($state) ? trim((string) $state) : null;
            $adjustment->update(['notes' => $value]);
            $this->adjustments['notes'] = $value;
            $this->dispatch('payroll-adjustment-updated');

            return;
        }

        if (in_array($field, $overrideColumns, true) && ($state === '' || $state === null)) {
            $adjustment->update([$field => null]);
            $this->adjustments[$field] = null;
            $this->dispatch('payroll-adjustment-updated');

            return;
        }

        if (! is_numeric($state) || (float) $state < 0) {
            Notification::make()
                ->title('Invalid payroll adjustment')
                ->body('Enter a value of zero or higher, or leave blank to use the automatic system value.')
                ->danger()
                ->send();

            $this->fillAdjustmentForm();

            return;
        }

        if ($field === 'days_worked_override' && (float) $state > 31) {
            Notification::make()
                ->title('Invalid days worked')
                ->body('Days worked cannot exceed 31 days.')
                ->danger()
                ->send();

            $this->fillAdjustmentForm();

            return;
        }

        $value = round((float) $state, 2);

        $adjustment->update([$field => $value]);
        $this->adjustments[$field] = $value;
        $this->dispatch('payroll-adjustment-updated');
    }

    public function clearOverride(string $rowKey): void
    {
        if ($this->isLocked()) {
            return;
        }

        $column = PayrollPeriodEmployeeAdjustment::OVERRIDABLE_FIELDS[$rowKey] ?? null;

        if (! $column) {
            return;
        }

        $adjustment = $this->adjustmentRecord();

        if (! $adjustment) {
            return;
        }

        $adjustment->update([$column => null]);
        $this->fillAdjustmentForm();
        $this->dispatch('payroll-adjustment-updated');
    }

    public function resetAllAdjustments(): void
    {
        if ($this->isLocked()) {
            return;
        }

        $adjustment = $this->adjustmentRecord();

        if (! $adjustment) {
            return;
        }

        $payload = [
            'salary_adjustment' => 0,
            'shortages' => 0,
            'notes' => null,
        ];

        foreach (PayrollPeriodEmployeeAdjustment::OVERRIDABLE_FIELDS as $column) {
            $payload[$column] = null;
        }

        $adjustment->update($payload);
        $this->fillAdjustmentForm();
        $this->dispatch('payroll-adjustment-updated');

        Notification::make()
            ->title('Reset to automatic system calculations')
            ->body('All manual overrides and period adjustments have been cleared.')
            ->success()
            ->send();
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

        $state = [
            'salary_adjustment' => $adjustment?->salary_adjustment ?? ($row['salary_adjustment'] ?? 0),
            'shortages' => $adjustment?->shortages ?? ($row['shortages'] ?? 0),
            'notes' => $adjustment?->notes,
        ];

        foreach (PayrollPeriodEmployeeAdjustment::OVERRIDABLE_FIELDS as $column) {
            $state[$column] = $adjustment?->{$column};
        }

        $this->form->fill($state);
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
            Action::make('resetAdjustments')
                ->label('Reset to System Values')
                ->icon(Heroicon::ArrowPath)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Reset Payroll to System Calculations?')
                ->modalDescription('This will clear all manual value overrides and period adjustments for this employee and restore the automatic DTR/master calculations.')
                ->action(fn () => $this->resetAllAdjustments())
                ->visible(fn (): bool => ! $this->isLocked() && (bool) ($this->payrollRow['is_adjusted'] ?? false)),

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
                ->url(fn (): string => Payroll::getUrl(array_filter([
                    'periodId' => PayrollPeriod::query()->find($this->period_id)?->publicKey(),
                    'branchId' => Branch::query()->find($this->branchId)?->publicKey(),
                ]))),
        ];
    }
}
