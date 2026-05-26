<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DtrPeriodBranchTable;
use App\Models\PayrollPeriod;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class DtrPeriodBranches extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.dtr-period-branches';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'D.T.R Period';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::QueueList;

    public ?int $periodId = null;

    public ?PayrollPeriod $period = null;

    public function mount(): void
    {
        $this->periodId = PayrollPeriod::resolvePublicId(request()->query('periodId'));
        $this->period = filled($this->periodId) ? PayrollPeriod::query()->find($this->periodId) : null;
    }

    public function getTitle(): string
    {
        return $this->period?->title
            ? 'D.T.R - '.$this->period->title
            : 'D.T.R Period';
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
                ->url(Dtr::getUrl()),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            DtrPeriodBranchTable::class,
        ];
    }
}
