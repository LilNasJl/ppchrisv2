<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Employee;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['employee.branch'])
                ->where('role', 'employee')
                ->whereHas('employee')
                ->leftJoin('employees as account_employees', 'account_employees.user_id', '=', 'users.id')
                ->select('users.*'))
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderBy('account_employees.uid'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                ImageColumn::make('profile_photo_path')
                    ->label('Profile')
                    ->disk('public')
                    ->circular(),

                TextColumn::make('employee.uid')
                    ->label('ID No.')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => Employee::companyIdFromUid($state) ?? 'N/A'),
                TextColumn::make('employee.lastname')
                    ->label('Employee Name')
                    ->formatStateUsing(fn ($record): string => $record->employee?->full_name ?? 'N/A')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('employee', fn (Builder $employeeQuery): Builder => $employeeQuery
                            ->where('lastname', 'like', "%{$search}%")
                            ->orWhere('middlename', 'like', "%{$search}%")
                            ->orWhere('firstname', 'like', "%{$search}%")))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('account_employees.lastname', $direction)
                        ->orderBy('account_employees.middlename', $direction)
                        ->orderBy('account_employees.firstname', $direction)),
                TextColumn::make('employee.branch.branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('employee.employment_type')
                    ->label('Employment Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, $record): string => $record->employee?->hasEndedEmployment()
                            ? "Employment End: {$state}"
                            : ($state ?: 'N/A')
                    )
                    ->color(fn ($record): string => $record->employee?->hasEndedEmployment() ? 'danger' : 'success'),

                ToggleColumn::make('is_disabled')
                    ->label('Disabled')
                    ->onColor('danger')
                    ->offColor('success'),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('users.created_at', 'like', "%{$search}%"))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('users.created_at', $direction)),
                // // Row index
                // TextColumn::make('index')
                //     ->label('#')
                //     ->rowIndex(),

                // // FULL NAME (fixed version)
                // TextColumn::make('firstname')
                //     ->label('Employee Name')
                //     ->formatStateUsing(function ($record) {
                //         return trim(
                //             $record->firstname . ' ' .
                //             ($record->middlename ? $record->middlename . ' ' : '') .
                //             $record->lastname
                //         );
                //     })
                //     ->searchable(query: function (Builder $query, string $search) {
                //         $query->where(function ($q) use ($search) {
                //             $q->where('firstname', 'like', "%{$search}%")
                //               ->orWhere('middlename', 'like', "%{$search}%")
                //               ->orWhere('lastname', 'like', "%{$search}%");
                //         });
                //     })
                //     ->sortable(query: function (Builder $query, string $direction) {
                //         $query->orderBy('firstname', $direction);
                //     }),

                // // Designation
                // TextColumn::make('designation.title')
                //     ->label('Designation')
                //     ->sortable()
                //     ->searchable(),

                // // Department
                // TextColumn::make('department.name')
                //     ->label('Department')
                //     ->sortable()
                //     ->searchable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->modalContent(fn ($record) => view('filament.resources.users.partials.profile-picture', [
                            'record' => $record,
                        ])),
                    EditAction::make(),
                ])
                    ->icon(Heroicon::EllipsisHorizontal),

            ])

            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                //     ForceDeleteBulkAction::make(),
                //     RestoreBulkAction::make(),
                // ]),
            ]);
    }
}
