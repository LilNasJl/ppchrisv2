<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\EmployeePayroll;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollPeriodBranchExclusion;
use App\Models\PayrollPeriodEmployeeExclusion;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class BranchPayrollEmployeeTable extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Employees';

    public ?int $branchId = null;

    public ?int $periodId = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Employee::query()
                ->with(['user', 'designation', 'branch'])
                ->activeEmployment()
                ->where('branch_id', $this->branchId)
                ->when(
                    $this->isBranchExcluded(),
                    fn (Builder $query) => $query->whereRaw('1 = 0')
                )
                ->when(filled($this->periodId), function (Builder $query): void {
                    $query->whereNotIn('employees.id', PayrollPeriodEmployeeExclusion::query()
                        ->select('employee_id')
                        ->where('payroll_period_id', $this->periodId));
                })
                ->whereHas('user', fn (Builder $query) => $query->where('role', 'employee'))
                ->orderBy('uid'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                ImageColumn::make('profile_photo')
                    ->label('Profile')
                    ->getStateUsing(fn (Employee $record): ?string => $record->user?->profile_photo_url)
                    ->defaultImageUrl(fn (): string => url('/image/ppc_logo_circle.png'))
                    ->circular(),

                TextColumn::make('uid')
                    ->label('ID No.')
                    ->badge()
                    ->formatStateUsing(fn (Employee $record): string => $record->company_id ?? 'N/A'),

                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['lastname', 'middlename', 'firstname'])
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('lastname', $direction)
                        ->orderBy('middlename', $direction)
                        ->orderBy('firstname', $direction)),

                TextColumn::make('designation.title')
                    ->label('Designation')
                    ->badge()
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewPayroll')
                        ->label('View Payroll')
                        ->icon(Heroicon::Eye)
                        ->url(fn (Employee $record): string => EmployeePayroll::getUrl([
                            'employeeId' => $record->publicKey(),
                            'branchId' => Branch::query()->find($record->branch_id)?->publicKey(),
                            'periodId' => PayrollPeriod::query()->find($this->periodId)?->publicKey(),
                        ])),
                ])
                    ->icon(Heroicon::EllipsisHorizontal),
            ]);
    }

    protected function isBranchExcluded(): bool
    {
        if (blank($this->periodId) || blank($this->branchId)) {
            return false;
        }

        return PayrollPeriodBranchExclusion::query()
            ->where('payroll_period_id', $this->periodId)
            ->where('branch_id', $this->branchId)
            ->exists();
    }
}
