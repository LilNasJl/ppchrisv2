<?php

namespace App\Filament\Pages;

use App\Models\Activity;
use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Memo;
use App\Models\PayrollPeriod;
use App\Services\HolidayResolver;
use App\Services\PayrollCalculator;
use App\Support\CompanyExportHeader;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Override;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class Reports extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.pages.reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Analytic and Reporting';

    protected static ?int $navigationSort = 3;

    public ?string $report_type = 'employee_headcount_branch';

    public ?string $report_category = 'employee';

    public ?string $payroll_period_id = null;

    public ?string $branch_id = 'all';

    public ?string $month = null;

    public function mount(): void
    {
        $this->report_category = $this->getCategoryForReportType($this->report_type);
        $this->payroll_period_id = (string) app(PayrollCalculator::class)->defaultPeriod()?->id;
        $this->month = now()->format('Y-m');

        $this->form->fill([
            'report_type' => $this->report_type,
            'payroll_period_id' => $this->payroll_period_id,
            'branch_id' => $this->branch_id,
            'month' => $this->month,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Group::make([
                Select::make('report_type')
                    ->label('Report Type')
                    ->options(fn (): array => $this->reportOptionsForCategory())
                    ->searchable()
                    ->reactive()
                    ->required()
                    ->columnSpanFull(),

                Select::make('payroll_period_id')
                    ->label('Payroll Period')
                    ->options(fn (): array => PayrollPeriod::query()->newestFirst()->pluck('title', 'id')->all())
                    ->searchable()
                    ->reactive()
                    ->visible(fn (Get $get): bool => $this->reportNeedsPeriod((string) $get('report_type'))),

                Select::make('branch_id')
                    ->label('Branch')
                    ->options(fn (): array => [
                        'all' => 'All Branches',
                        ...Branch::query()
                            ->orderBy('branch_name')
                            ->pluck('branch_name', 'id')
                            ->all(),
                    ])
                    ->default('all')
                    ->searchable()
                    ->reactive()
                    ->visible(fn (Get $get): bool => $this->reportNeedsBranch((string) $get('report_type'))),

                TextInput::make('month')
                    ->label('Calendar Month')
                    ->type('month')
                    ->reactive()
                    ->visible(fn (Get $get): bool => $this->reportNeedsMonth((string) $get('report_type'))),
            ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('exportExcel')
                    ->label('Export Excel')
                    ->icon(Heroicon::TableCells)
                    ->action(fn (): StreamedResponse => $this->exportExcel()),

                Action::make('print')
                    ->label('Print / PDF')
                    ->icon(Heroicon::Printer)
                    ->url(fn (): string => route('hr_tools.reports.print', [
                        'report_type' => $this->report_type,
                        'payroll_period_id' => $this->payroll_period_id,
                        'branch_id' => $this->branch_id,
                        'month' => $this->month,
                    ]))
                    ->openUrlInNewTab(),
            ])
                ->label('Export')
                ->icon(Heroicon::ChevronDown)
                ->button(),
        ];
    }

    public function getReportTitleProperty(): string
    {
        return $this->reportOptions()[$this->report_type] ?? 'Report';
    }

    public function getReportHeadersProperty(): array
    {
        return match ($this->report_type) {
            'employee_all' => ['Employee ID', 'Fullname', 'Designation', 'Department', 'Branch', 'Employment Status'],
            'employee_age_oldest' => ['#', 'Employee ID', 'Fullname', 'Birthdate', 'Age', 'Designation', 'Department', 'Branch'],

            'employee_headcount_branch',
            'employee_headcount_designation',
            'employee_headcount_department',
            'employee_demographics',
            'employee_status' => ['Group', 'Count'],
            'employee_tenure' => ['#', 'Employee', 'Branch', 'Hired Date', 'Tenure'],

            'payroll_total_period' => ['Payroll Period', 'Workers', 'Gross Pay', 'Total Deductions', 'Net Pay'],
            'payroll_branch_period' => ['#', 'Branch', 'Workers', 'Gross Pay', 'Total Deductions', 'Net Pay'],

            'dtr_late',
            'dtr_credited_overtime',
            'dtr_early_clock_in',
            'dtr_undertime' => ['Rank', 'Employee', 'Branch', 'Total Minutes'],

            'calendar_holidays_month' => ['Date', 'Holiday', 'Type', 'Rate (%)'],
            'activities_month' => ['From', 'To', 'Activity', 'Description'],
            'memo_month' => ['Rank', 'Employee', 'Memo Count'],
            default => [],
        };
    }

    public function getReportRowsProperty(): Collection
    {
        return match ($this->report_type) {
            'employee_all' => $this->allEmployeeRows(),
            'employee_age_oldest' => $this->employeeAgeRows(),
            'employee_headcount_branch' => $this->headcountBy('branch.branch_name', 'No Branch'),
            'employee_headcount_designation' => $this->headcountBy('designation.title', 'No Designation'),
            'employee_headcount_department' => $this->headcountBy('department.name', 'No Department'),
            'employee_demographics' => $this->demographicRows(),
            'employee_status' => $this->employmentStatusRows(),
            'employee_tenure' => $this->employeeTenureRows(),
            'payroll_total_period' => $this->payrollTotalRows(),
            'payroll_branch_period' => $this->payrollBranchRows(),
            'dtr_late' => $this->dtrMetricRows('late'),
            'dtr_credited_overtime' => $this->dtrMetricRows('credited_overtime'),
            'dtr_early_clock_in' => $this->dtrMetricRows('early_clock_in'),
            'dtr_undertime' => $this->dtrMetricRows('undertime'),
            'calendar_holidays_month' => $this->holidayMonthRows(),
            'activities_month' => $this->activityMonthRows(),
            'memo_month' => $this->memoMonthRows(),
            default => collect(),
        };
    }

    public function supportsReportType(string $reportType): bool
    {
        return array_key_exists($reportType, $this->reportOptions());
    }

    public function getReportFilterLabelsProperty(): array
    {
        $labels = [];

        if ($this->reportNeedsPeriod($this->report_type)) {
            $labels['Payroll Period'] = $this->selectedPeriod()?->title ?? 'Not selected';
        }

        if ($this->reportNeedsBranch($this->report_type)) {
            $labels['Branch'] = $this->branch_id === 'all' || blank($this->branch_id)
                ? 'All Branches'
                : (Branch::query()->find($this->branch_id)?->branch_name ?? 'Unknown Branch');
        }

        if ($this->reportNeedsMonth($this->report_type)) {
            $labels['Month'] = filled($this->month)
                ? Carbon::parse($this->month.'-01')->format('F Y')
                : now()->format('F Y');
        }

        return $labels;
    }

    public function selectReportCategory(string $category): void
    {
        if (! array_key_exists($category, $this->reportCategories())) {
            return;
        }

        $this->report_category = $category;
        $this->report_type = (string) array_key_first($this->reportOptionsForCategory($category));

        $this->form->fill([
            'report_type' => $this->report_type,
            'payroll_period_id' => $this->payroll_period_id,
            'branch_id' => $this->branch_id,
            'month' => $this->month,
        ]);
    }

    public function getReportCardsProperty(): array
    {
        return $this->reportCategories();
    }

    public function reportOptionsForCategory(?string $category = null): array
    {
        $category ??= $this->report_category ?: 'employee';

        return $this->reportCategories()[$category]['options'] ?? $this->reportCategories()['employee']['options'];
    }

    public function exportExcel(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            echo CompanyExportHeader::excelHtml(max(count($this->reportHeaders), 1));
            echo CompanyExportHeader::exportTitleHtml($this->reportTitle, max(count($this->reportHeaders), 1));
            echo '<table '.CompanyExportHeader::tableAttributes().'><thead><tr>';

            foreach ($this->reportHeaders as $header) {
                echo '<th '.CompanyExportHeader::thStyle().'>'.e($header).'</th>';
            }

            echo '</tr></thead><tbody>';

            foreach ($this->reportRows as $row) {
                echo '<tr>';

                foreach ($row as $value) {
                    echo '<td '.CompanyExportHeader::tdStyle().'>'.e((string) $value).'</td>';
                }

                echo '</tr>';
            }

            echo '</tbody></table>';
            echo CompanyExportHeader::generatedAtHtml();
        }, $this->fileName('xls'), ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    protected function activeEmployeeQuery(): Builder
    {
        return Employee::query()
            ->with(['branch', 'designation', 'department', 'user'])
            ->whereHas('user', fn (Builder $query) => $query->where('role', 'employee'))
            ->activeEmployment();
    }

    protected function headcountBy(string $path, string $fallback): Collection
    {
        return $this->activeEmployeeQuery()
            ->get()
            ->groupBy(fn (Employee $employee): string => data_get($employee, $path) ?: $fallback)
            ->map(fn (Collection $employees, string $group): array => [$group, $employees->count()])
            ->values();
    }

    protected function allEmployeeRows(): Collection
    {
        return $this->activeEmployeeQuery()
            ->orderBy('uid')
            ->orderBy('lastname')
            ->get()
            ->map(fn (Employee $employee): array => [
                $employee->company_id ?: 'No Employee ID',
                $employee->full_name,
                $employee->designation?->title ?: 'No Designation',
                $employee->department?->name ?: 'No Department',
                $employee->branch?->branch_name ?: 'No Branch',
                $employee->employment_type ?: 'Active',
            ]);
    }

    protected function employeeAgeRows(): Collection
    {
        return $this->activeEmployeeQuery()
            ->orderByRaw('birthdate IS NULL')
            ->orderBy('birthdate')
            ->orderBy('lastname')
            ->get()
            ->values()
            ->map(fn (Employee $employee, int $index): array => [
                $index + 1,
                $employee->company_id ?: 'No Employee ID',
                $employee->full_name,
                $employee->birthdate ? Carbon::parse($employee->birthdate)->format('Y-m-d') : 'Not Set',
                $employee->birthdate ? Carbon::parse($employee->birthdate)->age : 'Not Set',
                $employee->designation?->title ?: 'No Designation',
                $employee->department?->name ?: 'No Department',
                $employee->branch?->branch_name ?: 'No Branch',
            ]);
    }

    protected function demographicRows(): Collection
    {
        $employees = $this->activeEmployeeQuery()->get();
        $rows = collect();

        $employees->groupBy(fn (Employee $employee): string => 'Gender: '.($employee->gender ?: 'Not Set'))
            ->each(fn (Collection $group, string $label) => $rows->push([$label, $group->count()]));

        $employees->groupBy(fn (Employee $employee): string => 'Age: '.$this->ageBracket($employee))
            ->each(fn (Collection $group, string $label) => $rows->push([$label, $group->count()]));

        $employees->groupBy(fn (Employee $employee): string => 'Tenure: '.$this->tenureBracket($employee))
            ->each(fn (Collection $group, string $label) => $rows->push([$label, $group->count()]));

        $rows->push(['Ethnicity: Not recorded', $employees->count()]);

        return $rows->values();
    }

    protected function employmentStatusRows(): Collection
    {
        $employees = $this->activeEmployeeQuery()->get();

        return collect([
            ['Active Employee', $employees->count()],
        ]);
    }

    protected function employeeTenureRows(): Collection
    {
        return $this->activeEmployeeQuery()
            ->orderByRaw('hired_date IS NULL')
            ->orderBy('hired_date')
            ->orderBy('lastname')
            ->get()
            ->values()
            ->map(fn (Employee $employee, int $index): array => [
                $index + 1,
                $employee->full_name,
                $employee->branch?->branch_name ?: 'No Branch',
                $employee->hired_date ? Carbon::parse($employee->hired_date)->format('Y-m-d') : 'Not Set',
                $employee->tenure,
            ]);
    }

    protected function payrollTotalRows(): Collection
    {
        $period = $this->selectedPeriod();

        if (! $period) {
            return collect();
        }

        $summaryRows = app(PayrollCalculator::class)->branchSummaryRows($period);

        return collect([[
            $period->title,
            $summaryRows->sum('workers'),
            number_format((float) $summaryRows->sum('gross_pay'), 2),
            number_format((float) $summaryRows->sum('total_deductions'), 2),
            number_format((float) $summaryRows->sum('net_pay'), 2),
        ]]);
    }

    protected function payrollBranchRows(): Collection
    {
        $period = $this->selectedPeriod();

        if (! $period) {
            return collect();
        }

        return app(PayrollCalculator::class)
            ->branchSummaryRows($period)
            ->map(fn (array $row): array => [
                $row['number'],
                $row['branch'],
                $row['workers'],
                number_format((float) $row['gross_pay'], 2),
                number_format((float) $row['total_deductions'], 2),
                number_format((float) $row['net_pay'], 2),
            ]);
    }

    protected function dtrMetricRows(string $metric): Collection
    {
        $period = $this->selectedPeriod();

        if (! $period) {
            return collect();
        }

        return Dtr::query()
            ->where('payroll_period_id', $period->id)
            ->when(
                filled($this->branch_id) && $this->branch_id !== 'all',
                fn (Builder $query): Builder => $query->where('branch_id', $this->branch_id),
            )
            ->get()
            ->reject(fn (Dtr $dtr): bool => $dtr->requiresAttendanceApproval())
            ->groupBy(fn (Dtr $dtr): string => "{$dtr->branch_id}-{$dtr->fingerprint_id}")
            ->map(function (Collection $dtrs) use ($metric): array {
                $first = $dtrs->first();
                $employee = Employee::query()
                    ->with('branch')
                    ->activeEmployment()
                    ->whereHas('user', fn (Builder $query) => $query->where('role', 'employee'))
                    ->where('branch_id', $first->branch_id)
                    ->where('fingerprint_id', $first->fingerprint_id)
                    ->first();

                if (! $employee) {
                    return [];
                }

                return [
                    'employee' => $employee->full_name,
                    'branch' => $employee->branch?->branch_name ?: (string) $first->branch_id,
                    'minutes' => (float) $dtrs->sum(fn (Dtr $dtr): float => (float) ($dtr->{$metric} ?? 0)),
                ];
            })
            ->filter()
            ->sortByDesc('minutes')
            ->values()
            ->map(fn (array $row, int $index): array => [
                $index + 1,
                $row['employee'],
                $row['branch'],
                $row['minutes'],
            ]);
    }

    protected function holidayMonthRows(): Collection
    {
        $month = filled($this->month) ? Carbon::parse($this->month.'-01') : now();

        return app(HolidayResolver::class)
            ->nationalHolidaysForMonth($month)
            ->map(fn (Holiday $holiday): array => [
                Carbon::parse($holiday->occurrence_date)->format('Y-m-d'),
                $holiday->title,
                $holiday->type?->type,
                $holiday->type?->rate,
            ]);
    }

    protected function activityMonthRows(): Collection
    {
        $month = filled($this->month) ? Carbon::parse($this->month.'-01') : now();

        return Activity::query()
            ->whereDate('date_from', '<=', $month->copy()->endOfMonth())
            ->whereDate('date_to', '>=', $month->copy()->startOfMonth())
            ->orderBy('date_from')
            ->get()
            ->map(fn (Activity $activity): array => [
                Carbon::parse($activity->date_from)->format('Y-m-d'),
                Carbon::parse($activity->date_to)->format('Y-m-d'),
                $activity->title,
                $activity->description,
            ]);
    }

    protected function memoMonthRows(): Collection
    {
        $month = filled($this->month) ? Carbon::parse($this->month.'-01') : now();

        return Memo::query()
            ->with('employee')
            ->whereHas('employee', fn (Builder $query) => $query
                ->activeEmployment()
                ->whereHas('user', fn (Builder $userQuery) => $userQuery->where('role', 'employee')))
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get()
            ->groupBy('employee_id')
            ->map(function (Collection $memos): array {
                $employee = $memos->first()?->employee;

                return [
                    'employee' => $employee?->full_name ?: 'Unknown Employee',
                    'count' => $memos->count(),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->map(fn (array $row, int $index): array => [
                $index + 1,
                $row['employee'],
                $row['count'],
            ]);
    }

    protected function selectedPeriod(): ?PayrollPeriod
    {
        return filled($this->payroll_period_id)
            ? PayrollPeriod::query()->find($this->payroll_period_id)
            : app(PayrollCalculator::class)->defaultPeriod();
    }

    protected function ageBracket(Employee $employee): string
    {
        if (blank($employee->birthdate)) {
            return 'Not Set';
        }

        $age = Carbon::parse($employee->birthdate)->age;

        return match (true) {
            $age < 25 => 'Below 25',
            $age <= 34 => '25-34',
            $age <= 44 => '35-44',
            $age <= 54 => '45-54',
            default => '55+',
        };
    }

    protected function tenureBracket(Employee $employee): string
    {
        if (blank($employee->hired_date)) {
            return 'Not Set';
        }

        $years = Carbon::parse($employee->hired_date)->diffInYears(now());

        return match (true) {
            $years < 1 => 'Below 1 year',
            $years < 3 => '1-2 years',
            $years < 5 => '3-4 years',
            default => '5+ years',
        };
    }

    protected function reportOptions(): array
    {
        return collect($this->reportCategories())
            ->pluck('options')
            ->collapse()
            ->all();
    }

    protected function reportCategories(): array
    {
        return [
            'employee' => [
                'title' => 'Employee Report',
                'subtitle' => 'Headcount, demographics, and employment status',
                'options' => [
                    'employee_all' => 'All Employees',
                    'employee_age_oldest' => 'Active Employees by Age',
                    'employee_headcount_branch' => 'Headcount by Branch',
                    'employee_headcount_designation' => 'Headcount by Designation',
                    'employee_headcount_department' => 'Headcount by Department',
                    'employee_demographics' => 'Workforce Demographics',
                    'employee_status' => 'Active and End Contract',
                    'employee_tenure' => 'Employee Tenure',
                ],
            ],
            'payroll' => [
                'title' => 'Payroll Report',
                'subtitle' => 'Totals by period and branch',
                'options' => [
                    'payroll_total_period' => 'Total Payroll per Period',
                    'payroll_branch_period' => 'Total Payroll per Branch',
                ],
            ],
            'dtr' => [
                'title' => 'DTR Report',
                'subtitle' => 'Late, overtime, early clock-in, and undertime rankings',
                'options' => [
                    'dtr_late' => 'Late Ranking',
                    'dtr_credited_overtime' => 'Credited Overtime Ranking',
                    'dtr_early_clock_in' => 'Early Clock In Ranking',
                    'dtr_undertime' => 'Undertime Ranking',
                ],
            ],
            'calendar' => [
                'title' => 'Calendar Report',
                'subtitle' => 'Holidays by selected month',
                'options' => [
                    'calendar_holidays_month' => 'Holidays by Month',
                ],
            ],
            'activities' => [
                'title' => 'Activities Report',
                'subtitle' => 'Activities by selected month',
                'options' => [
                    'activities_month' => 'Activities by Month',
                ],
            ],
            'memo' => [
                'title' => 'Memo Report',
                'subtitle' => 'Memo ranking by selected month',
                'options' => [
                    'memo_month' => 'Employee Memo Ranking by Month',
                ],
            ],
        ];
    }

    protected function getCategoryForReportType(?string $reportType): string
    {
        foreach ($this->reportCategories() as $category => $definition) {
            if (array_key_exists((string) $reportType, $definition['options'])) {
                return $category;
            }
        }

        return 'employee';
    }

    protected function reportNeedsPeriod(?string $reportType): bool
    {
        return in_array($reportType, [
            'payroll_total_period',
            'payroll_branch_period',
            'dtr_late',
            'dtr_credited_overtime',
            'dtr_early_clock_in',
            'dtr_undertime',
        ], true);
    }

    protected function reportNeedsMonth(?string $reportType): bool
    {
        return in_array($reportType, [
            'calendar_holidays_month',
            'activities_month',
            'memo_month',
        ], true);
    }

    protected function reportNeedsBranch(?string $reportType): bool
    {
        return in_array($reportType, [
            'dtr_late',
            'dtr_credited_overtime',
            'dtr_early_clock_in',
            'dtr_undertime',
        ], true);
    }

    protected function fileName(string $extension): string
    {
        return str($this->reportTitle)->slug().'-'.now()->format('Ymd-His').'.'.$extension;
    }
}
