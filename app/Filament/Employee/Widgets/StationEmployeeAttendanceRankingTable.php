<?php

namespace App\Filament\Employee\Widgets;

use App\Models\Branch;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
use App\Services\StationManagementAccess;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StationEmployeeAttendanceRankingTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return StationManagementAccess::canAccessStationManagement(auth()->user());
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Employee Attendance Rankings by Branch')
            ->description('Late and undertime hours, weekday absence days, and Saturday absence hours by employee.')
            ->query(fn (): Builder => $this->rankingQuery())
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->weight('bold')
                    ->width('16rem')
                    ->wrap()
                    ->placeholder('Unknown employee'),

                TextColumn::make('branch.branch_name')
                    ->label('Branch')
                    ->width('12rem')
                    ->wrap()
                    ->placeholder('Unknown branch'),

                TextColumn::make('late_hours')
                    ->label('Late Hours')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' hrs')
                    ->alignCenter()
                    ->badge()
                    ->color('danger')
                    ->sortable(),

                TextColumn::make('undertime_hours')
                    ->label('Undertime Hours')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' hrs')
                    ->alignCenter()
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                TextColumn::make('absent_days')
                    ->label('Weekday Absent Days')
                    ->numeric()
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('saturday_absent_hours')
                    ->label('Saturday Absent Hours')
                    ->numeric()
                    ->suffix(' hrs')
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('total_exceptions')
                    ->label('Total Exceptions')
                    ->numeric()
                    ->width('12rem')
                    ->alignCenter()
                    ->badge()
                    ->color('primary')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn (): array => $this->branchOptions())
                    ->searchable()
                    ->placeholder('All assigned branches')
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('employee_visible_dtrs.branch_id', $data['value']),
                    )),

                SelectFilter::make('payroll_period_id')
                    ->label('Payroll Period')
                    ->options(fn (): array => PayrollPeriod::query()
                        ->newestFirst()
                        ->limit(36)
                        ->pluck('title', 'id')
                        ->all())
                    ->default($this->defaultPayrollPeriodId())
                    ->searchable()
                    ->placeholder('Select a payroll period')
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('employee_visible_dtrs.payroll_period_id', $data['value']),
                    )),
            ], FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->filtersFormColumns([
                'default' => 1,
                'lg' => 2,
            ])
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByDesc('total_exceptions')
                ->orderBy('branch_id')
                ->orderBy('employee_id'))
            ->defaultKeySort(false)
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('No attendance ranking data')
            ->emptyStateDescription('No late, undertime, or absence exceptions were found for these filters.');
    }

    private function rankingQuery(): Builder
    {
        $workSchedule = "LOWER(TRIM(COALESCE(schedule_type, ''))) NOT IN ('leave', 'absent', 'overtime')";
        $lateDays = "COUNT(DISTINCT CASE WHEN COALESCE(late, 0) > 0 AND time_in IS NOT NULL AND leave_id IS NULL AND {$workSchedule} THEN date_in END)";
        $undertimeDays = "COUNT(DISTINCT CASE WHEN COALESCE(undertime, 0) > 0 AND time_out IS NOT NULL AND leave_id IS NULL AND {$workSchedule} THEN date_in END)";
        $lateHours = "ROUND(SUM(CASE WHEN COALESCE(late, 0) > 0 AND time_in IS NOT NULL AND leave_id IS NULL AND {$workSchedule} THEN COALESCE(late, 0) ELSE 0 END) / 60.0, 2)";
        $undertimeHours = "ROUND(SUM(CASE WHEN COALESCE(undertime, 0) > 0 AND time_out IS NOT NULL AND leave_id IS NULL AND {$workSchedule} THEN COALESCE(undertime, 0) ELSE 0 END) / 60.0, 2)";
        $isSaturday = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%w', date_in) = '6'"
            : 'DAYOFWEEK(date_in) = 7';
        $absence = "(is_absent = 1 OR LOWER(TRIM(COALESCE(schedule_type, ''))) = 'absent') AND leave_id IS NULL";
        $absentDays = "COUNT(DISTINCT CASE WHEN {$absence} AND NOT ({$isSaturday}) THEN date_in END)";
        $saturdayAbsences = "COUNT(DISTINCT CASE WHEN {$absence} AND {$isSaturday} THEN date_in END)";

        return EmployeeVisibleDtr::query()
            ->with([
                'employee' => fn ($query) => $query->withTrashed(),
                'branch' => fn ($query) => $query->withTrashed(),
            ])
            ->whereIn('branch_id', StationManagementAccess::getManagedBranchIds(auth()->user()))
            ->whereNotNull('date_in')
            ->whereNotNull('employee_id')
            ->finalizedAttendance()
            ->select(['branch_id', 'employee_id'])
            ->selectRaw('MIN(id) as id')
            ->selectRaw("{$lateDays} as late_days")
            ->selectRaw("{$undertimeDays} as undertime_days")
            ->selectRaw("{$lateHours} as late_hours")
            ->selectRaw("{$undertimeHours} as undertime_hours")
            ->selectRaw("{$absentDays} as absent_days")
            ->selectRaw("({$saturdayAbsences}) * 3 as saturday_absent_hours")
            ->selectRaw("({$lateDays}) + ({$undertimeDays}) + ({$absentDays}) + ({$saturdayAbsences}) as total_exceptions")
            ->groupBy('branch_id', 'employee_id')
            ->havingRaw("({$lateDays}) + ({$undertimeDays}) + ({$absentDays}) + ({$saturdayAbsences}) > 0");
    }

    private function branchOptions(): array
    {
        $branchIds = StationManagementAccess::getManagedBranchIds(auth()->user());

        if ($branchIds === []) {
            return [];
        }

        return Branch::withTrashed()
            ->whereIn('id', $branchIds)
            ->orderBy('branch_name')
            ->pluck('branch_name', 'id')
            ->all();
    }

    private function defaultPayrollPeriodId(): ?string
    {
        $id = PayrollPeriod::query()
            ->where('is_locked', false)
            ->newestFirst()
            ->value('id')
            ?? PayrollPeriod::query()->newestFirst()->value('id');

        return $id ? (string) $id : null;
    }
}
