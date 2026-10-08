<?php

namespace App\Filament\Resources\PayrollPeriods\Tables;

use App\Filament\Pages\Payroll;
use App\Filament\Pages\PayrollByBranch;
use App\Filament\Pages\PayrollSummary;
use App\Models\PayrollPeriod;
use App\Services\PayrollPeriodLockService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
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
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('date_start')
                    ->label('Date Start')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('date_end')
                    ->label('Date End')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('date_payout')
                    ->label('Date Payout')
                    ->date('M d, Y')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('auto_lock_on')
                    ->label('Auto Lock On')
                    ->getStateUsing(fn (PayrollPeriod $record): ?string => $record->date_payout?->copy()->addDay()->toDateString())
                    ->date('M d, Y')
                    ->tooltip('Automatic locking runs 1 day after the scheduled payout date.'),

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
                    ->tooltip(fn (PayrollPeriod $record): string => $record->is_locked ? 'Locked (Not currently running)' : 'Open (Currently running)')
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
                    ->label('Description')
                    ->getStateUsing(function (PayrollPeriod $record): string {
                        if ($record->is_locked && (blank($record->description) || $record->description === 'Payroll currently running')) {
                            return 'Payroll not currently running';
                        }

                        if (! $record->is_locked && (blank($record->description) || $record->description === 'Payroll not currently running')) {
                            return 'Payroll currently running';
                        }

                        return (string) $record->description;
                    })
                    ->badge()
                    ->color(fn (PayrollPeriod $record): string => $record->is_locked ? 'gray' : 'success'),
            ])
            ->filters([
                SelectFilter::make('is_locked')
                    ->label('Lock Status')
                    ->options([
                        '0' => 'Currently Running (Open)',
                        '1' => 'Locked (Not Running)',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn (PayrollPeriod $record): bool => ! (bool) $record->is_locked),
                    Action::make('open_payroll')
                        ->label('Payroll Processing')
                        ->icon('heroicon-o-table-cells')
                        ->color('primary')
                        ->url(fn (PayrollPeriod $record): string => Payroll::getUrl(['periodId' => $record->publicKey()])),
                    Action::make('open_branch')
                        ->label('Payroll By Branch')
                        ->icon('heroicon-o-building-storefront')
                        ->color('info')
                        ->url(fn (PayrollPeriod $record): string => PayrollByBranch::getUrl(['periodId' => $record->publicKey()])),
                    Action::make('open_summary')
                        ->label('Payroll Summary')
                        ->icon('heroicon-o-chart-bar')
                        ->color('success')
                        ->url(fn (PayrollPeriod $record): string => PayrollSummary::getUrl(['periodId' => $record->publicKey()])),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Actions')
                    ->color('primary'),
            ])
            ->toolbarActions([
                Action::make('return')
                    ->label('Return')
                    ->icon('heroicon-o-arrow-left')
                    ->color('gray')
                    ->url(Payroll::getUrl()),
            ]);
    }
}
