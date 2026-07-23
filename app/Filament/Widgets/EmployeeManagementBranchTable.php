<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ComplianceBenefits;
use App\Filament\Pages\EmployeeDetails;
use App\Models\Branch;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class EmployeeManagementBranchTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Branches';

    public string $context = 'records';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Branch::query()
                ->withCount([
                    'employees as employee_accounts_count' => fn (Builder $query): Builder => $query
                        ->whereHas('user', fn (Builder $userQuery): Builder => $userQuery
                            ->where('role', 'employee')),
                ])
                ->orderBy('branch_name'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('branch_address')
                    ->label('Address')
                    ->searchable()
                    ->wrap()
                    ->placeholder('No address set'),

                TextColumn::make('employee_accounts_count')
                    ->label('Employees')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('viewEmployees')
                    ->label('View Employees')
                    ->icon(Heroicon::Users)
                    ->url(fn (Branch $record): string => $this->branchUrl($record)),
            ])
            ->recordUrl(fn (Branch $record): string => $this->branchUrl($record));
    }

    protected function branchUrl(Branch $branch): string
    {
        $parameters = ['branchId' => $branch->publicKey()];

        return $this->context === 'deductions'
            ? ComplianceBenefits::getUrl($parameters)
            : EmployeeDetails::getUrl($parameters);
    }
}
