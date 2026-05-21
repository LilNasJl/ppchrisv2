<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Counter;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

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
                        // 👇 Pre-loaded UID preview — lives inside the relationship section
                        TextInput::make('uid')
                            ->label('Employee ID (Auto-generated)')
                            ->default(function (): string {
                                $counter = Counter::first();
                                $next = $counter ? $counter->uid + 1 : 1;

                                return 'PF-'.str_pad($next, 4, '0', STR_PAD_LEFT);
                            })
                            ->disabled()        // User cannot edit it
                            ->dehydrated()      // ⚠️ Critical: disabled fields are excluded from submitted data without this
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
                            ->placeholder('e.g., PF0001')
                            ->default(function (): string {
                                $counter = Counter::first();
                                $next = $counter ? $counter->uid + 1 : 1;

                                return 'PF'.str_pad($next, 4, '0', STR_PAD_LEFT);
                            })
                            ->readOnly()
                            ->dehydrated(false),

                        TextInput::make('email')
                            ->placeholder('e.g., juandelacruz@gmail.com')
                            ->email()
                            ->required()
                            ->unique(
                                table: User::class,
                                column: 'email',
                                ignoreRecord: true,
                            )
                            ->validationAttribute('email address')
                            ->validationMessages([
                                'unique' => 'This email address is already registered.',
                            ]),

                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->placeholder('e.g., password123')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),

                        Toggle::make('is_disabled')
                            ->label('Disable Account')
                            ->helperText('Disabled employee accounts cannot log in until enabled again.')
                            ->default(false)
                            ->inline(false),
                    ])->columns(2),

            ]);
    }
}
