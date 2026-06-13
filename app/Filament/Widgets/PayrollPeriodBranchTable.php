<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\BranchPayrollEmployees;
use App\Models\Branch;
use App\Models\PayrollPeriod;
use App\Models\PayrollPeriodBranchExclusion;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PayrollPeriodBranchTable extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Branches';

    public ?int $periodId = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Branch::query()
                ->when(filled($this->periodId), function (Builder $query): void {
                    $query->whereNotIn('id', PayrollPeriodBranchExclusion::query()
                        ->select('branch_id')
                        ->where('payroll_period_id', $this->periodId));
                })
                ->orderBy('branch_name'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewEmployees')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->url(fn (Branch $record): string => BranchPayrollEmployees::getUrl([
                            'periodId' => PayrollPeriod::query()->find($this->periodId)?->publicKey(),
                            'branchId' => $record->publicKey(),
                        ])),
                ])
                    ->icon(Heroicon::EllipsisHorizontal),
            ]);
    }
}
