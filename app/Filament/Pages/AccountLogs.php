<?php

namespace App\Filament\Pages;

use App\Models\AccountLog;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class AccountLogs extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $title = 'Account Logs';

    protected static ?string $navigationLabel = 'Account Logs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clock;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => AccountLog::query()
                ->with(['user.employee', 'actor.employee'])
                ->latest('occurred_at'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('occurred_at')
                    ->label('Date')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('event')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Str::headline((string) $state))
                    ->color(fn (?string $state): string => match ($state) {
                        'login' => 'success',
                        'logout' => 'gray',
                        'impersonation_started' => 'warning',
                        'impersonation_ended' => 'info',
                        default => 'primary',
                    })
                    ->sortable(),

                TextColumn::make('account_label')
                    ->label('Account')
                    ->searchable(['account_name', 'username'])
                    ->wrap(),

                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Str::headline((string) ($state ?: 'N/A')))
                    ->sortable(),

                TextColumn::make('panel')
                    ->label('Panel')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Str::headline((string) ($state ?: 'System')))
                    ->sortable(),

                TextColumn::make('actor_label')
                    ->label('By')
                    ->wrap(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::Eye)
                    ->modalHeading('Account Log Details')
                    ->modalSubmitAction(false)
                    ->schema($this->detailsSchema())
                    ->fillForm(fn (AccountLog $record): array => [
                        'date' => optional($record->occurred_at)->format('M d, Y h:i A'),
                        'action' => Str::headline($record->event),
                        'account' => $record->account_label,
                        'username' => $record->username,
                        'role' => Str::headline((string) ($record->role ?: 'N/A')),
                        'panel' => Str::headline((string) ($record->panel ?: 'System')),
                        'actor' => $record->actor_label,
                        'ip_address' => $record->ip_address,
                        'session_id' => $record->session_id,
                        'user_agent' => $record->user_agent,
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    protected function detailsSchema(): array
    {
        return [
            Section::make('Account')
                ->schema([
                    TextInput::make('date')->disabled()->dehydrated(false),
                    TextInput::make('action')->disabled()->dehydrated(false),
                    TextInput::make('account')->disabled()->dehydrated(false),
                    TextInput::make('username')->disabled()->dehydrated(false),
                    TextInput::make('role')->disabled()->dehydrated(false),
                    TextInput::make('panel')->disabled()->dehydrated(false),
                    TextInput::make('actor')->label('Performed By')->disabled()->dehydrated(false),
                    TextInput::make('ip_address')->label('IP Address')->disabled()->dehydrated(false),
                    TextInput::make('session_id')->disabled()->dehydrated(false)->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Device')
                ->schema([
                    Textarea::make('user_agent')
                        ->label('User Agent')
                        ->rows(4)
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),
        ];
    }
}
