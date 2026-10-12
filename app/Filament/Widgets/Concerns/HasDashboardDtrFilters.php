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
        $this->getFiltersSchema()->fill();

        $queryKey = $this->getFilterQueryKey();
        $queryFilters = request()->query($queryKey);
        if (is_array($queryFilters) && $queryFilters !== []) {
            $this->filters = array_replace($this->filters ?? [], $queryFilters);
        }

        if ($this->hasDeferredFilters()) {
            $this->filters = $this->deferredFilters;
        }

        // ChartWidget mounts before this trait and may cache unfiltered data.
        $this->cachedData = null;
        $this->dataChecksum = $this->generateDataChecksum();
    }

    protected function getFilterQueryKey(): string
    {
        return property_exists($this, 'filterQueryKey') ? $this->filterQueryKey : 'filters';
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
                ->default(fn (): string => $this->defaultAttendanceDate())
                ->native(false);
        }

        return $schema->components($components);
    }

    protected function defaultAttendanceDate(): string
    {
        return now()->toDateString();
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
            ->when(filled($filters['branch_id'] ?? null), fn (Builder $query): Builder => $query->where('branch_id', $filters['branch_id']));

        if ($applySelectedDate) {
            $date = Carbon::make($filters['date'] ?? null)?->toDateString() ?? now()->toDateString();
            $periodId = $filters['payroll_period_id'] ?? null;

            if ($periodId) {
                $period = PayrollPeriod::find($periodId);
                if ($period) {
                    $periodStart = Carbon::parse($period->date_start)->toDateString();
                    $periodEnd = Carbon::parse($period->date_end)->toDateString();

                    if ($date >= $periodStart && $date <= $periodEnd) {
                        $query->where('payroll_period_id', $periodId)
                            ->whereDate('date_in', $date);
                    } else {
                        $latestDateInPeriod = Dtr::query()
                            ->where('payroll_period_id', $periodId)
                            ->whereNotNull('date_in')
                            ->max('date_in');

                        if ($latestDateInPeriod) {
                            $query->where('payroll_period_id', $periodId)
                                ->whereDate('date_in', Carbon::parse($latestDateInPeriod)->toDateString());
                        } else {
                            $query->whereDate('date_in', $date);
                        }
                    }
                } else {
                    $query->whereDate('date_in', $date);
                }
            } else {
                $query->whereDate('date_in', $date);
            }
        } elseif (filled($filters['payroll_period_id'] ?? null)) {
            $query->where('payroll_period_id', $filters['payroll_period_id']);
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
