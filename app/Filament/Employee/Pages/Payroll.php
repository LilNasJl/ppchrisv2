<?php

namespace App\Filament\Employee\Pages;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\PayrollCalculator;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Payroll extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.employee.pages.payroll';

    protected static ?string $slug = 'payroll';

    protected static ?string $title = 'Payroll';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static ?int $navigationSort = 2;

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
        $this->period_id = (string) PayrollPeriod::query()
            ->where('is_locked', true)
            ->newestFirst()
            ->value('id');

        $this->form->fill([
            'period_id' => $this->period_id,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('period_id')
                ->label('Payroll Period')
                ->options(fn (): array => PayrollPeriod::query()
                    ->where('is_locked', true)
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
            ? PayrollPeriod::query()->where('is_locked', true)->find($this->period_id)
            : null;
    }

    public function getPayrollRowProperty(): ?array
    {
        if (! $this->employee || ! $this->selectedPeriod) {
            return null;
        }

        return app(PayrollCalculator::class)->row($this->employee, $this->selectedPeriod);
    }
}
