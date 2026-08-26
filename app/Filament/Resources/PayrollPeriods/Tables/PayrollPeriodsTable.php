<?php

namespace App\Filament\Resources\PayrollPeriods\Tables;

use App\Models\PayrollPeriod;
use App\Services\PayrollPeriodLockService;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Throwable;

class PayrollPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date_start', 'desc')
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

                TextColumn::make('auto_lock_on')
                    ->label('Auto Lock On')
                    ->getStateUsing(fn (PayrollPeriod $record): ?string => $record->date_payout?->copy()->addDay()->toDateString())
                    ->date()
                    ->tooltip('Automatic lock runs after the payout date.'),

                TextColumn::make('auto_lock_status')
                    ->label('Auto Lock Status')
                    ->getStateUsing(fn (PayrollPeriod $record): string => match (true) {
                        $record->is_locked => 'Locked',
                        filled($record->auto_lock_blocked_reason) => 'Blocked',
                        $record->unlocked_at?->greaterThan(now('Asia/Manila')->subDay()) => 'Manual Grace Period',
                        $record->date_payout?->isBefore(now('Asia/Manila')->startOfDay()) => 'Due',
                        default => 'Scheduled',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Locked' => 'success',
                        'Blocked' => 'danger',
                        'Due', 'Manual Grace Period' => 'warning',
                        default => 'gray',
                    })
                    ->tooltip(fn (PayrollPeriod $record): ?string => $record->auto_lock_blocked_reason),

                ToggleColumn::make('is_locked')
                    ->label('Locked')
                    ->onColor('danger')
                    ->offColor('success')
                    ->updateStateUsing(function (PayrollPeriod $record, mixed $state): bool {
                        try {
                            app(PayrollPeriodLockService::class)->setLocked($record, (bool) $state);
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Payroll period was not locked')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();

                            return false;
                        }

                        return (bool) $state;
                    }),

                TextColumn::make('description')
                    ->limit(80)
                    ->wrap(),
            ])
            ->filters([])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
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
