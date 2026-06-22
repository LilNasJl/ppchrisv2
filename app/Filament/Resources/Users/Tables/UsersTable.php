<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Models\AccountStatusHistory;
use App\Models\Employee;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['employee.branch', 'employee.department'])
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

                ImageColumn::make('profile_photo')
                    ->label('Profile')
                    ->getStateUsing(fn ($record): ?string => $record->profile_photo_url)
                    ->defaultImageUrl(fn (): string => url('/image/ppc_logo_circle.png'))
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
                TextColumn::make('employee.department.name')
                    ->label('Department')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

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

                TextColumn::make('is_disabled')
                    ->label('Account')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Disabled' : 'Enabled')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success'),

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
                    EditAction::make()
                        ->url(fn (User $record, ?object $livewire = null): string => UserResource::getUrl('edit', [
                            'record' => $record,
                            'returnUrl' => static::tableReturnUrl($livewire, UserResource::getUrl()),
                        ])),
                    Action::make('toggleAccountStatus')
                        ->label(fn (User $record): string => $record->is_disabled ? 'Enable Account' : 'Disable Account')
                        ->icon(fn (User $record): Heroicon => $record->is_disabled ? Heroicon::CheckCircle : Heroicon::NoSymbol)
                        ->color(fn (User $record): string => $record->is_disabled ? 'success' : 'danger')
                        ->requiresConfirmation()
                        ->modalHeading(fn (User $record): string => $record->is_disabled ? 'Enable employee account?' : 'Disable employee account?')
                        ->modalDescription('Please add remarks for this account status change. This will be saved in the account history.')
                        ->schema([
                            Textarea::make('remarks')
                                ->label('Remarks')
                                ->required()
                                ->rows(4)
                                ->maxLength(1000)
                                ->columnSpanFull(),
                        ])
                        ->action(function (User $record, array $data): void {
                            $newState = ! (bool) $record->is_disabled;

                            $record->forceFill([
                                'is_disabled' => $newState,
                            ])->save();

                            AccountStatusHistory::create([
                                'user_id' => $record->id,
                                'changed_by_user_id' => auth()->id(),
                                'is_disabled' => $newState,
                                'remarks' => $data['remarks'] ?? null,
                            ]);

                            Notification::make()
                                ->title($newState ? 'Account disabled' : 'Account enabled')
                                ->success()
                                ->send();
                        }),
                    Action::make('accountHistory')
                        ->label('Account History')
                        ->icon(Heroicon::Clock)
                        ->modalHeading(fn (User $record): string => ($record->employee?->full_name ?? 'Employee').' Account History')
                        ->modalSubmitAction(false)
                        ->modalContent(fn (User $record) => view('filament.resources.users.partials.account-history', [
                            'histories' => $record->accountStatusHistories()
                                ->with('changedBy')
                                ->get(),
                        ])),
                ])
                    ->icon(Heroicon::EllipsisHorizontal),

            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function tableReturnUrl(?object $livewire, string $baseUrl): string
    {
        $query = request()->query();
        unset($query['returnUrl']);

        if ($livewire && method_exists($livewire, 'getTablePaginationPageName') && method_exists($livewire, 'getTablePage')) {
            $query[$livewire->getTablePaginationPageName()] = $livewire->getTablePage();
        }

        foreach (['tableRecordsPerPage', 'tableSearch', 'tableFilters', 'tableSort'] as $property) {
            if ($livewire && property_exists($livewire, $property) && filled($livewire->{$property})) {
                $query[$property] = $livewire->{$property};
            }
        }

        return $baseUrl.(blank($query) ? '' : '?'.Arr::query($query));
    }
}
