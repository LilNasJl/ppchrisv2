<?php

namespace App\Filament\Widgets;

use App\Models\Branch;
use App\Models\PayrollPeriod;
use App\Models\PayrollPeriodBranchExclusion;
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

class PayrollRosterBranchTable extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Branch Payroll Roster Exemptions';

    public ?int $periodId = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => PayrollPeriod::query()
                ->withCount('branchExclusions')
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

                TextColumn::make('branch_exclusions_count')
                    ->label('Exempted Branches')
                    ->badge(),
            ])
            ->recordActions([
                Action::make('manageExemptions')
                    ->label('Manage Exempted')
                    ->icon(Heroicon::BuildingOffice2)
                    ->schema([
                        Select::make('branch_ids')
                            ->label('Branches not included in this payroll period')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => $this->branchOptions())
                            ->columnSpanFull(),
                    ])
                    ->fillForm(fn (PayrollPeriod $record): array => [
                        'branch_ids' => $record->branchExclusions()
                            ->pluck('branch_id')
                            ->map(fn (int $id): string => (string) $id)
                            ->all(),
                    ])
                    ->disabled(fn (PayrollPeriod $record): bool => (bool) $record->is_locked)
                    ->tooltip(fn (PayrollPeriod $record): ?string => $record->is_locked ? 'Locked payroll periods cannot be changed.' : null)
                    ->modalHeading('Branch payroll roster exemptions')
                    ->modalSubmitActionLabel('Save')
                    ->action(function (PayrollPeriod $record, array $data): void {
                        $branchIds = collect($data['branch_ids'] ?? [])
                            ->map(fn ($id): int => (int) $id)
                            ->filter()
                            ->unique()
                            ->values();

                        DB::transaction(function () use ($record, $branchIds): void {
                            PayrollPeriodBranchExclusion::query()
                                ->where('payroll_period_id', $record->id)
                                ->whereNotIn('branch_id', $branchIds)
                                ->delete();

                            $branchIds->each(fn (int $branchId) => PayrollPeriodBranchExclusion::firstOrCreate([
                                'payroll_period_id' => $record->id,
                                'branch_id' => $branchId,
                            ]));
                        });

                        Notification::make()
                            ->title('Branch payroll roster updated')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function branchOptions(): array
    {
        return Branch::query()
            ->orderBy('branch_name')
            ->pluck('branch_name', 'id')
            ->all();
    }
}
