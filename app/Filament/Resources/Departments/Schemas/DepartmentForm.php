<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Department Details')
                    ->description('Create new department')
                    ->schema([
                        TextInput::make('name')
                            ->label('Department Name')
                            ->placeholder('e.g., Provincial Information Communication Technology Office')
                            ->maxLength(100)
                            ->required(),
                        TextInput::make('acronym')
                            ->label('Acronym')
                            ->placeholder('e.g., PICTO')
                            ->required(),
                        Textarea::make('description')
                            ->placeholder('Description...')
                            ->label('Department description')
                            ->maxLength(255)
                            ->rows(5)
                            ->columnSpanFull()
                    ])->columns(2),
            ])->columns(1);
    }
}
