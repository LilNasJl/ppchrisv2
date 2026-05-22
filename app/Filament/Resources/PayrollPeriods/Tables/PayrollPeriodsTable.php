<?php

namespace App\Filament\Resources\PayrollPeriods\Tables;

use App\Models\PayrollPeriod;
use App\Services\PayrollCalculator;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class PayrollPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date_start')
                    ->label('Date Start')
                    ->date()
                    ->sortable(),

                TextColumn::make('date_end')
                    ->label('Date End')
                    ->date()
                    ->sortable(),

                TextColumn::make('date_payout')
                    ->label('Date Payout')
                    ->date()
                    ->sortable(),

                IconColumn::make('is_locked')
                    ->label('Locked')
                    ->boolean(),

                TextColumn::make('description')
                    ->limit(80)
                    ->wrap(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('lockPayrollPeriod')
                        ->label('Lock Payroll Period')
                        ->icon('heroicon-m-lock-closed')
                        ->color('danger')
                        ->visible(fn (PayrollPeriod $record): bool => ! (bool) $record->is_locked && ! $record->trashed())
                        ->modalHeading('Permanently lock this payroll period?')
                        ->modalDescription('After locking, this payroll period cannot be unlocked. D.T.R entries, payroll calculations, summaries, and payroll results for this period become fixed and cannot be edited.')
                        ->modalSubmitActionLabel('Lock Payroll Period')
                        ->schema([
                            TextInput::make('password')
                                ->label('Current Admin Password')
                                ->password()
                                ->required(),
                        ])
                        ->action(function (PayrollPeriod $record, array $data): void {
                            if (! Hash::check((string) ($data['password'] ?? ''), (string) auth()->user()?->password)) {
                                Notification::make()
                                    ->title('Unable to lock payroll period')
                                    ->body('The password you entered does not match your current account password.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            app(PayrollCalculator::class)->snapshotPeriod($record);

                            $record->forceFill(['is_locked' => true])->save();

                            Notification::make()
                                ->title('Payroll period locked')
                                ->body('This payroll period can no longer be unlocked or edited.')
                                ->success()
                                ->send();
                        }),

                    EditAction::make()
                        ->visible(fn (PayrollPeriod $record): bool => ! (bool) $record->is_locked),
                    DeleteAction::make()
                        ->modalDescription('This will also soft delete all D.T.R entries using this payroll period.'),
                    RestoreAction::make(),
                    ForceDeleteAction::make()
                        ->modalDescription('This will permanently delete this payroll period and all D.T.R entries using it.'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Actions')
                    ->color('primary'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
