<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PayrollRosterTable;
use App\Models\PayrollPeriod;
use App\Services\PayrollCalculator;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class PayrollRosterEmployeeExemptions extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.payroll-roster-table';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Employee Payroll Roster Exemptions';

    protected static ?string $slug = 'payroll-roster/employee-exemptions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserMinus;

    public ?int $periodId = null;

    public ?PayrollPeriod $period = null;

    public function mount(): void
    {
        $this->periodId = PayrollPeriod::resolvePublicId(request()->query('periodId')) ?: app(PayrollCalculator::class)->defaultPeriod()?->id;
        $this->period = filled($this->periodId) ? PayrollPeriod::query()->find($this->periodId) : null;
    }

    public function getTitle(): string
    {
        return $this->period?->title
            ? 'Employee Exemptions - '.$this->period->title
            : 'Employee Payroll Roster Exemptions';
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
                ->url(fn (): string => PayrollRoster::getUrl(['periodId' => $this->period?->publicKey()])),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            PayrollRosterTable::class,
        ];
    }
}
