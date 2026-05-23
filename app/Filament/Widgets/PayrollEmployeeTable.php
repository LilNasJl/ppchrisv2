<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\EmployeePayroll;
use App\Models\Employee;
use App\Services\PayrollCalculator;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PayrollEmployeeTable extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Employee Payroll';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => app(PayrollCalculator::class)->employeesQuery())
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                ImageColumn::make('user.profile_photo_path')
                    ->label('Profile')
                    ->disk('public')
                    ->circular(),

                TextColumn::make('fullname')
                    ->label('Name')
                    ->getStateUsing(fn (Employee $record): string => trim($record->lastname.', '.(filled($record->middlename) ? $record->middlename.'. ' : '').$record->firstname))
                    ->searchable(['lastname', 'firstname', 'middlename'])
                    ->sortable(['lastname', 'firstname', 'middlename']),

                TextColumn::make('designation.title')
                    ->label('Designation')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('branch.branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewPayroll')
                        ->label('View Payroll')
                        ->icon(Heroicon::Eye)
                        ->url(fn (Employee $record): string => EmployeePayroll::getUrl([
                            'employeeId' => $record->id,
                            'branchId' => $record->branch_id,
                        ])),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
