<?php

namespace App\Filament\Resources\PayrollPeriods\Schemas;

use Filament\Forms\Components\DatePicker;
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
                Section::make('Payroll Period')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        DatePicker::make('date_start')
                            ->label('Date Start')
                            ->required(),

                        DatePicker::make('date_end')
                            ->label('Date End')
                            ->required(),

                        DatePicker::make('date_payout')
                            ->label('Date Payout')
                            ->required(),

                        Toggle::make('is_locked')
                            ->label('Locked')
                            ->helperText('Turn this on to restrict D.T.R changes for this payroll period. It can be turned off again when corrections are needed.')
                            ->default(false),

                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
