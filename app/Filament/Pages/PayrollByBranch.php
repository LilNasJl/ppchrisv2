<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\PayrollPeriod;
use App\Models\PayrollSignatory;
use App\Services\PayrollCalculator;
use App\Support\CompanyExportHeader;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Group;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Override;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollByBranch extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.pages.payroll-by-branch';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Payroll By Branch';

    public ?string $period_id = null;

    public ?string $branch_id = null;

    public ?string $period_display = null;

    public ?string $prepared_by = null;

    public ?string $checked_by = null;

    public ?string $approved_by = null;

    public ?string $payroll_search = null;

    public int $payroll_page = 1;

    public int $payroll_per_page = 10;

    public string $payroll_preset = 'all';

    protected ?PayrollPeriod $cachedSelectedPeriod = null;

    protected ?string $cachedSelectedPeriodKey = null;

    protected ?Branch $cachedSelectedBranch = null;

    protected ?string $cachedSelectedBranchKey = null;

    protected ?array $cachedBranchOptions = null;

    protected ?string $cachedBranchOptionsKey = null;

    protected ?Collection $cachedRows = null;

    protected ?string $cachedRowsKey = null;

    #[Override]
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    #[Override]
    public function getHeading(): string
    {
        return '';
    }

    #[Override]
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function mount(): void
    {
        $resolvedPeriodId = PayrollPeriod::resolvePublicId(request()->query('periodId')) ?: app(PayrollCalculator::class)->defaultPeriod()?->id;
        $this->period_id = filled($resolvedPeriodId) ? (string) $resolvedPeriodId : null;
        $this->branch_id = $this->resolveBranchId();
        $this->period_display = $this->selectedPeriod?->title ?? 'No payroll period selected';
        $this->payroll_search = str(request()->query('payrollSearch', ''))->limit(120)->toString();
        $this->payroll_page = max(1, request()->integer('payrollPage', 1));
        $requestedPerPage = request()->integer('payrollPerPage', 10);
        $this->payroll_per_page = in_array($requestedPerPage, [10, 25, 50, 100], true)
            ? $requestedPerPage
            : 10;
        $requestedPreset = (string) request()->query('payrollPreset', 'all');
        $this->payroll_preset = in_array($requestedPreset, ['summary', 'earnings', 'deductions', 'remittances', 'all'], true)
            ? $requestedPreset
            : 'all';

        $this->form->fill([
            'period_display' => $this->period_display,
            'branch_id' => $this->branch_id,
        ]);

        $this->loadSignatories();
    }

    protected function getFormSchema(): array
    {
        return [
            Group::make([
                TextInput::make('period_display')
                    ->label('Payroll Period')
                    ->disabled()
                    ->dehydrated(false),

                Select::make('branch_id')
                    ->label('Branch')
                    ->options(fn (): array => $this->branchOptions())
                    ->searchable()
                    ->reactive(),
            ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    /**
     * @return Collection<int, PayrollPeriod>
     */
    public function payrollPeriods(): Collection
    {
        return PayrollPeriod::query()
            ->newestFirst()
            ->get();
    }

    /**
     * @return array<int|string, string>
     */
    public function branchOptions(): array
    {
        $key = (string) ($this->period_id ?? '');

        if ($this->cachedBranchOptions !== null && $this->cachedBranchOptionsKey === $key) {
            return $this->cachedBranchOptions;
        }

        $this->cachedBranchOptionsKey = $key;

        return $this->cachedBranchOptions = app(PayrollCalculator::class)->branchOptionsForPeriod($this->selectedPeriod);
    }

    public function selectedBranch(): ?Branch
    {
        if (blank($this->branch_id)) {
            return null;
        }

        $key = (string) $this->branch_id;

        if ($this->cachedSelectedBranchKey === $key) {
            return $this->cachedSelectedBranch;
        }

        $this->cachedSelectedBranchKey = $key;

        return $this->cachedSelectedBranch = Branch::query()->find((int) $this->branch_id);
    }

    public function updatedPeriodId(): void
    {
        $this->cachedRows = null;
        $this->cachedRowsKey = null;
        $period = $this->selectedPeriod;
        $this->period_display = $period?->title ?? 'No payroll period selected';

        $branchOptions = $this->branchOptions();
        if (! $this->branch_id || ! array_key_exists((int) $this->branch_id, $branchOptions)) {
            $firstKey = array_key_first($branchOptions);
            $this->branch_id = filled($firstKey) ? (string) $firstKey : null;
        }

        $this->payroll_page = 1;
    }

    public function updatedBranchId(): void
    {
        $this->cachedRows = null;
        $this->cachedRowsKey = null;
        $this->payroll_page = 1;
    }

    public function previousBranchId(): ?string
    {
        $keys = array_map('strval', array_keys($this->branchOptions()));
        $index = array_search((string) $this->branch_id, $keys, true);

        if ($index === false || $index <= 0) {
            return null;
        }

        return $keys[$index - 1] ?? null;
    }

    public function nextBranchId(): ?string
    {
        $keys = array_map('strval', array_keys($this->branchOptions()));
        $index = array_search((string) $this->branch_id, $keys, true);

        if ($index === false || $index >= count($keys) - 1) {
            return null;
        }

        return $keys[$index + 1] ?? null;
    }

    public function selectPreviousBranch(): void
    {
        $prev = $this->previousBranchId();
        if (filled($prev)) {
            $this->branch_id = $prev;
            $this->payroll_page = 1;
        }
    }

    public function selectNextBranch(): void
    {
        $next = $this->nextBranchId();
        if (filled($next)) {
            $this->branch_id = $next;
            $this->payroll_page = 1;
        }
    }

    public function setPreset(string $preset): void
    {
        if (! in_array($preset, ['summary', 'earnings', 'deductions', 'remittances', 'all'], true)) {
            return;
        }

        $this->payroll_preset = $preset;
        $this->dispatch('payroll-column-preset-changed', preset: $preset);
    }

    #[On('payroll-column-preset-changed')]
    public function syncPresetState(string $preset): void
    {
        if (in_array($preset, ['summary', 'earnings', 'deductions', 'remittances', 'all'], true)) {
            $this->payroll_preset = $preset;
        }
    }

    public function getSelectedPeriodProperty(): ?PayrollPeriod
    {
        if (blank($this->period_id)) {
            return null;
        }

        $key = (string) $this->period_id;

        if ($this->cachedSelectedPeriodKey === $key) {
            return $this->cachedSelectedPeriod;
        }

        $this->cachedSelectedPeriodKey = $key;

        return $this->cachedSelectedPeriod = PayrollPeriod::query()->find($this->period_id);
    }

    public function getRowsProperty(): Collection
    {
        if (! $this->selectedPeriod || blank($this->branch_id)) {
            return collect();
        }

        $key = $this->selectedPeriod->id.'|'.(int) $this->branch_id;

        if ($this->cachedRows !== null && $this->cachedRowsKey === $key) {
            return $this->cachedRows;
        }

        $this->cachedRowsKey = $key;

        return $this->cachedRows = app(PayrollCalculator::class)->rows($this->selectedPeriod, (int) $this->branch_id);
    }

    /**
     * @return array{employees_count: int, gross_pay: float, total_deductions: float, net_pay: float}
     */
    public function branchMetrics(): array
    {
        $rows = $this->rows;

        return [
            'employees_count' => $rows->count(),
            'gross_pay' => (float) $rows->sum(fn (array $row): float => (float) ($row['gross_pay'] ?? 0)),
            'total_deductions' => (float) $rows->sum(fn (array $row): float => (float) ($row['total_deductions'] ?? 0)),
            'net_pay' => (float) $rows->sum(fn (array $row): float => (float) ($row['net_pay'] ?? 0)),
        ];
    }

    public function returnUrl(): string
    {
        return Payroll::getUrl(array_filter([
            'periodId' => $this->selectedPeriod?->publicKey(),
            'branchId' => $this->selectedBranch()?->publicKey(),
        ]));
    }

    public function payrollSummaryUrl(): ?string
    {
        return $this->selectedPeriod
            ? PayrollSummary::getUrl(['periodId' => $this->selectedPeriod->publicKey()])
            : null;
    }

    public function payrollCalculationUrl(): ?string
    {
        return $this->selectedPeriod
            ? PayrollCalculation::getUrl(['periodId' => $this->selectedPeriod->publicKey()])
            : null;
    }

    public function printUrl(): ?string
    {
        if (blank($this->period_id) || blank($this->branch_id)) {
            return null;
        }

        return route('hr_tools.payroll_by_branch.print', [
            'period_id' => $this->period_id,
            'branch_id' => $this->branch_id,
        ]);
    }

    protected function resolveBranchId(): ?string
    {
        $branchOptions = $this->branchOptions();
        $requestedBranchId = Branch::resolvePublicId(request()->query('branchId'));

        if ($requestedBranchId && array_key_exists($requestedBranchId, $branchOptions)) {
            return (string) $requestedBranchId;
        }

        $firstBranchId = array_key_first($branchOptions);

        return filled($firstBranchId) ? (string) $firstBranchId : null;
    }

    #[On('payroll-adjustment-updated')]
    public function refreshPayrollRows(): void
    {
        $this->cachedRows = null;
        $this->cachedRowsKey = null;
    }

    public function exportExcel(): StreamedResponse
    {
        return $this->downloadExcel();
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => $this->returnUrl()),

            Action::make('signatories')
                ->label('Signatories')
                ->icon(Heroicon::UserGroup)
                ->schema($this->getSignatoryFormSchema())
                ->fillForm(fn (): array => $this->getSignatoryData())
                ->modalHeading('Edit Signatories')
                ->modalSubmitActionLabel('Save')
                ->action(function (array $data): void {
                    $this->prepared_by = $data['prepared_by'] ?: 'Prepared By';
                    $this->checked_by = $data['checked_by'] ?: 'Checked By';
                    $this->approved_by = $data['approved_by'] ?: 'Approved By';

                    PayrollSignatory::default()->update($this->getSignatoryData());

                    Notification::make()
                        ->title('Signatories updated')
                        ->success()
                        ->send();
                }),

            ActionGroup::make([
                Action::make('exportExcel')
                    ->label('Export Excel')
                    ->icon(Heroicon::TableCells)
                    ->action(fn (): StreamedResponse => $this->exportExcel()),

                Action::make('print')
                    ->label('Print / PDF')
                    ->icon(Heroicon::Printer)
                    ->url(fn (): string => route('hr_tools.payroll_by_branch.print', [
                        'period_id' => $this->period_id,
                        'branch_id' => $this->branch_id,
                    ]))
                    ->openUrlInNewTab(),
            ])
                ->label('Export')
                ->icon(Heroicon::ChevronDown)
                ->button(),
        ];
    }

    protected function getSignatoryFormSchema(): array
    {
        return [
            Group::make([
                TextInput::make('prepared_by')
                    ->label('Prepared By')
                    ->required(),

                TextInput::make('checked_by')
                    ->label('Checked By')
                    ->required(),

                TextInput::make('approved_by')
                    ->label('Approved By')
                    ->required(),
            ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    protected function getSignatoryData(): array
    {
        return [
            'prepared_by' => $this->prepared_by,
            'checked_by' => $this->checked_by,
            'approved_by' => $this->approved_by,
        ];
    }

    protected function loadSignatories(): void
    {
        $signatory = PayrollSignatory::default();

        $this->prepared_by = $signatory->prepared_by;
        $this->checked_by = $signatory->checked_by;
        $this->approved_by = $signatory->approved_by;
    }

    protected function downloadExcel(): StreamedResponse
    {
        $headers = app(PayrollCalculator::class)->payrollHeaders();
        $period = $this->selectedPeriod?->title ?? 'payroll';
        $branch = $this->selectedBranch()?->branch_name ?? 'branch';
        $filename = 'payroll-'.str($branch)->slug().'-'.str($period)->slug().'.xls';

        return response()->streamDownload(function () use ($headers): void {
            echo CompanyExportHeader::excelHtml(count($headers));
            echo CompanyExportHeader::exportTitleHtml('Payroll By Branch', count($headers));
            echo '<table '.CompanyExportHeader::tableAttributes().'><thead><tr>';

            foreach ($headers as $label) {
                echo '<th '.CompanyExportHeader::thStyle().'>'.e($label).'</th>';
            }

            echo '</tr></thead><tbody>';

            foreach ($this->rows as $row) {
                echo '<tr>';

                foreach (array_keys($headers) as $key) {
                    echo '<td '.CompanyExportHeader::tdStyle().'>'.e((string) ($row[$key] ?? '')).'</td>';
                }

                echo '</tr>';
            }

            echo '</tbody></table><br>';
            echo '<br><table '.CompanyExportHeader::tableAttributes().'>';
            echo '<tr><th '.CompanyExportHeader::thStyle().'>Prepared by</th><th '.CompanyExportHeader::thStyle().'>Checked by</th><th '.CompanyExportHeader::thStyle().'>Approved by</th></tr>';
            echo '<tr><td '.CompanyExportHeader::tdStyle().'>'.e($this->prepared_by).'</td><td '.CompanyExportHeader::tdStyle().'>'.e($this->checked_by).'</td><td '.CompanyExportHeader::tdStyle().'>'.e($this->approved_by).'</td></tr>';
            echo '</table>';
            echo CompanyExportHeader::generatedAtHtml();
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }
}
