<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PayrollPeriods\PayrollPeriodResource;
use App\Models\Branch;
use App\Models\Dtr as DtrModel;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollPeriodBranchExclusion;
use App\Models\PayrollPeriodEmployeeExclusion;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Override;
use UnitEnum;

class Payroll extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.pages.payroll';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Compensation & Benefits Mngt';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Payroll Processing';

    public ?int $periodId = null;

    public ?int $branchId = null;

    public string $search = '';

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
        $requestedPeriodId = PayrollPeriod::resolvePublicId(request()->query('periodId'));
        $requestedBranchId = Branch::resolvePublicId(request()->query('branchId'));

        $defaultPeriodId = PayrollPeriod::query()->newestFirst()->value('id');
        $this->periodId = $requestedPeriodId ? (int) $requestedPeriodId : ($defaultPeriodId ? (int) $defaultPeriodId : null);
        $this->branchId = $requestedBranchId ? (int) $requestedBranchId : null;
        $this->search = trim((string) request()->query('search', ''));
    }

    public function resetFilters(): void
    {
        $newestId = PayrollPeriod::query()->newestFirst()->value('id');
        $this->periodId = $newestId ? (int) $newestId : null;
        $this->branchId = null;
        $this->search = '';
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
     * @return Collection<int, Branch>
     */
    public function branches(): Collection
    {
        return Branch::query()
            ->orderBy('branch_name')
            ->get();
    }

    public function selectedPeriod(): ?PayrollPeriod
    {
        return filled($this->periodId)
            ? PayrollPeriod::query()->find((int) $this->periodId)
            : null;
    }

    public function selectedBranch(): ?Branch
    {
        return filled($this->branchId)
            ? Branch::query()->find((int) $this->branchId)
            : null;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function filteredEmployeeRows(): Collection
    {
        $period = $this->selectedPeriod();

        $query = Employee::query()
            ->with(['user', 'designation', 'department', 'branch'])
            ->activeEmployment()
            ->whereHas('user', fn (Builder $q): Builder => $q->where('role', 'employee'))
            ->when(filled($this->branchId), fn (Builder $q): Builder => $q->where('branch_id', (int) $this->branchId))
            ->when($period && Schema::hasTable('payroll_period_branch_exclusions'), function (Builder $q) use ($period): void {
                $q->whereNotIn('employees.branch_id', PayrollPeriodBranchExclusion::query()
                    ->select('branch_id')
                    ->where('payroll_period_id', $period->id));
            })
            ->when($period && Schema::hasTable('payroll_period_employee_exclusions'), function (Builder $q) use ($period): void {
                $q->whereNotIn('employees.id', PayrollPeriodEmployeeExclusion::query()
                    ->select('employee_id')
                    ->where('payroll_period_id', $period->id));
            })
            ->when(trim($this->search) !== '', function (Builder $q): void {
                $term = '%'.trim($this->search).'%';
                $q->where(function (Builder $sub) use ($term): void {
                    $sub->where('lastname', 'like', $term)
                        ->orWhere('firstname', 'like', $term)
                        ->orWhere('middlename', 'like', $term)
                        ->orWhere('uid', 'like', $term)
                        ->orWhere('fingerprint_id', 'like', $term)
                        ->orWhereHas('designation', fn (Builder $dq): Builder => $dq->where('title', 'like', $term));
                });
            })
            ->orderBy('lastname')
            ->orderBy('firstname');

        $employees = $query->limit(150)->get();

        $dtrCountsByFingerprint = collect();
        if ($period && $employees->isNotEmpty()) {
            $fingerprints = $employees
                ->map(fn (Employee $e): string => (string) ($e->fingerprint_id ?: $e->uid ?: ''))
                ->filter()
                ->unique()
                ->values();

            if ($fingerprints->isNotEmpty()) {
                $dtrCountsByFingerprint = DtrModel::query()
                    ->where('payroll_period_id', $period->id)
                    ->whereIn('fingerprint_id', $fingerprints)
                    ->get(['id', 'fingerprint_id'])
                    ->groupBy(fn (DtrModel $dtr): string => (string) $dtr->fingerprint_id)
                    ->map(fn (Collection $group): int => $group->count());
            }
        }

        return $employees->map(function (Employee $employee) use ($period, $dtrCountsByFingerprint): array {
            $fp = (string) ($employee->fingerprint_id ?: $employee->uid ?: '');
            $dtrCount = $fp !== '' ? (int) ($dtrCountsByFingerprint->get($fp) ?? 0) : 0;

            $viewPayrollUrl = ($period && $employee->branch)
                ? EmployeePayroll::getUrl([
                    'employeeId' => $employee->publicKey(),
                    'branchId' => $employee->branch->publicKey(),
                    'periodId' => $period->publicKey(),
                ])
                : '#';

            $payslipUrl = $period
                ? route('payroll.payslip.print', [
                    'period' => $period->publicKey(),
                    'employee' => $employee->publicKey(),
                ])
                : null;

            return [
                'employee' => $employee,
                'dtr_count' => $dtrCount,
                'view_payroll_url' => $viewPayrollUrl,
                'payslip_url' => $payslipUrl,
            ];
        });
    }

    public function payrollSummaryUrl(): ?string
    {
        $period = $this->selectedPeriod();

        return $period
            ? PayrollSummary::getUrl(['periodId' => $period->publicKey()])
            : null;
    }

    public function payrollByBranchUrl(): ?string
    {
        $period = $this->selectedPeriod();

        return $period
            ? PayrollByBranch::getUrl(array_filter([
                'periodId' => $period->publicKey(),
                'branchId' => $this->selectedBranch()?->publicKey(),
            ]))
            : null;
    }

    public function payrollRosterUrl(): ?string
    {
        $period = $this->selectedPeriod();

        return $period
            ? PayrollRoster::getUrl(['periodId' => $period->publicKey()])
            : null;
    }

    public function payrollCalculationUrl(): ?string
    {
        $period = $this->selectedPeriod();

        return $period
            ? PayrollCalculation::getUrl(['periodId' => $period->publicKey()])
            : null;
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
