<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Employee;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Employee Details (entangled with 'employee' relationship)
                Group::make([
                    // 1. Personal Information
                    Section::make('Personal Information')
                        ->description('Core employee identity, legal names, and contact details.')
                        ->icon(Heroicon::User)
                        ->schema([
                            TextInput::make('uid')
                                ->label('Employee ID')
                                ->prefix('PF-')
                                ->placeholder('0001')
                                ->helperText('Numeric ID only (prefix PF- is added automatically).')
                                ->required()
                                ->maxLength(20)
                                ->live(onBlur: true)
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
                                ->label('First Name')
                                ->placeholder('e.g., Juan')
                                ->required(),

                            TextInput::make('middlename')
                                ->label('Middle Name')
                                ->placeholder('e.g., Dela (optional)'),

                            TextInput::make('lastname')
                                ->label('Last Name')
                                ->placeholder('e.g., Cruz')
                                ->required(),

                            DatePicker::make('birthdate')
                                ->label('Date of Birth')
                                ->required()
                                ->native(false)
                                ->maxDate(now()),

                            Select::make('gender')
                                ->label('Gender')
                                ->options([
                                    'male' => 'Male',
                                    'female' => 'Female',
                                ])
                                ->native(false),

                            Select::make('status')
                                ->label('Civil Status')
                                ->options([
                                    'single' => 'Single',
                                    'married' => 'Married',
                                    'widowed' => 'Widowed',
                                    'separated' => 'Separated',
                                    'divorce' => 'Divorced',
                                    'annuled' => 'Annulled',
                                ])
                                ->default('single')
                                ->native(false),

                            TextInput::make('mobile')
                                ->label('Mobile #')
                                ->tel()
                                ->mask('99999999999')
                                ->placeholder('09123456789')
                                ->regex('/^09\d{9}$/')
                                ->validationMessages([
                                    'regex' => 'The mobile number must start with 09 and be exactly 11 digits.',
                                ])
                                ->columnSpan(['default' => 1, 'md' => 1]),

                            TextInput::make('address')
                                ->label('Permanent Address')
                                ->placeholder('e.g., Tagum City, Davao del Norte')
                                ->columnSpan(['default' => 1, 'md' => 2]),
                        ])
                        ->columns([
                            'default' => 1,
                            'md' => 3,
                        ]),

                    // 2. Work Assignment & Designation
                    Section::make('Work Assignment & Designation')
                        ->description('Organizational unit, station branch, and employment status.')
                        ->icon(Heroicon::Briefcase)
                        ->schema([
                            Select::make('branch_id')
                                ->label('Assigned Branch')
                                ->relationship('branch', 'branch_name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('department_id')
                                ->label('Department')
                                ->relationship('department', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('designation_id')
                                ->label('Position / Designation')
                                ->relationship('designation', 'title')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('employment_type')
                                ->label('Employment Type')
                                ->options(Employee::employmentTypeOptions())
                                ->default('Permanent')
                                ->searchable()
                                ->required(),

                            DatePicker::make('hired_date')
                                ->label('Date Hired')
                                ->native(false)
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->columns([
                            'default' => 1,
                            'md' => 2,
                        ]),

                    // 3. Compensation & Payroll
                    Section::make('Compensation & Payroll')
                        ->description('Rate structure, schedule profile, and payment mechanism.')
                        ->icon(Heroicon::Banknotes)
                        ->schema([
                            Select::make('rate_type')
                                ->label('Rate Type')
                                ->options([
                                    'daily' => 'Daily Rate',
                                    'monthly' => 'Monthly Salary',
                                ])
                                ->live()
                                ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('schedule_type', $state === 'daily' ? 'daily' : 'regular'))
                                ->required()
                                ->native(false),

                            Select::make('schedule_type')
                                ->label('Schedule Profile')
                                ->options([
                                    'regular' => 'Regular Sched (Monthly Basis)',
                                    'daily' => 'Daily Sched (Daily Basis)',
                                ])
                                ->default('regular')
                                ->disabled()
                                ->dehydrated(true)
                                ->native(false),

                            Select::make('payment_type')
                                ->label('Payment Method')
                                ->options([
                                    'atm' => 'ATM / Bank Transfer',
                                    'cash' => 'Cash Voucher',
                                ])
                                ->default('atm')
                                ->required()
                                ->native(false),

                            TextInput::make('monthly_rate')
                                ->label('Basic Monthly Rate')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('₱')
                                ->placeholder('0.00')
                                ->helperText('Basis: 26 regular work days/month.')
                                ->live(onBlur: true)
                                ->visible(fn (Get $get): bool => $get('rate_type') === 'monthly')
                                ->afterStateUpdated(fn (Set $set, mixed $state): mixed => $set('daily_rate', filled($state) && (float) $state > 0
                                    ? round((float) $state / Employee::REGULAR_WORK_DAYS_PER_MONTH, 2)
                                    : null))
                                ->required(fn (Get $get): bool => $get('rate_type') === 'monthly'),

                            TextInput::make('daily_rate')
                                ->label('Daily Rate')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('₱')
                                ->placeholder('0.00')
                                ->helperText(fn (Get $get): string => $get('rate_type') === 'monthly' ? 'Auto-calculated: Monthly ÷ 26.' : 'Enter standard daily working rate.')
                                ->readOnly(fn (Get $get): bool => $get('rate_type') === 'monthly')
                                ->dehydrated(true)
                                ->required(fn (Get $get): bool => $get('rate_type') === 'daily'),

                            TextInput::make('allowance')
                                ->label('Monthly Allowance')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('₱')
                                ->placeholder('0.00')
                                ->default(0)
                                ->helperText('Standard non-taxable or regular allowance.')
                                ->dehydrateStateUsing(fn ($state) => $state ?? 0),
                        ])
                        ->columns([
                            'default' => 1,
                            'md' => 3,
                        ]),

                    // 4. Statutory & Government Identification
                    Section::make('Statutory & Government Identification')
                        ->description('Official government-mandated registration numbers.')
                        ->icon(Heroicon::Identification)
                        ->collapsible()
                        ->schema([
                            TextInput::make('sss')
                                ->label('SSS #')
                                ->placeholder('e.g., 00-0000000-0')
                                ->maxLength(20),

                            TextInput::make('philhealth')
                                ->label('PhilHealth ID #')
                                ->placeholder('e.g., 00-000000000-0')
                                ->maxLength(20),

                            TextInput::make('pagibig')
                                ->label('Pag-IBIG MID #')
                                ->placeholder('e.g., 0000-0000-0000')
                                ->maxLength(20),

                            TextInput::make('tin')
                                ->label('TIN #')
                                ->placeholder('e.g., 000-000-000-000')
                                ->maxLength(20),

                            TextInput::make('gsis')
                                ->label('GSIS BP #')
                                ->placeholder('e.g., 0000000000')
                                ->maxLength(20),

                            TextInput::make('bank_id_no')
                                ->label('Payroll Bank Account / ID #')
                                ->placeholder('e.g., 1234-5678-90')
                                ->maxLength(191),
                        ])
                        ->columns([
                            'default' => 1,
                            'md' => 2,
                            'lg' => 3,
                        ]),
                ])
                ->relationship('employee'),

                // 5. Account Access & Credentials
                Section::make('Account Credentials')
                    ->description('Unified self-service portal access for the employee.')
                    ->icon(Heroicon::Key)
                    ->schema([
                        TextInput::make('username')
                            ->label('Portal Username')
                            ->placeholder(fn (Get $get): string => User::companyUsernameFromUid($get('employee.uid')) ?? 'e.g., PF0001')
                            ->helperText('Automatically created from the Employee ID (e.g. PF0001).')
                            ->readOnly()
                            ->dehydrated(false)
                            ->prefixIcon(Heroicon::UserCircle),

                        TextInput::make('password')
                            ->label('Initial Password')
                            ->password()
                            ->revealable()
                            ->default(fn (string $context): ?string => $context === 'create' ? 'PASSWORD1.' : null)
                            ->readOnly(fn (string $context): bool => $context === 'create')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->helperText('Default initial password is PASSWORD1.')
                            ->prefixIcon(Heroicon::LockClosed),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
            ]);
    }
}
