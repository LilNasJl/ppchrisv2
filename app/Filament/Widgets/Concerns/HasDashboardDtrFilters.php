<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\PayrollPeriod;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Database\Eloquent\Builder;

trait HasDashboardDtrFilters
{
    use HasFiltersSchema;

    public function mountHasFiltersSchema(): void
    {
        $filters = $this->filters ?? [];

        $this->getFiltersSchema()->fill();

        if ($filters !== []) {
            $this->filters = array_replace($this->filters ?? [], $filters);
        }

        if ($this->hasDeferredFilters()) {
            $this->filters = $this->deferredFilters;
        }

        // ChartWidget mounts before this trait and may cache unfiltered data.
        $this->cachedData = null;
        $this->dataChecksum = $this->generateDataChecksum();
    }

    public function filtersSchema(Schema $schema): Schema
    {
        $components = [
            Select::make('branch_id')
                ->label('Branch')
                ->placeholder('All branches')
                ->options(fn (): array => Branch::query()->orderBy('branch_name')->pluck('branch_name', 'id')->all())
                ->searchable()
                ->native(false),
            Select::make('payroll_period_id')
                ->label('Payroll Period')
                ->placeholder('All payroll periods')
                ->options(fn (): array => PayrollPeriod::query()
                    ->orderByDesc('date_start')
                    ->pluck('title', 'id')
                    ->all())
                ->default(fn (): ?int => $this->currentPayrollPeriodId())
                ->searchable()
                ->native(false),
        ];

        if ($this->hasAttendanceDateFilter()) {
            $components[] = DatePicker::make('date')
                ->label('Date')
                ->default(fn (): string => now()->toDateString())
                ->native(false)
                ->required();
        }

        return $schema->components($components);
    }

    protected function selectedBranchLabel(): string
    {
        $branchId = $this->filters['branch_id'] ?? null;

        if (blank($branchId)) {
            return 'All branches';
        }

        return Branch::withTrashed()->whereKey($branchId)->value('branch_name') ?? 'Selected branch';
    }

    protected function hasAttendanceDateFilter(): bool
    {
        return false;
    }

    protected function filteredDtrRecords(bool $applySelectedDate = false): Builder
    {
        $filters = $this->filters ?? [];

        $query = Dtr::query()
            ->finalizedAttendance()
            ->whereRaw("LOWER(TRIM(COALESCE(schedule_type, ''))) NOT LIKE ?", ['%for approval%'])
            ->when(filled($filters['branch_id'] ?? null), fn (Builder $query): Builder => $query->where('branch_id', $filters['branch_id']))
            ->when(filled($filters['payroll_period_id'] ?? null), fn (Builder $query): Builder => $query->where('payroll_period_id', $filters['payroll_period_id']));

        if ($applySelectedDate) {
            $date = Carbon::make($filters['date'] ?? null)?->toDateString() ?? now()->toDateString();
            $query->whereDate('date_in', $date);
        }

        return $query;
    }

    private function currentPayrollPeriodId(): ?int
    {
        return PayrollPeriod::query()
            ->whereDate('date_start', '<=', today())
            ->whereDate('date_end', '>=', today())
            ->orderByDesc('date_start')
            ->value('id');
    }
}
