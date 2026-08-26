<?php

namespace App\Filament\SicRc\Widgets;

use App\Models\Branch;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
use App\Models\SicRcAccount;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class SicRcBranchAttendanceChart extends ChartWidget
{
    protected ?string $heading = 'Branch Attendance Statistics';

    protected ?string $description = 'Approved overtime, undertime, late, and approved early overtime in minutes.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '360px';

    public static function canView(): bool
    {
        return auth('sicrc')->check();
    }

    public function mount(): void
    {
        $this->filter = (string) (PayrollPeriod::query()
            ->where('is_locked', false)
            ->newestFirst()
            ->value('id') ?: '');

        parent::mount();
    }

    public function updatedFilter(): void
    {
        $this->cachedData = null;
    }

    protected function getData(): array
    {
        $branchIds = $this->assignedBranchIds();
        $periodId = filled($this->filter) ? (int) $this->filter : null;

        $branches = $branchIds === []
            ? collect()
            : Branch::query()
                ->whereIn('id', $branchIds)
                ->orderBy('branch_name')
                ->get(['id', 'branch_name']);

        $totals = $this->attendanceTotals($branchIds, $periodId)->keyBy('branch_id');

        return [
            'datasets' => [
                $this->dataset('Approved Overtime', '#2563eb', $branches, $totals, 'approved_overtime'),
                $this->dataset('Undertime', '#f59e0b', $branches, $totals, 'undertime'),
                $this->dataset('Late', '#ef4444', $branches, $totals, 'late'),
                $this->dataset('Approved Early Overtime', '#06b6d4', $branches, $totals, 'approved_early_overtime'),
            ],
            'labels' => $branches->pluck('branch_name')->all(),
        ];
    }

    protected function getFilters(): ?array
    {
        return PayrollPeriod::query()
            ->where('is_locked', false)
            ->newestFirst()
            ->limit(24)
            ->pluck('title', 'id')
            ->mapWithKeys(fn (string $title, int $id): array => [(string) $id => $title])
            ->all();
    }

    protected function getOptions(): ?array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                    'title' => ['display' => true, 'text' => 'Minutes'],
                ],
            ],
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function assignedBranchIds(): array
    {
        $account = auth('sicrc')->user();

        return $account instanceof SicRcAccount ? $account->assignedBranchIds() : [];
    }

    protected function attendanceTotals(array $branchIds, ?int $periodId): Collection
    {
        if ($branchIds === [] || ! $periodId) {
            return collect();
        }

        return EmployeeVisibleDtr::query()
            ->where('payroll_period_id', $periodId)
            ->whereIn('branch_id', $branchIds)
            ->finalizedAttendance()
            ->select('branch_id')
            ->selectRaw('COALESCE(SUM(CASE WHEN overtime_approved = 1 THEN credited_overtime ELSE 0 END), 0) as approved_overtime')
            ->selectRaw('COALESCE(SUM(undertime), 0) as undertime')
            ->selectRaw('COALESCE(SUM(late), 0) as late')
            ->selectRaw('COALESCE(SUM(CASE WHEN early_clock_in_approved = 1 THEN credited_early_clock_in ELSE 0 END), 0) as approved_early_overtime')
            ->groupBy('branch_id')
            ->get();
    }

    protected function dataset(
        string $label,
        string $color,
        Collection $branches,
        Collection $totals,
        string $column,
    ): array {
        return [
            'label' => $label,
            'data' => $branches
                ->map(fn (Branch $branch): int => (int) ($totals->get($branch->id)?->{$column} ?? 0))
                ->all(),
            'backgroundColor' => $color,
            'borderColor' => $color,
            'borderWidth' => 1,
            'borderRadius' => 4,
        ];
    }
}
