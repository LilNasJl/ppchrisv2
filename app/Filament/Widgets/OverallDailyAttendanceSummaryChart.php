<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardDtrFilters;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class OverallDailyAttendanceSummaryChart extends ChartWidget
{
    use HasDashboardDtrFilters;

    protected string $filterQueryKey = 'attendanceFilters';

    public ?array $filters = [];

    protected ?string $description = 'Employee entries for the selected branch, payroll period, and date.';

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '280px';

    public function getHeading(): string
    {
        return 'Overall Daily Attendance Summary - '.$this->selectedBranchLabel();
    }

    protected function hasAttendanceDateFilter(): bool
    {
        return true;
    }

    protected function getData(): array
    {
        $records = $this->filteredDtrRecords(applySelectedDate: true);

        $present = (clone $records)
            ->where(function (Builder $query): void {
                $query->whereNull('is_absent')->orWhere('is_absent', false);
            })
            ->whereNotNull('time_in')
            ->whereRaw("LOWER(TRIM(COALESCE(schedule_type, ''))) NOT IN ('absent', 'leave')")
            ->count();

        $absent = (clone $records)
            ->where(function (Builder $query): void {
                $query->where('is_absent', true)
                    ->orWhereRaw("LOWER(TRIM(COALESCE(schedule_type, ''))) = 'absent'");
            })
            ->count();

        $late = (clone $records)
            ->where('late', '>', 0)
            ->where(function (Builder $query): void {
                $query->whereNull('is_absent')->orWhere('is_absent', false);
            })
            ->whereNotNull('time_in')
            ->whereRaw("LOWER(TRIM(COALESCE(schedule_type, ''))) NOT IN ('absent', 'leave')")
            ->count();

        return [
            'datasets' => [[
                'label' => 'DTR Entries',
                'data' => [$present, $absent, $late],
                'backgroundColor' => ['#16a34a', '#dc2626', '#f59e0b'],
                'borderColor' => ['#15803d', '#b91c1c', '#d97706'],
                'borderWidth' => 1,
                'borderRadius' => 4,
            ]],
            'labels' => ['Present', 'Absent', 'Late'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                    'title' => ['display' => true, 'text' => 'Employee entries'],
                ],
                'x' => ['grid' => ['display' => false]],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
