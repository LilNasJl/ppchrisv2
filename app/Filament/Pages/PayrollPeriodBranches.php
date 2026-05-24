<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PayrollPeriodBranchTable;
use App\Models\PayrollPeriod;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class PayrollPeriodBranches extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.payroll-period-branches';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Payroll Period';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    public ?int $periodId = null;

    public ?PayrollPeriod $period = null;

    public function mount(): void
    {
        $this->periodId = (int) request()->query('periodId');
        $this->period = PayrollPeriod::query()->find($this->periodId);
    }

    public function getTitle(): string
    {
        return $this->period?->title
            ? 'Payroll - '.$this->period->title
            : 'Payroll Period';
    }

    public function getWidgetData(): array
    {
        return [
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
                ->url(Payroll::getUrl()),

            ActionGroup::make([
                Action::make('payrollSummary')
                    ->label('Payroll Summary')
                    ->icon(Heroicon::ChartBar)
                    ->url(fn (): string => PayrollSummary::getUrl(['periodId' => $this->periodId])),

                Action::make('payrollByBranch')
                    ->label('Payroll By Branch')
                    ->icon(Heroicon::BuildingStorefront)
                    ->url(fn (): string => PayrollByBranch::getUrl(['periodId' => $this->periodId])),

                Action::make('payrollRoster')
                    ->label('Payroll Roster')
                    ->icon(Heroicon::ClipboardDocumentList)
                    ->url(fn (): string => PayrollRoster::getUrl(['periodId' => $this->periodId])),

                Action::make('payrollCalculation')
                    ->label('Payroll Calculation')
                    ->icon(Heroicon::Cog6Tooth)
                    ->url(fn (): string => PayrollCalculation::getUrl(['periodId' => $this->periodId])),
            ])
                ->label('Manage Payroll')
                ->icon(Heroicon::ChevronDown)
                ->button(),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            PayrollPeriodBranchTable::class,
        ];
    }
}
