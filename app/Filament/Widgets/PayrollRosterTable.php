<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollPeriodEmployeeExclusion;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PayrollRosterTable extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Payroll Period Roster Exemptions';

    public ?int $periodId = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => PayrollPeriod::query()
                ->withCount('employeeExclusions')
                ->when(
                    filled($this->periodId),
                    fn (Builder $query) => $query->whereKey($this->periodId),
                    fn (Builder $query) => $query->whereRaw('1 = 0')
                )
                ->latest('date_start'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('title')
                    ->label('Payroll Period')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date_start')
                    ->label('Start')
                    ->date(),

                TextColumn::make('date_end')
                    ->label('End')
                    ->date(),

                TextColumn::make('date_payout')
                    ->label('Payout')
                    ->date(),

                IconColumn::make('is_locked')
                    ->label('Locked')
                    ->boolean(),

                TextColumn::make('employee_exclusions_count')
                    ->label('Exempted Employees')
                    ->badge(),
            ])
            ->recordActions([
                Action::make('manageExemptions')
                    ->label('Manage Exempted')
                    ->icon(Heroicon::UserMinus)
                    ->schema([
                        Select::make('employee_ids')
                            ->label('Employees not included in this payroll period')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => $this->employeeOptions())
                            ->columnSpanFull(),
                    ])
                    ->fillForm(fn (PayrollPeriod $record): array => [
                        'employee_ids' => $record->employeeExclusions()
                            ->pluck('employee_id')
                            ->map(fn (int $id): string => (string) $id)
                            ->all(),
                    ])
                    ->disabled(fn (PayrollPeriod $record): bool => (bool) $record->is_locked)
                    ->tooltip(fn (PayrollPeriod $record): ?string => $record->is_locked ? 'Locked payroll periods cannot be changed.' : null)
                    ->modalHeading('Payroll roster exemptions')
                    ->modalSubmitActionLabel('Save')
                    ->action(function (PayrollPeriod $record, array $data): void {
                        $employeeIds = collect($data['employee_ids'] ?? [])
                            ->map(fn ($id): int => (int) $id)
                            ->filter()
                            ->unique()
                            ->values();

                        DB::transaction(function () use ($record, $employeeIds): void {
                            PayrollPeriodEmployeeExclusion::query()
                                ->where('payroll_period_id', $record->id)
                                ->whereNotIn('employee_id', $employeeIds)
                                ->delete();

                            $employeeIds->each(fn (int $employeeId) => PayrollPeriodEmployeeExclusion::firstOrCreate([
                                'payroll_period_id' => $record->id,
                                'employee_id' => $employeeId,
                            ]));
                        });

                        Notification::make()
                            ->title('Payroll roster updated')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function employeeOptions(): array
    {
        return Employee::query()
            ->with('branch')
            ->activeEmployment()
            ->whereHas('user', fn (Builder $query) => $query->where('role', 'employee'))
            ->orderBy('uid')
            ->get()
            ->mapWithKeys(fn (Employee $employee): array => [
                $employee->id => ($employee->company_id ?? 'N/A').' - '.$employee->full_name.' ('.($employee->branch?->branch_name ?: 'No branch').')',
            ])
            ->all();
    }
}
