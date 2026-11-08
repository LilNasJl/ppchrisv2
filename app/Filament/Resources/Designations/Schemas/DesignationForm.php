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
                Section::make('Designation Details')
                    ->description('Specify the organizational title, job qualifications, and role responsibilities.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Designation Title')
                            ->placeholder('e.g., Forecourt Attendant, Accounting Staff')
                            ->maxLength(100)
                            ->required(),
                        Textarea::make('specification')
                            ->placeholder('Required skills, education, qualifications, and traits...')
                            ->label('Job Specification')
                            ->maxLength(255)
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->placeholder('Core duties, daily responsibilities, and key results...')
                            ->label('Job Description')
                            ->maxLength(255)
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(1),
            ])->columns(1);
    }
}
