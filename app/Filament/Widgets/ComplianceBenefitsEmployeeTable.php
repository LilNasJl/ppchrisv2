<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DeductionsManagement;
use App\Filament\Pages\EmployeeComplianceBenefits;
use App\Models\Employee;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class ComplianceBenefitsEmployeeTable extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Deductions';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Employee::query()
                ->with(['user', 'branch', 'designation', 'department'])
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
                    ->formatStateUsing(fn (Employee $record): string => $record->company_id ?? 'N/A')
                    ->sortable(),

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

                TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('branch.branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewDeductions')
                        ->label('View Deductions')
                        ->icon(Heroicon::Eye)
                        ->url(fn (Employee $record): string => EmployeeComplianceBenefits::getUrl([
                            'employeeId' => $record->publicKey(),
                            'returnUrl' => $this->tableReturnUrl(DeductionsManagement::getUrl()),
                        ])),
                ])
                    ->icon(Heroicon::EllipsisHorizontal),
            ]);
    }

    protected function tableReturnUrl(string $baseUrl): string
    {
        $query = request()->query();
        unset($query['returnUrl']);

        $query[$this->getTablePaginationPageName()] = $this->getTablePage();

        foreach (['tableRecordsPerPage', 'tableSearch', 'tableFilters', 'tableSort'] as $property) {
            if (filled($this->{$property})) {
                $query[$property] = $this->{$property};
            }
        }

        return $baseUrl.(blank($query) ? '' : '?'.Arr::query($query));
    }
}
