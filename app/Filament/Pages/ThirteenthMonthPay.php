<?php

namespace App\Filament\Pages;

use App\Services\ThirteenthMonthPayService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Group;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Override;
use UnitEnum;

class ThirteenthMonthPay extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.pages.thirteenth-month-pay';

    protected static ?string $title = '13th Month Pay';

    protected static ?string $navigationLabel = '13th Month Pay';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Compensation & Benefits Mngt';

    protected static ?int $navigationSort = 3;

    public ?string $year = null;

    public ?string $calculation_type = ThirteenthMonthPayService::MIDYEAR;

    public ?string $branch_id = null;

    public string $view_mode = 'details';

    public int $results_version = 0;

    public function mount(): void
    {
        $service = app(ThirteenthMonthPayService::class);
        $this->year = (string) array_key_first($service->yearOptions());
        $this->calculation_type = now()->month <= 6 ? $service::MIDYEAR : $service::YEAR_END;

        $this->form->fill([
            'year' => $this->year,
            'calculation_type' => $this->calculation_type,
            'branch_id' => $this->branch_id,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Group::make([
                Select::make('year')
                    ->label('Year')
                    ->options(fn (): array => app(ThirteenthMonthPayService::class)->yearOptions())
                    ->required()
                    ->live(),

                Select::make('calculation_type')
                    ->label('Payout')
                    ->options(fn (): array => app(ThirteenthMonthPayService::class)->segmentOptions())
                    ->required()
                    ->selectablePlaceholder(false)
                    ->live(),

                Select::make('branch_id')
                    ->label('Branch')
                    ->options(fn (): array => app(ThirteenthMonthPayService::class)->branchOptions())
                    ->placeholder('All Branches')
                    ->searchable()
                    ->live(),
            ])
                ->columns(['default' => 1, 'md' => 3])
                ->columnSpanFull(),
        ];
    }

    public function getRowsProperty(): Collection
    {
        if (blank($this->year) || ! $this->hasValidPayout()) {
            return collect();
        }

        return $this->service()->rows(
            (int) $this->year,
            $this->calculation_type,
            filled($this->branch_id) ? (int) $this->branch_id : null,
        );
    }

    public function getSummaryRowsProperty(): Collection
    {
        return $this->service()->summaryRows($this->rows);
    }

    public function getMonthLabelsProperty(): array
    {
        if (! $this->hasValidPayout()) {
            return [];
        }

        return $this->service()->monthLabels($this->calculation_type);
    }

    public function getPeriodColumnsProperty(): array
    {
        if (blank($this->year) || ! $this->hasValidPayout()) {
            return [];
        }

        return $this->service()->periodColumns((int) $this->year, $this->calculation_type);
    }

    public function getSegmentLabelProperty(): string
    {
        if (! $this->hasValidPayout()) {
            return 'Select a payout';
        }

        return $this->service()->segmentLabel($this->calculation_type);
    }

    public function getPeriodLabelProperty(): string
    {
        if (blank($this->year) || ! $this->hasValidPayout()) {
            return 'Select a year and payout';
        }

        return $this->service()->periodLabel((int) $this->year, $this->calculation_type);
    }

    public function getDivisorProperty(): int
    {
        return ThirteenthMonthPayService::DIVISOR;
    }

    public function getReleaseConflictProperty(): ?string
    {
        if (! $this->hasValidPayout()) {
            return 'Select a valid payout before recording payment.';
        }

        return blank($this->year)
            ? null
            : $this->service()->releaseConflict((int) $this->year, $this->calculation_type);
    }

    public function showDetails(): void
    {
        $this->view_mode = 'details';
    }

    public function showSummary(): void
    {
        $this->view_mode = 'summary';
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageRelease')
                ->label('Record Payout')
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Record paid 13th month benefit')
                ->modalDescription(fn (): string => 'Record '.number_format($this->rows->where('can_release', true)->sum('pending_amount'), 2).' for '.$this->rows->where('can_release', true)->count().' employee(s) in the current filters. Previous payments are deducted automatically. This records payment history only; it does not transfer money. Existing payout records will not be overwritten.')
                ->modalSubmitActionLabel('Confirm Paid')
                ->modalCancelActionLabel('Cancel')
                ->modalWidth('md')
                ->disabled(fn (): bool => ! $this->rows->contains('can_release', true) || filled($this->releaseConflict))
                ->action(function (): void {
                    abort_unless(static::canAccess(), 403);
                    try {
                        $affected = $this->service()->setReleaseStatus(
                            $this->rows,
                            (int) $this->year,
                            $this->calculation_type,
                            auth()->id(),
                            'released',
                        );
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()
                            ->title('Release action unavailable')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->refreshCalculationResults();

                    Notification::make()
                        ->title('Payout recorded')
                        ->body($affected.' new payout record(s) saved. Previous payment history is unchanged.')
                        ->success()
                        ->send();
                }),

            ActionGroup::make([
                Action::make('printDetails')
                    ->label('Print Employee Details')
                    ->icon(Heroicon::Printer)
                    ->url(fn (): string => $this->printUrl('details'))
                    ->openUrlInNewTab(),

                Action::make('printSummary')
                    ->label('Print Summary')
                    ->icon(Heroicon::DocumentChartBar)
                    ->url(fn (): string => $this->printUrl('summary'))
                    ->openUrlInNewTab(),
            ])
                ->label('Print / PDF')
                ->icon(Heroicon::ChevronDown)
                ->button(),
        ];
    }

    protected function printUrl(string $view): string
    {
        return route('hr_tools.thirteenth_month.print', array_filter([
            'year' => $this->year,
            'type' => $this->calculation_type,
            'branch_id' => $this->branch_id,
            'view' => $view,
        ], fn ($value): bool => filled($value)));
    }

    protected function service(): ThirteenthMonthPayService
    {
        return app(ThirteenthMonthPayService::class);
    }

    protected function hasValidPayout(): bool
    {
        return $this->calculation_type !== null
            && array_key_exists($this->calculation_type, $this->service()->segmentOptions());
    }

    protected function refreshCalculationResults(): void
    {
        unset($this->rows, $this->summaryRows, $this->releaseConflict);
        $this->results_version++;
    }
}
