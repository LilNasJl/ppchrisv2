<?php

namespace App\Filament\Resources\Designations\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DesignationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->label('Designation Title')
                            ->placeholder('e.g., IT Staff')
                            ->maxLength(100)
                            ->required(),
                        Textarea::make('specification')
                            ->placeholder('Required skills, education, and traits')
                            ->label('Job Specification')
                            ->maxLength(255)
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->placeholder('Description...')
                            ->label('Job Description')
                            ->maxLength(255)
                            ->rows(5)
                            ->columnSpanFull(),
                    ])->columns(2),
            ])->columns(1);
    }
}
