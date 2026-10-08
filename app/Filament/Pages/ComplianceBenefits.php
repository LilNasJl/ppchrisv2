<?php

namespace App\Filament\Pages;

use App\Models\Branch as BranchModel;
use App\Models\Deduction;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Override;
use UnitEnum;

class ComplianceBenefits extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.pages.compliance-benefits';

    protected static ?string $title = 'Benefits & Company Deduction Management';

    protected static ?string $navigationLabel = 'Benefits & Company Deduction Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::MinusCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Compensation and Benefits Management';

    protected static ?int $navigationSort = 4;

    #[Url(as: 'branchId')]
    public mixed $branchId = null;

    #[Url(as: 'departmentId')]
    public mixed $departmentId = null;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    public ?BranchModel $branch = null;

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
        $requestedBranchId = BranchModel::resolvePublicId($this->branchId ?: request()->query('branchId'));
        $requestedDepartmentId = Department::resolvePublicId($this->departmentId ?: request()->query('departmentId'));

        $this->branchId = $requestedBranchId ? (int) $requestedBranchId : null;
        $this->departmentId = $requestedDepartmentId ? (int) $requestedDepartmentId : null;
        $this->search = trim((string) ($this->search ?: request()->query('search', '')));
        $this->branch = $this->selectedBranch();
    }

    public function updatedBranchId(): void
    {
        $resolved = BranchModel::resolvePublicId($this->branchId);
        $this->branchId = $resolved ? (int) $resolved : null;
        $this->branch = $this->selectedBranch();
    }

    public function updatedDepartmentId(): void
    {
        $resolved = Department::resolvePublicId($this->departmentId);
        $this->departmentId = $resolved ? (int) $resolved : null;
    }

    public function resetFilters(): void
    {
        $this->branchId = null;
        $this->departmentId = null;
        $this->search = '';
        $this->branch = null;
    }

    /**
     * @return Collection<int, BranchModel>
     */
    public function branches(): Collection
    {
        return BranchModel::query()
            ->orderBy('branch_name')
            ->get();
    }

    /**
     * @return Collection<int, Department>
     */
    public function departments(): Collection
    {
        return Department::query()
            ->orderBy('name')
            ->get();
    }

    public function selectedBranch(): ?BranchModel
    {
        $resolved = BranchModel::resolvePublicId($this->branchId);

        return filled($resolved)
            ? BranchModel::query()->find((int) $resolved)
            : null;
    }

    public function selectedDepartment(): ?Department
    {
        $resolved = Department::resolvePublicId($this->departmentId);

        return filled($resolved)
            ? Department::query()->find((int) $resolved)
            : null;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function filteredEmployeeRows(): Collection
    {
        $resolvedBranchId = BranchModel::resolvePublicId($this->branchId);
        $resolvedDepartmentId = Department::resolvePublicId($this->departmentId);

        $query = Employee::query()
            ->with(['user', 'designation', 'department', 'branch', 'employeeDeductions.deduction'])
            ->activeEmployment()
            ->whereHas('user', fn (Builder $q): Builder => $q->where('role', 'employee'))
            ->when(filled($resolvedBranchId), fn (Builder $q): Builder => $q->where('branch_id', (int) $resolvedBranchId))
            ->when(filled($resolvedDepartmentId), fn (Builder $q): Builder => $q->where('department_id', (int) $resolvedDepartmentId))
            ->when(trim($this->search) !== '', function (Builder $q): void {
                $raw = trim($this->search);
                $term = '%'.$raw.'%';
                $digits = preg_replace('/[^0-9]/', '', $raw);

                $q->where(function (Builder $sub) use ($term, $digits): void {
                    $sub->where('lastname', 'like', $term)
                        ->orWhere('firstname', 'like', $term)
                        ->orWhere('middlename', 'like', $term)
                        ->orWhere('uid', 'like', $term)
                        ->orWhere('fingerprint_id', 'like', $term)
                        ->when(filled($digits), fn (Builder $dq): Builder => $dq
                            ->orWhere('uid', 'like', "%{$digits}%")
                            ->orWhere('uid', '=', (int) $digits))
                        ->orWhereHas('designation', fn (Builder $dq): Builder => $dq->where('title', 'like', $term))
                        ->orWhereHas('department', fn (Builder $deptQ): Builder => $deptQ->where('name', 'like', $term))
                        ->orWhereHas('branch', fn (Builder $bq): Builder => $bq->where('branch_name', 'like', $term));
                });
            })
            ->orderBy('lastname')
            ->orderBy('firstname');

        $employees = $query->limit(200)->get();
        $returnUrl = $this->currentPageReturnUrl();

        return $employees->map(function (Employee $employee) use ($returnUrl): array {
            $activeDeductions = $employee->employeeDeductions
                ->filter(fn (EmployeeDeduction $ed): bool => (bool) $ed->active
                    && $ed->deduction !== null
                    && $ed->isActiveForPayroll()
                    && (float) $ed->amount > 0);

            $remittanceDeductions = $activeDeductions->filter(
                fn (EmployeeDeduction $ed): bool => $ed->deduction?->category === Deduction::CATEGORY_REMITTANCE
            );

            $companyDeductions = $activeDeductions->filter(
                fn (EmployeeDeduction $ed): bool => in_array(
                    $ed->deduction?->category,
                    [Deduction::CATEGORY_COMPANY, Deduction::CATEGORY_OTHER],
                    true
                )
            );

            $remittanceAmount = (float) $remittanceDeductions->sum('amount');
            $companyAmount = (float) $companyDeductions->sum('amount');
            $totalAmount = $remittanceAmount + $companyAmount;

            $manageUrl = EmployeeComplianceBenefits::getUrl([
                'employeeId' => $employee->publicKey(),
                'returnUrl' => $returnUrl,
            ]);

            return [
                'employee' => $employee,
                'remittance_count' => $remittanceDeductions->count(),
                'remittance_amount' => $remittanceAmount,
                'company_count' => $companyDeductions->count(),
                'company_amount' => $companyAmount,
                'active_count' => $activeDeductions->count(),
                'total_amount' => $totalAmount,
                'manage_url' => $manageUrl,
            ];
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{total_employees:int, employees_with_deductions:int, total_remittance_amount:float, total_company_amount:float, total_deduction_amount:float, total_active_items:int}
     */
    public function deductionSummary(Collection $rows): array
    {
        return [
            'total_employees' => $rows->count(),
            'employees_with_deductions' => $rows->filter(fn (array $row): bool => $row['active_count'] > 0)->count(),
            'total_remittance_amount' => (float) $rows->sum('remittance_amount'),
            'total_company_amount' => (float) $rows->sum('company_amount'),
            'total_deduction_amount' => (float) $rows->sum('total_amount'),
            'total_active_items' => (int) $rows->sum('active_count'),
        ];
    }

    public function currentPageReturnUrl(): string
    {
        $query = array_filter([
            'branchId' => filled($this->branchId) ? $this->branchId : null,
            'departmentId' => filled($this->departmentId) ? $this->departmentId : null,
            'search' => trim($this->search) !== '' ? trim($this->search) : null,
        ], fn ($value): bool => filled($value));

        $baseUrl = static::getUrl();

        return $baseUrl.(empty($query) ? '' : '?'.Arr::query($query));
    }

    public function manageDeductionsUrl(): string
    {
        return ManageDeductions::getUrl();
    }

    public function getTitle(): string
    {
        return $this->branch
            ? 'Benefits & Company Deduction Management - '.$this->branch->branch_name
            : 'Benefits & Company Deduction Management';
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [];
    }
}
