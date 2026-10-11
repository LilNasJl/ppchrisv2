<?php

namespace App\Filament\Employee\Widgets;

use App\Models\Dtr;
use App\Models\EmployeeVisibleDtr;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class EmployeeLatesAbsencesChart extends ChartWidget
{
    protected ?string $heading = 'Lates & Absences';

    protected ?string $description = 'Days recorded in your finalized attendance.';

    protected ?string $maxHeight = '260px';

    public function mount(): void
    {
        $this->filter = (string) now()->year;
        parent::mount();
    }

    protected function getFilters(): ?array
    {
        $employee = auth()->user()?->employee;
        $years = [(int) now()->year];

        if ($employee) {
            $dates = EmployeeVisibleDtr::query()
                ->forEmployee($employee)
                ->whereNotNull('date_in')
                ->selectRaw('MIN(date_in) as first_date, MAX(date_in) as last_date')
                ->first();

            if ($dates?->first_date && $dates?->last_date) {
                $years = array_merge($years, range(Carbon::parse($dates->first_date)->year, Carbon::parse($dates->last_date)->year));
            }

            $fingerprintId = $employee->fingerprint_id ?: $employee->uid;
            if (filled($fingerprintId)) {
                $historicalDates = Dtr::query()
                    ->where('fingerprint_id', $fingerprintId)
                    ->where('branch_id', $employee->branch_id)
                    ->whereNotNull('date_in')
                    ->selectRaw('MIN(date_in) as first_date, MAX(date_in) as last_date')
                    ->first();

                if ($historicalDates?->first_date && $historicalDates?->last_date) {
                    $years = array_merge($years, range(Carbon::parse($historicalDates->first_date)->year, Carbon::parse($historicalDates->last_date)->year));
                }
            }
        }

        $years = array_values(array_unique($years));
        rsort($years);

        return array_combine(array_map('strval', $years), array_map('strval', $years));
    }

    protected function getData(): array
    {
        $year = isset(($this->getFilters() ?? [])[$this->filter ?? '']) ? (int) $this->filter : (int) now()->year;
        $lateDays = array_fill(1, 12, []);
        $absentDays = array_fill(1, 12, []);
        $employee = auth()->user()?->employee;

        if ($employee) {
            $visiblePeriodIds = EmployeeVisibleDtr::query()
                ->forEmployee($employee)
                ->whereBetween('date_in', ["{$year}-01-01", "{$year}-12-31"])
                ->whereNotNull('payroll_period_id')
                ->distinct()
                ->pluck('payroll_period_id');

            $visibleRecords = EmployeeVisibleDtr::query()
                ->forEmployee($employee)
                ->finalizedAttendance()
                ->whereBetween('date_in', ["{$year}-01-01", "{$year}-12-31"])
                ->get(['payroll_period_id', 'date_in', 'time_in', 'late', 'is_absent', 'schedule_type', 'leave_id']);

            $records = $visibleRecords;
            $fingerprintId = $employee->fingerprint_id ?: $employee->uid;

            if (filled($fingerprintId)) {
                $historicalRecords = Dtr::query()
                    ->where('fingerprint_id', $fingerprintId)
                    ->where('branch_id', $employee->branch_id)
                    ->finalizedAttendance()
                    ->whereBetween('date_in', ["{$year}-01-01", "{$year}-12-31"])
                    ->when($visiblePeriodIds->isNotEmpty(), fn ($query) => $query->whereNotIn('payroll_period_id', $visiblePeriodIds))
                    ->get(['date_in', 'time_in', 'late', 'is_absent', 'schedule_type', 'leave_id']);

                $records = $visibleRecords->concat($historicalRecords);
            }

            $records->each(function (Dtr $record) use (&$lateDays, &$absentDays): void {
                $date = Carbon::parse($record->date_in);
                $day = $date->toDateString();
                $type = strtolower(trim((string) $record->schedule_type));

                if ($record->is_absent || $type === 'absent') {
                    $absentDays[$date->month][$day] = true;
                } elseif ((int) $record->late > 0 && filled($record->time_in) && ! $record->leave_id && $type !== 'leave') {
                    $lateDays[$date->month][$day] = true;
                }
            });
        }

        return [
            'datasets' => [
                ['label' => 'Late', 'data' => array_map('count', array_values($lateDays)), 'backgroundColor' => '#f59e0b', 'borderRadius' => 3],
                ['label' => 'Absent', 'data' => array_map('count', array_values($absentDays)), 'backgroundColor' => '#ef4444', 'borderRadius' => 3],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                'x' => ['grid' => ['display' => false]],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
