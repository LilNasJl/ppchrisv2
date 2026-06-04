<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // Group: Employee Initial Details
                Section::make('Employee Initial Details')
                    ->relationship('employee')
                    ->description('Personal identification information.')
                    ->schema([
                        TextInput::make('uid')
                            ->label('Employee ID')
                            ->prefix('PF-')
                            ->placeholder('0001')
                            ->helperText('Enter the employee ID number only. The PF- prefix is added automatically.')
                            ->required()
                            ->maxLength(20)
                            ->formatStateUsing(fn ($state): ?string => Employee::normalizeUid($state))
                            ->dehydrateStateUsing(fn ($state): ?string => Employee::normalizeUid($state))
                            ->rules(fn (?Employee $record): array => [
                                'regex:/^[0-9]+$/',
                                Rule::unique('employees', 'uid')->ignore($record?->id),
                            ])
                            ->validationAttribute('employee ID')
                            ->validationMessages([
                                'regex' => 'The employee ID must contain numbers only.',
                                'unique' => 'This employee ID is already registered.',
                            ])
                            ->columnSpanFull(),

                        TextInput::make('firstname')
                            ->placeholder('e.g., Juan')
                            ->required(),
                        TextInput::make('middlename')
                            ->placeholder('e.g., Dela'),
                        TextInput::make('lastname')
                            ->placeholder('e.g., Cruz')
                            ->required(),
                        Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ]),
                        DatePicker::make('birthdate')
                            ->required(),
                        DatePicker::make('hired_date')
                            ->required(),
                        Select::make('designation_id')
                            ->relationship('designation', 'title')
                            ->required()
                            ->columnSpanFull(),
                        Select::make('branch_id')
                            ->relationship('branch', 'branch_name')
                            ->required()
                            ->columnSpanFull(),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->required()
                            ->columnSpanFull(),

                    ])->columns(2), // Fits 3 names across one row nicely

                // Group: Employee Account Details
                Section::make('Employee Account Details')
                    ->description('Primary login and security information.')
                    ->schema([
                        TextInput::make('username')
                            ->label('Username')
                            ->helperText('This is automatically set from the employee ID without the dash, e.g. PF0001.')
                            ->readOnly()
                            ->dehydrated(false),

                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->default(fn (string $context): ?string => $context === 'create' ? 'PASSWORD1.' : null)
                            ->readOnly(fn (string $context): bool => $context === 'create')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->helperText('New employee accounts start with PASSWORD1.'),

                        Toggle::make('is_disabled')
                            ->label('Disable Account')
                            ->helperText('Disabled employee accounts cannot log in until enabled again.')
                            ->default(false)
                            ->inline(false),
                    ])->columns(2),

            ]);
    }
}
