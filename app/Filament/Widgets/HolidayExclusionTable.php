<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\HolidayEmployeeExclusion;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class HolidayExclusionTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Holiday Exclusions';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Holiday::query()
                ->with(['type', 'branch'])
                ->withCount('employeeExclusions')
                ->latest('date'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('title')
                    ->label('Holiday')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('type.type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('scope')
                    ->label('Scope')
                    ->getStateUsing(fn (Holiday $record): string => $record->branch?->branch_name ?: 'National')
                    ->badge(),

                TextColumn::make('employee_exclusions_count')
                    ->label('Excluded')
                    ->numeric(),
            ])
            ->recordActions([
                Action::make('manageExclusions')
                    ->label('Manage Exclusions')
                    ->icon(Heroicon::UserMinus)
                    ->modalWidth('5xl')
                    ->schema([
                        Select::make('employee_ids')
                            ->label('Excluded Employees')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn (?Holiday $record = null): array => $record ? $this->employeeOptions($record) : [])
                            ->helperText('Selected employees will not receive this holiday rate/pay for this holiday.')
                            ->columnSpanFull(),

                        Toggle::make('applies_every_year')
                            ->label('Apply selected exclusions every year')
                            ->helperText('Use this only for fixed yearly holidays. Movable holidays should stay date-specific.'),

                        Textarea::make('reason')
                            ->label('Reason')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->fillForm(fn (Holiday $record): array => $this->exclusionFormData($record))
                    ->action(fn (Holiday $record, array $data): mixed => $this->syncExclusions($record, $data)),
            ]);
    }

    protected function employeeOptions(Holiday $holiday): array
    {
        return Employee::query()
            ->with('branch')
            ->activeEmployment()
            ->whereHas('user', fn (Builder $query) => $query->where('role', 'employee'))
            ->when($holiday->branch_id, fn (Builder $query, int $branchId): Builder => $query->where('employees.branch_id', $branchId))
            ->leftJoin('branches', 'branches.id', '=', 'employees.branch_id')
            ->select('employees.*')
            ->orderBy('branches.branch_name')
            ->orderBy('employees.lastname')
            ->orderBy('employees.firstname')
            ->get()
            ->mapWithKeys(fn (Employee $employee): array => [
                $employee->id => trim(($employee->branch?->branch_name ?: 'No Branch').' - '.$employee->company_id.' - '.$employee->full_name),
            ])
            ->all();
    }

    protected function exclusionFormData(Holiday $holiday): array
    {
        $exclusions = $this->managedExclusions($holiday)->get();

        return [
            'employee_ids' => $exclusions->pluck('employee_id')->all(),
            'applies_every_year' => $exclusions->contains(fn (HolidayEmployeeExclusion $exclusion): bool => (bool) $exclusion->applies_every_year),
            'reason' => $exclusions->first()?->reason,
        ];
    }

    protected function syncExclusions(Holiday $holiday, array $data): void
    {
        $employeeIds = collect($data['employee_ids'] ?? [])
            ->filter()
            ->map(fn ($employeeId): int => (int) $employeeId)
            ->unique()
            ->values();

        $this->managedExclusions($holiday)->delete();

        $occurrenceDate = Carbon::parse($holiday->date)->toDateString();

        $employeeIds->each(function (int $employeeId) use ($holiday, $data, $occurrenceDate): void {
            HolidayEmployeeExclusion::query()->create([
                'holiday_id' => $holiday->id,
                'employee_id' => $employeeId,
                'occurrence_date' => $occurrenceDate,
                'applies_every_year' => (bool) ($data['applies_every_year'] ?? false),
                'reason' => blank($data['reason'] ?? null) ? null : trim((string) $data['reason']),
                'created_by' => auth()->id(),
            ]);
        });

        $this->flushCachedTableRecords();

        Notification::make()
            ->title('Holiday exclusions updated')
            ->body($employeeIds->count().' employee(s) excluded.')
            ->success()
            ->send();
    }

    protected function managedExclusions(Holiday $holiday): Builder
    {
        $date = Carbon::parse($holiday->date)->startOfDay();

        return HolidayEmployeeExclusion::query()
            ->where('holiday_id', $holiday->id)
            ->where(function (Builder $query) use ($date): void {
                $query
                    ->whereDate('occurrence_date', $date->toDateString())
                    ->orWhere(function (Builder $query) use ($date): void {
                        $query
                            ->where('applies_every_year', true)
                            ->whereMonth('occurrence_date', $date->month)
                            ->whereDay('occurrence_date', $date->day);
                    });
            });
    }
}
