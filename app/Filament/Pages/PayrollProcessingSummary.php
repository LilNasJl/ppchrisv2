<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Department;
use App\Models\PayrollPeriod;
use App\Models\PayrollSignatory;
use App\Models\PayrollSummaryScope;
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
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Override;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollProcessingSummary extends Page implements HasForms
{
    use HasPageShield {
        canAccess as protected canAccessWithShield;
    }
    use InteractsWithForms;

    protected string $view = 'filament.pages.payroll-summary';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'payroll-processing-summary';

    protected static ?string $title = 'Payroll Summary';

    public ?string $period_id = null;

    public string $group_by = 'branch';

    public array $included_ids = [];

    public array $branch_ids = [];

    public array $department_ids = [];

    public ?string $prepared_by = null;

    public ?string $checked_by = null;

    public ?string $approved_by = null;

    protected ?PayrollPeriod $cachedSelectedPeriod = null;

    protected ?string $cachedSelectedPeriodKey = null;

    protected ?Collection $cachedSummaryRows = null;

    protected ?string $cachedSummaryRowsKey = null;

    public static function canAccess(): bool
    {
        return static::canAccessWithShield() || Payroll::canAccess() || PayrollSummary::canAccess();
    }

    public function mount(): void
    {
        $periodId = PayrollPeriod::resolvePublicId(request()->query('periodId'))
            ?: app(PayrollCalculator::class)->defaultPeriod()?->id;

        $this->period_id = filled($periodId) ? (string) $periodId : null;

        $this->loadSummaryScope();

        $this->fillFilterForm();

        $this->loadSignatories();
    }

    protected function stringArrayStateCast(): StateCast
    {
        return new class implements StateCast
        {
            public function get(mixed $state): array
            {
                if (blank($state)) {
                    return [];
                }

                $items = is_array($state) ? $state : (json_decode((string) $state, true) ?: [$state]);

                return array_values(array_unique(array_filter(array_map('strval', $items))));
            }

            public function set(mixed $state): mixed
            {
                if (blank($state)) {
                    return [];
                }

                $items = is_array($state) ? $state : (json_decode((string) $state, true) ?: [$state]);

                return array_values(array_unique(array_filter(array_map('strval', $items))));
            }
        };
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('period_id')
                ->label('Payroll Period')
                ->options(fn (): array => app(PayrollCalculator::class)->periodOptions())
                ->searchable()
                ->reactive(),

            View::make('filament.pages.partials.payroll-summary-filter-modal')
                ->schema([
                    Select::make('branch_ids')
                        ->label('Branch Selection')
                        ->placeholder('All branches (or select specific branches)')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->stateCast($this->stringArrayStateCast())
                        ->options(function (Get $get): array {
                            $selected = array_values(array_unique(array_filter(array_map('intval', (array) ($get('branch_ids') ?? $this->branch_ids)))));

                            return Branch::query()
                                ->when($selected !== [], fn ($query) => $query->whereNotIn('id', $selected))
                                ->orderBy('branch_name')
                                ->pluck('branch_name', 'id')
                                ->mapWithKeys(fn ($name, $id) => [(string) $id => $name])
                                ->all();
                        })
                        ->getOptionLabelsUsing(fn (array $values): array => Branch::query()
                            ->whereIn('id', array_map('intval', $values))
                            ->pluck('branch_name', 'id')
                            ->mapWithKeys(fn ($name, $id) => [(string) $id => $name])
                            ->all())
                        ->reactive(),

                    Select::make('department_ids')
                        ->label('Department Selection')
                        ->placeholder('All departments (or select specific departments)')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->stateCast($this->stringArrayStateCast())
                        ->options(function (Get $get): array {
                            $selected = array_values(array_unique(array_filter(array_map('intval', (array) ($get('department_ids') ?? $this->department_ids)))));

                            return Department::query()
                                ->when($selected !== [], fn ($query) => $query->whereNotIn('id', $selected))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->mapWithKeys(fn ($name, $id) => [(string) $id => $name])
                                ->all();
                        })
                        ->getOptionLabelsUsing(fn (array $values): array => Department::query()
                            ->whereIn('id', array_map('intval', $values))
                            ->pluck('name', 'id')
                            ->mapWithKeys(fn ($name, $id) => [(string) $id => $name])
                            ->all())
                        ->reactive(),
                ]),
        ];
    }

    protected function fillFilterForm(): void
    {
        $this->form->fill([
            'period_id' => $this->period_id,
            'group_by' => $this->group_by,
            'included_ids' => $this->included_ids,
            'branch_ids' => array_values(array_map('strval', $this->branch_ids)),
            'department_ids' => array_values(array_map('strval', $this->department_ids)),
        ]);
    }

    public function updatedPeriodId(): void
    {
        $this->loadSummaryScope();
        $this->cachedSummaryRows = null;
        $this->fillFilterForm();
    }

    public function updatedGroupBy(): void
    {
        $this->included_ids = [];
        $this->branch_ids = [];
        $this->department_ids = [];
        $this->cachedSummaryRows = null;
    }

    public function updatedIncludedIds(): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $this->included_ids))));

        if ($this->group_by === 'department') {
            $this->department_ids = array_values(array_map('strval', $ids));
            $this->branch_ids = [];
        } else {
            $this->branch_ids = array_values(array_map('strval', $ids));
            $this->department_ids = [];
        }

        $this->cachedSummaryRows = null;
    }

    public function updatedBranchIds(): void
    {
        $this->branch_ids = array_values(array_unique(array_filter(array_map('strval', $this->branch_ids))));
        $this->syncLegacyScopeProperties();
        $this->cachedSummaryRows = null;
    }

    public function updatedDepartmentIds(): void
    {
        $this->department_ids = array_values(array_unique(array_filter(array_map('strval', $this->department_ids))));
        $this->syncLegacyScopeProperties();
        $this->cachedSummaryRows = null;
    }

    public function clearFilterSelection(): void
    {
        $this->branch_ids = [];
        $this->department_ids = [];
        $this->included_ids = [];
        $this->group_by = 'branch';
        $this->cachedSummaryRows = null;
        $this->fillFilterForm();
    }

    protected function syncLegacyScopeProperties(): void
    {
        $intDeptIds = array_values(array_unique(array_filter(array_map('intval', $this->department_ids))));
        $intBranchIds = array_values(array_unique(array_filter(array_map('intval', $this->branch_ids))));

        if ($intDeptIds !== [] && $intBranchIds === []) {
            $this->group_by = 'department';
            $this->included_ids = $intDeptIds;
        } elseif ($intBranchIds !== [] && $intDeptIds === []) {
            $this->group_by = 'branch';
            $this->included_ids = $intBranchIds;
        } else {
            $this->included_ids = $intDeptIds !== [] ? $intDeptIds : $intBranchIds;
        }
    }

    protected function loadSummaryScope(): void
    {
        $scope = filled($this->period_id)
            ? PayrollSummaryScope::query()->where('payroll_period_id', $this->period_id)->first()
            : null;

        $this->group_by = $scope?->group_by ?: 'branch';
        $this->branch_ids = $scope ? array_values(array_map('strval', $scope->resolvedBranchIds())) : [];
        $this->department_ids = $scope ? array_values(array_map('strval', $scope->resolvedDepartmentIds())) : [];
        $this->included_ids = $scope?->included_ids
            ? array_values(array_unique(array_filter(array_map('intval', $scope->included_ids))))
            : array_values(array_unique(array_filter(array_map('intval', $this->group_by === 'department' ? $this->department_ids : $this->branch_ids))));
    }

    public function saveSummarySettings(): void
    {
        $data = $this->form->getState();
        $periodId = $data['period_id'] ?? $this->period_id;
        $period = PayrollPeriod::query()->findOrFail($periodId);
        abort_if($period->is_locked, 403, 'Locked payroll summary settings cannot be changed.');

        $groupBy = $this->group_by ?: 'branch';
        abort_unless(in_array($groupBy, ['branch', 'department'], true), 422);

        $rawBranchIds = $data['branch_ids'] ?? $this->branch_ids;
        $rawDepartmentIds = $data['department_ids'] ?? $this->department_ids;

        $branchIds = array_values(array_unique(array_filter(array_map('intval', is_array($rawBranchIds) ? $rawBranchIds : []))));
        $departmentIds = array_values(array_unique(array_filter(array_map('intval', is_array($rawDepartmentIds) ? $rawDepartmentIds : []))));

        // Support legacy callers that set group_by + included_ids directly without setting branch_ids/department_ids
        if ($branchIds === [] && $departmentIds === [] && $this->included_ids !== []) {
            $legacyIds = array_values(array_unique(array_filter(array_map('intval', $this->included_ids))));

            if ($groupBy === 'department') {
                $departmentIds = $legacyIds;
            } else {
                $branchIds = $legacyIds;
            }
        }

        if ($branchIds !== []) {
            $allowedBranches = Branch::query()->whereIn('id', $branchIds)->count();
            abort_unless($allowedBranches === count($branchIds), 422);
        }

        if ($departmentIds !== []) {
            $allowedDepartments = Department::query()->whereIn('id', $departmentIds)->count();
            abort_unless($allowedDepartments === count($departmentIds), 422);
        }

        if ($departmentIds !== [] && $branchIds === []) {
            $groupBy = 'department';
            $includedIds = $departmentIds;
        } elseif ($branchIds !== [] && $departmentIds === []) {
            $groupBy = 'branch';
            $includedIds = $branchIds;
        } else {
            $includedIds = $groupBy === 'department' ? $departmentIds : $branchIds;
        }

        PayrollSummaryScope::query()->updateOrCreate(
            ['payroll_period_id' => $period->id],
            [
                'group_by' => $groupBy,
                'included_ids' => $includedIds,
                'branch_ids' => $branchIds,
                'department_ids' => $departmentIds,
            ],
        );

        $this->group_by = $groupBy;
        $this->included_ids = $includedIds;
        $this->branch_ids = array_values(array_map('strval', $branchIds));
        $this->department_ids = array_values(array_map('strval', $departmentIds));
        $this->cachedSummaryRows = null;

        Notification::make()->title('Payroll summary settings saved')->success()->send();
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

    /**
     * @return array{0: array<int>, 1: array<int>}
     */
    protected function resolvedSelectionArrays(): array
    {
        $branchIds = array_values(array_unique(array_filter(array_map('intval', $this->branch_ids))));
        $departmentIds = array_values(array_unique(array_filter(array_map('intval', $this->department_ids))));

        if ($branchIds === [] && $departmentIds === [] && $this->included_ids !== []) {
            $legacyIds = array_values(array_unique(array_filter(array_map('intval', $this->included_ids))));

            if ($this->group_by === 'department') {
                $departmentIds = $legacyIds;
            } else {
                $branchIds = $legacyIds;
            }
        }

        return [$branchIds, $departmentIds];
    }

    public function getSummaryRowsProperty(): Collection
    {
        if (! $this->selectedPeriod) {
            return collect();
        }

        [$branchIds, $departmentIds] = $this->resolvedSelectionArrays();

        $key = implode('|', [
            $this->selectedPeriod->id,
            $this->group_by,
            implode(',', $this->included_ids),
            implode(',', $branchIds),
            implode(',', $departmentIds),
        ]);

        if ($this->cachedSummaryRows !== null && $this->cachedSummaryRowsKey === $key) {
            return $this->cachedSummaryRows;
        }

        $this->cachedSummaryRowsKey = $key;

        return $this->cachedSummaryRows = app(PayrollCalculator::class)->summaryRows(
            $this->selectedPeriod,
            $this->group_by,
            $this->included_ids,
            $branchIds,
            $departmentIds,
        );
    }

    public function returnUrl(): string
    {
        return Payroll::getUrl();
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
                    ->url(fn (): string => route('hr_tools.payroll_summary.print', [
                        'period_id' => $this->period_id,
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
        [$branchIds, $departmentIds] = $this->resolvedSelectionArrays();
        $headers = app(PayrollCalculator::class)->summaryHeaders($this->group_by, $branchIds, $departmentIds);
        $rows = $this->summaryRows;
        $period = $this->selectedPeriod?->title ?? 'payroll';
        $filename = 'payroll-summary-'.str($period)->slug().'.xls';

        return response()->streamDownload(function () use ($headers, $rows): void {
            echo CompanyExportHeader::excelHtml(count($headers));
            echo CompanyExportHeader::exportTitleHtml('Salaries & Wages - Summary', count($headers));
            echo '<table '.CompanyExportHeader::tableAttributes().'><thead><tr>';

            foreach ($headers as $label) {
                echo '<th '.CompanyExportHeader::thStyle().'>'.e($label).'</th>';
            }

            echo '</tr></thead><tbody>';

            $departmentRows = $rows->where('group_type', 'department')->values();
            $branchRows = $rows->where('group_type', 'branch')->values();
            $hasBothTypes = $departmentRows->isNotEmpty() && $branchRows->isNotEmpty();

            $renderRow = function (array $row) use ($headers): void {
                echo '<tr>';
                foreach (array_keys($headers) as $key) {
                    echo '<td '.CompanyExportHeader::tdStyle().'>'.e((string) ($row[$key] ?? '')).'</td>';
                }
                echo '</tr>';
            };

            if ($hasBothTypes) {
                foreach ($departmentRows as $row) {
                    $renderRow($row);
                }
                $renderRow([
                    'number' => '',
                    'branch' => 'DEPARTMENTS SUBTOTAL',
                    'workers' => $departmentRows->sum('workers'),
                    'gross_pay' => round($departmentRows->sum('gross_pay'), 2),
                    'total_deductions' => round($departmentRows->sum('total_deductions'), 2),
                    'net_pay' => round($departmentRows->sum('net_pay'), 2),
                    'previous_net_pay' => round($departmentRows->sum('previous_net_pay'), 2),
                    'variance' => round($departmentRows->sum('variance'), 2),
                ]);

                foreach ($branchRows as $row) {
                    $renderRow($row);
                }
                $renderRow([
                    'number' => '',
                    'branch' => 'BRANCHES SUBTOTAL',
                    'workers' => $branchRows->sum('workers'),
                    'gross_pay' => round($branchRows->sum('gross_pay'), 2),
                    'total_deductions' => round($branchRows->sum('total_deductions'), 2),
                    'net_pay' => round($branchRows->sum('net_pay'), 2),
                    'previous_net_pay' => round($branchRows->sum('previous_net_pay'), 2),
                    'variance' => round($branchRows->sum('variance'), 2),
                ]);
            } else {
                foreach ($rows as $row) {
                    $renderRow($row);
                }
            }

            if ($rows->isNotEmpty()) {
                $renderRow([
                    'number' => '',
                    'branch' => 'GRAND TOTAL',
                    'workers' => $rows->sum('workers'),
                    'gross_pay' => round($rows->sum('gross_pay'), 2),
                    'total_deductions' => round($rows->sum('total_deductions'), 2),
                    'net_pay' => round($rows->sum('net_pay'), 2),
                    'previous_net_pay' => round($rows->sum('previous_net_pay'), 2),
                    'variance' => round($rows->sum('variance'), 2),
                ]);
            }

            echo '</tbody></table>';
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
