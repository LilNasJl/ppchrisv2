<?php

namespace App\Filament\Resources\Leaves\Tables;

use App\Filament\Resources\Leaves\LeaveResource;
use App\Models\Leave as ModelsLeave;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class LeavesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->whereHas('employee', fn (Builder $query) => $query->activeEmployment())
                ->latest('created_at'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('employee.lastname')
                    ->label('Employee Name')
                    ->formatStateUsing(fn ($record) =>
                        $record->employee
                            ? "{$record->employee->lastname}, {$record->employee->firstname} {$record->employee->middlename}"
                            : 'N/A'
                    )
                    ->searchable(['firstname', 'lastname', 'middlename'])
                    ->sortable(),

                TextColumn::make('leave_type')
                    ->label('Leave Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('requested_days')
                    ->label('Days')
                    ->getStateUsing(fn (ModelsLeave $record): string => (string) $record->getRequestedLeaveDays()),

                TextColumn::make('employee.leave_credits')
                    ->label('Leave Count')
                    ->numeric(),

                TextColumn::make('employee.birthday_leave_credits')
                    ->label('Birthday Leave')
                    ->numeric(),
                    
                TextColumn::make('created_at')
                    ->label('Requested At')
                    ->badge()
                    ->searchable()
                    ->sortable(),


                TextColumn::make('status_updated_at')
                    ->label('Approved/Rejected Date'),

                TextColumn::make('reviewedBy.name')
                    ->label('Approved/Rejected By')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('attachment_original_name')
                    ->label('Attachment')
                    ->url(fn (ModelsLeave $record): ?string => $record->attachment_url, shouldOpenInNewTab: true)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Pending' => 'warning',   // yellow
                        'Approved' => 'success',  // green
                        'Rejected' => 'danger',   // red
                        default => 'gray',
                    }),

                    ])
            ->filters([
                // TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ActionGroup::make([
                        Action::make('approve')
                            ->label('Approve')
                            ->icon(Heroicon::HandThumbUp)
                            ->color('success')
                            ->schema([
                                Textarea::make('hr_comment')
                                    ->label('HR Comment')
                                    ->rows(4)
                                    ->required(),
                            ])
                            ->modalSubmitActionLabel('Approve')
                            ->visible(fn (ModelsLeave $record): bool => $record->status === 'Pending')
                            ->action(function (ModelsLeave $record, array $data): void {
                                try {
                                    $record->approveRequest($data['hr_comment'] ?? null, auth()->id());

                                    Notification::make()
                                        ->title('Leave approved')
                                        ->success()
                                        ->send();
                                } catch (RuntimeException $exception) {
                                    Notification::make()
                                        ->title('Unable to approve leave')
                                        ->body($exception->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),

                        Action::make('reject')
                            ->label('Reject')
                            ->icon(Heroicon::HandThumbDown)
                            ->color('danger')
                            ->schema([
                                Textarea::make('hr_comment')
                                    ->label('HR Comment')
                                    ->rows(4)
                                    ->required(),
                            ])
                            ->modalSubmitActionLabel('Reject')
                            ->visible(fn (ModelsLeave $record): bool => $record->status === 'Pending')
                            ->action(function (ModelsLeave $record, array $data): void {
                                $record->rejectRequest($data['hr_comment'] ?? null, auth()->id());

                                Notification::make()
                                    ->title('Leave rejected')
                                    ->success()
                                    ->send();
                            }),
                    ])
                        ->label('Approval Action')
                        ->visible(fn (ModelsLeave $record): bool => $record->status === 'Pending'),

                    ViewAction::make()
                        ->url(fn (ModelsLeave $record): string => LeaveResource::getUrl('view', ['record' => $record])),
                    DeleteAction::make()
                        ->requiresConfirmation(),
                    // EditAction::make(),
                ])
                // ->icon(Heroicon::EllipsisHorizontal)
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
