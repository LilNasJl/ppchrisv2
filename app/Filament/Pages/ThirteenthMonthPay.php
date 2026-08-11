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

    protected static string|UnitEnum|null $navigationGroup = 'Compensation and Benefits Management';

    protected static ?int $navigationSort = 7;

    public ?string $year = null;

    public string $calculation_type = ThirteenthMonthPayService::MIDYEAR;

    public ?string $branch_id = null;

    public string $view_mode = 'details';

    public int $results_version = 0;

    public function mount(): void
    {
        $service = app(ThirteenthMonthPayService::class);
        $this->year = (string) array_key_first($service->yearOptions());

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
                    ->label('Calculation Type')
                    ->options(fn (): array => app(ThirteenthMonthPayService::class)->segmentOptions())
                    ->required()
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
        if (blank($this->year)) {
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
        return $this->service()->monthLabels($this->calculation_type);
    }

    public function getPeriodColumnsProperty(): array
    {
        return $this->service()->periodColumns((int) $this->year, $this->calculation_type);
    }

    public function getSegmentLabelProperty(): string
    {
        return $this->service()->segmentLabel($this->calculation_type);
    }

    public function getPeriodLabelProperty(): string
    {
        return $this->service()->periodLabel((int) $this->year, $this->calculation_type);
    }

    public function getDivisorProperty(): int
    {
        return $this->service()->divisor($this->calculation_type);
    }

    public function getReleaseConflictProperty(): ?string
    {
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
                ->label(fn (): string => $this->rows->contains(fn (array $row): bool => $row['pending_amount'] > 0)
                    ? 'Release Current Results'
                    : 'Manage Release')
                ->icon(fn (): Heroicon => $this->rows->contains(fn (array $row): bool => $row['pending_amount'] > 0)
                    ? Heroicon::CheckCircle
                    : Heroicon::Cog6Tooth)
                ->color(fn (): string => $this->rows->contains(fn (array $row): bool => $row['pending_amount'] > 0)
                    ? 'success'
                    : 'gray')
                ->schema([
                    Select::make('release_status')
                        ->label('Release Action')
                        ->options([
                            'released' => 'Release or refresh current results',
                            'pending' => 'Reopen current results and set to Pending',
                        ])
                        ->helperText('The action applies only to employees shown by the current year, calculation type, and branch filters.')
                        ->required(),
                ])
                ->fillForm(fn (): array => [
                    'release_status' => $this->rows->contains(fn (array $row): bool => $row['pending_amount'] > 0)
                        ? 'released'
                        : 'pending',
                ])
                ->modalHeading('Manage current release')
                ->modalDescription(fn (): string => 'Manage '.$this->segmentLabel.' for '.$this->periodLabel.'. Releasing stores the current locked-payroll calculation. Reopening removes only this calculation type\'s release records.')
                ->modalSubmitActionLabel('Confirm Action')
                ->modalCancelActionLabel('Cancel')
                ->modalWidth('md')
                ->visible(fn (): bool => $this->rows->isNotEmpty() && blank($this->releaseConflict))
                ->action(function (array $data): void {
                    try {
                        $affected = $this->service()->setReleaseStatus(
                            $this->rows,
                            (int) $this->year,
                            $this->calculation_type,
                            auth()->id(),
                            $data['release_status'],
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
                        ->title('Release status updated')
                        ->body($affected.' release record(s) affected for the current results.')
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

    protected function refreshCalculationResults(): void
    {
        unset($this->rows, $this->summaryRows, $this->releaseConflict);
        $this->results_version++;
    }
}
