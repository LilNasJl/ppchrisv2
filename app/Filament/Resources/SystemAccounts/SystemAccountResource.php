<?php

namespace App\Filament\Resources\SystemAccounts;

use App\Filament\Resources\SystemAccounts\Pages\CreateSystemAccount;
use App\Filament\Resources\SystemAccounts\Pages\EditSystemAccount;
use App\Filament\Resources\SystemAccounts\Pages\ListSystemAccounts;
use App\Filament\Resources\SystemAccounts\Pages\ViewSystemAccount;
use App\Models\SystemAccount;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class SystemAccountResource extends Resource
{
    public const HIDDEN_SYSTEM_USERNAMES = [
        'masteradmin',
    ];

    protected static ?string $model = SystemAccount::class;

    protected static ?string $recordRouteKeyName = 'uuid';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'System Accounts';

    protected static ?string $modelLabel = 'System Account';

    protected static ?string $pluralModelLabel = 'System Accounts';

    protected static ?string $recordTitleAttribute = 'username';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Details')
                    ->description('Accounts here are only for HR/admin access. Employee accounts stay in Employee Accounts.')
                    ->schema([
                        TextInput::make('username')
                            ->label('Username')
                            ->placeholder('e.g., jlladroma')
                            ->required()
                            ->maxLength(255)
                            ->rule('regex:/^\S+$/')
                            ->unique(
                                table: User::class,
                                column: 'username',
                                ignoreRecord: true,
                            )
                            ->validationMessages([
                                'regex' => 'The username must not contain spaces.',
                                'unique' => 'This username is already registered.',
                            ]),

                        TextInput::make('email')
                            ->label('Email')
                            ->placeholder('e.g., admin@example.com')
                            ->email()
                            ->required()
                            ->unique(
                                table: User::class,
                                column: 'email',
                                ignoreRecord: true,
                            )
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),

                        Select::make('role')
                            ->label('Account Role')
                            ->options([
                                'hr' => 'HR',
                                'admin' => 'Admin',
                            ])
                            ->default('hr')
                            ->required()
                            ->native(false),

                        Select::make('roles')
                            ->label('Shield Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('These Shield roles control the permissions for this HR/admin account.'),

                        Toggle::make('is_disabled')
                            ->label('Disable Account')
                            ->helperText('Disabled system accounts cannot log in until enabled again.')
                            ->default(false)
                            ->inline(false),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('username')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Account Role')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'admin' => 'Admin',
                        'hr' => 'HR',
                        default => $state ?: 'N/A',
                    })
                    ->color(fn (?string $state): string => $state === 'admin' ? 'primary' : 'success')
                    ->sortable(),

                TextColumn::make('shield_roles')
                    ->label('Shield Roles')
                    ->badge()
                    ->getStateUsing(fn (SystemAccount $record): string => $record->roles->pluck('name')->join(', ') ?: 'No Shield role'),

                IconColumn::make('is_disabled')
                    ->label('Disabled')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->icon(Heroicon::EllipsisHorizontal),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('role', ['hr', 'admin'])
            ->whereNotIn('username', self::HIDDEN_SYSTEM_USERNAMES)
            ->with('roles');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->whereIn('role', ['hr', 'admin'])
            ->whereNotIn('username', self::HIDDEN_SYSTEM_USERNAMES)
            ->with('roles');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSystemAccounts::route('/'),
            'create' => CreateSystemAccount::route('/create'),
            'view' => ViewSystemAccount::route('/{record}'),
            'edit' => EditSystemAccount::route('/{record}/edit'),
        ];
    }
}
