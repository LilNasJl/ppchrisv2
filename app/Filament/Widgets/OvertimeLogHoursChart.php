<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardDtrFilters;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OvertimeLogHoursChart extends ChartWidget
{
    use HasDashboardDtrFilters;

    protected string $filterQueryKey = 'overtimeFilters';

    public ?array $filters = [];

    protected ?string $description = 'Approved, rejected, and pending overtime and early time-in hours.';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '280px';

    public function getHeading(): string
    {
        return 'Overtime Log Hours - '.$this->selectedBranchLabel();
    }

    protected function getData(): array
    {
        $records = $this->filteredDtrRecords();

        $approvedOvertime = (int) (clone $records)
            ->where('overtime_approved', true)
            ->sum(DB::raw('CASE WHEN COALESCE(credited_overtime, 0) > COALESCE(credited_early_clock_in, 0) THEN COALESCE(credited_overtime, 0) - COALESCE(credited_early_clock_in, 0) ELSE 0 END'));

        $approvedEarlyTimeIn = (int) (clone $records)
            ->where('early_clock_in_approved', true)
            ->sum(DB::raw('CASE WHEN COALESCE(credited_early_clock_in, 0) > 0 THEN credited_early_clock_in ELSE COALESCE(early_clock_in, 0) END'));

        $rejectedOvertime = (int) (clone $records)
            ->where('overtime_status', 'Rejected')
            ->where('overtime', '>=', 30)
            ->sum('overtime');

        $rejectedEarlyTimeIn = (int) (clone $records)
            ->where('overtime_status', 'Rejected')
            ->where('early_clock_in', '>=', 30)
            ->sum('early_clock_in');

        $pendingRecords = (clone $records)
            ->where(function (Builder $query): void {
                $query->whereNull('overtime_status')
                    ->orWhereRaw('LOWER(TRIM(overtime_status)) != ?', ['rejected']);
            });

        $pendingOvertime = (int) (clone $pendingRecords)
            ->where('overtime', '>=', 30)
            ->where(function (Builder $query): void {
                $query->where('overtime_approved', false)->orWhereNull('overtime_approved');
            })
            ->sum('overtime');

        $pendingEarlyTimeIn = (int) (clone $pendingRecords)
            ->where('early_clock_in', '>=', 30)
            ->where(function (Builder $query): void {
                $query->where('early_clock_in_approved', false)->orWhereNull('early_clock_in_approved');
            })
            ->sum('early_clock_in');

        return [
            'datasets' => [[
                'label' => 'Hours',
                'data' => array_map(
                    fn (int $minutes): float => round($minutes / 60, 2),
                    [$approvedOvertime, $approvedEarlyTimeIn, $rejectedOvertime, $rejectedEarlyTimeIn, $pendingOvertime, $pendingEarlyTimeIn],
                ),
                'backgroundColor' => ['#16a34a', '#2563eb', '#f97316', '#dc2626', '#eab308', '#8b5cf6'],
                'borderColor' => ['#15803d', '#1d4ed8', '#c2410c', '#b91c1c', '#ca8a04', '#7c3aed'],
                'borderWidth' => 1,
                'borderRadius' => 4,
            ]],
            'labels' => [
                'Approved Overtime',
                'Approved Early Time-In',
                'Rejected Overtime',
                'Rejected Early Time-In',
                'Pending Overtime',
                'Pending Early Time-In',
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'title' => ['display' => true, 'text' => 'Hours'],
                ],
                'y' => ['grid' => ['display' => false]],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
