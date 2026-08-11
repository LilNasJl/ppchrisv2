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

    public function mount(): void
    {
        $this->period_id = PayrollPeriod::resolvePublicId(request()->query('periodId')) ?: app(PayrollCalculator::class)->defaultPeriod()?->id;
        $this->branch_id = $this->resolveBranchId();
        $this->period_display = PayrollPeriod::query()->find($this->period_id)?->title ?? 'No payroll period selected';

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
                    ->options(fn (): array => app(PayrollCalculator::class)->branchOptionsForPeriod($this->selectedPeriod))
                    ->searchable()
                    ->reactive(),
            ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    public function getSelectedPeriodProperty(): ?PayrollPeriod
    {
        if (blank($this->period_id)) {
            return null;
        }

        return PayrollPeriod::query()->find($this->period_id);
    }

    public function getRowsProperty(): Collection
    {
        if (! $this->selectedPeriod || blank($this->branch_id)) {
            return collect();
        }

        return app(PayrollCalculator::class)->rows($this->selectedPeriod, (int) $this->branch_id);
    }

    protected function resolveBranchId(): ?string
    {
        $selectedPeriod = filled($this->period_id) ? PayrollPeriod::query()->find($this->period_id) : null;
        $branchOptions = app(PayrollCalculator::class)->branchOptionsForPeriod($selectedPeriod);
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
        // Re-render computed payroll rows after inline adjustments.
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
                ->url(fn (): string => filled($this->period_id)
                    ? PayrollPeriodBranches::getUrl(['periodId' => $this->selectedPeriod?->publicKey()])
                    : Payroll::getUrl()),

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
        $branch = Branch::query()->find($this->branch_id)?->branch_name ?? 'branch';
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
