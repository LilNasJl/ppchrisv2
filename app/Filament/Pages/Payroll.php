<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PayrollPeriods\PayrollPeriodResource;
use App\Models\PayrollPeriod;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Override;
use UnitEnum;

class Payroll extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.pages.payroll';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Compensation and Benefits Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Payroll Processing';

    public ?int $periodId = null;

    #[Override]
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function mount(): void
    {
        $this->form->fill(['periodId' => null]);
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('periodId')
                ->label('Search Payroll Period')
                ->options(fn (): array => PayrollPeriod::query()
                    ->newestFirst()
                    ->get()
                    ->mapWithKeys(fn (PayrollPeriod $period): array => [
                        $period->id => trim($period->title.' - '.($period->is_locked ? 'Locked' : 'Open')),
                    ])
                    ->all())
                ->searchable()
                ->preload()
                ->placeholder('Select payroll period')
                ->live()
                ->afterStateUpdated(function ($state): void {
                    $period = filled($state) ? PayrollPeriod::query()->find((int) $state) : null;

                    if (! $period) {
                        return;
                    }

                    $this->redirect(PayrollPeriodBranches::getUrl([
                        'periodId' => $period->publicKey(),
                    ]));
                }),
        ];
    }

    public function managePayrollPeriodsUrl(): string
    {
        return PayrollPeriodResource::getUrl();
    }

    public function payrollVisibilityUrl(): string
    {
        return PayrollVisibility::getUrl();
    }
}
