<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Override;
use UnitEnum;

class Kpi extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.kpi';

    protected static ?string $title = 'KPI';

    protected static ?string $navigationLabel = 'KPI';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBarSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Performance Management';

    protected static ?int $navigationSort = 1;

    public string $viewMode = 'departments';

    public string $search = '';

    public function mount(): void
    {
        $view = (string) request()->query('view');

        if (in_array($view, array_keys($this->viewOptions()), true)) {
            $this->viewMode = $view;
        }
    }

    public function setViewMode(string $viewMode): void
    {
        if (! array_key_exists($viewMode, $this->viewOptions())) {
            return;
        }

        $this->viewMode = $viewMode;
        $this->search = '';
    }

    public function viewOptions(): array
    {
        return [
            'departments' => 'Per Departments',
            'attendants' => 'Per Attendant / Cashier',
            'employees' => 'Per Employee',
            'branches' => 'Per Branch/Station',
        ];
    }

    public function rows(): Collection
    {
        return match ($this->viewMode) {
            'attendants' => $this->employeeRows(true),
            'employees' => $this->employeeRows(),
            'branches' => $this->branchRows(),
            default => $this->departmentRows(),
        };
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('configuration')
                ->label('KPI Configuration')
                ->icon(Heroicon::AdjustmentsHorizontal)
                ->url(KpiConfiguration::getUrl()),

            Action::make('accounts')
                ->label('Accounts')
                ->icon(Heroicon::Users)
                ->url(KpiAccounts::getUrl()),
        ];
    }

    protected function departmentRows(): Collection
    {
        $search = trim($this->search);

        return Department::query()
            ->when($search !== '', fn (Builder $query): Builder => $query
                ->where(fn (Builder $query): Builder => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('acronym', 'like', "%{$search}%")))
            ->withCount([
                'employees as employees_count' => fn (Builder $query): Builder => $query->activeEmployment(),
            ])
            ->orderBy('name')
            ->get();
    }

    protected function employeeRows(bool $attendantsOnly = false): Collection
    {
        $search = trim($this->search);

        return Employee::query()
            ->activeEmployment()
            ->with(['branch', 'department', 'designation'])
            ->when($attendantsOnly, fn (Builder $query): Builder => $query
                ->whereHas('designation', fn (Builder $query): Builder => $query
                    ->whereRaw('UPPER(title) IN (?, ?, ?)', [
                        'FORECOURT ATTENDANT',
                        'CASHIER/FORECOURT ATTENDANT',
                        'CASHIER / FORECOURT ATTENDANT',
                    ])))
            ->when($search !== '', fn (Builder $query): Builder => $query
                ->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('lastname', 'like', "%{$search}%")
                        ->orWhere('middlename', 'like', "%{$search}%")
                        ->orWhere('firstname', 'like', "%{$search}%")
                        ->orWhere('uid', 'like', "%{$search}%")
                        ->orWhereHas('branch', fn (Builder $query): Builder => $query
                            ->where('branch_name', 'like', "%{$search}%"))
                        ->orWhereHas('department', fn (Builder $query): Builder => $query
                            ->where('name', 'like', "%{$search}%"));
                }))
            ->orderBy('lastname')
            ->orderBy('middlename')
            ->orderBy('firstname')
            ->get();
    }

    protected function branchRows(): Collection
    {
        $search = trim($this->search);

        return Branch::query()
            ->when($search !== '', fn (Builder $query): Builder => $query
                ->where('branch_name', 'like', "%{$search}%"))
            ->select('branches.*')
            ->selectSub(
                Employee::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('employees.branch_id', 'branches.id')
                    ->activeEmployment(),
                'employees_count',
            )
            ->orderBy('branch_name')
            ->get();
    }
}
