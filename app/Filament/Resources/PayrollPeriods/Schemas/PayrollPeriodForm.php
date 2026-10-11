<?php

namespace App\Filament\Resources\PayrollPeriods\Schemas;

use App\Models\PayrollPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayrollPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payroll Period Details')
                    ->description('Specify the dates for this cutoff cycle and configure period settings.')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        TextInput::make('title')
                            ->label('Period Title')
                            ->placeholder('e.g., Oct 11 - 25, 2026')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        DatePicker::make('date_start')
                            ->label('Cutoff Start Date')
                            ->required()
                            ->native(false),

                        DatePicker::make('date_end')
                            ->label('Cutoff End Date')
                            ->required()
                            ->native(false),

                        DatePicker::make('date_payout')
                            ->label('Scheduled Payout Date')
                            ->required()
                            ->native(false)
                            ->helperText('Scheduled date for compensation payout.'),

                        Toggle::make('is_locked')
                            ->label('Lock Payroll Period')
                            ->helperText('Restrict DTR changes and freeze calculation for this cutoff period.')
                            ->default(false),

                        Select::make('loan_quincena')
                            ->label('Loan Deduction Quincena')
                            ->options(['first' => 'First Quincena', 'second' => 'Second Quincena'])
                            ->placeholder('Automatic from standard cutoff dates')
                            ->disabled(fn (?PayrollPeriod $record): bool => (bool) $record?->is_locked),

                        Textarea::make('description')
                            ->label('Description / Notes')
                            ->placeholder('Optional notes or remarks regarding this payroll cycle...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
