<?php

namespace App\Filament\Pages;

use App\Models\ActionHistory;
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

class History extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $title = 'History';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Bell;

    protected static string|UnitEnum|null $navigationGroup = 'Analytic and Reporting';

    protected static ?int $navigationSort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ActionHistory::query()
                ->whereIn('actor_role', ['admin', 'hr'])
                ->latest('created_at'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('actor_name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('action')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Str::headline((string) $state))
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted',
                        'permanently deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('model_label')
                    ->label('Record Type')
                    ->formatStateUsing(fn (?string $state): string => Str::headline((string) $state))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('record_label')
                    ->label('Record')
                    ->placeholder('Record')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('summary')
                    ->wrap()
                    ->limit(80),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::Eye)
                    ->modalHeading('History Details')
                    ->modalSubmitAction(false)
                    ->schema($this->historyDetailsSchema())
                    ->fillForm(fn (ActionHistory $record): array => [
                        'date' => optional($record->created_at)->format('M d, Y h:i A'),
                        'user' => $record->actor_name,
                        'action' => Str::headline($record->action),
                        'record_type' => Str::headline((string) $record->model_label),
                        'record' => $record->record_label ?: 'Record',
                        'summary' => $record->summary,
                        'before_data' => $this->formatHistoryData($record->before_data),
                        'after_data' => $this->formatHistoryData($record->after_data),
                        'changed_data' => $this->formatHistoryData($record->changed_data),
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

    protected function historyDetailsSchema(): array
    {
        return [
            Section::make('Action')
                ->schema([
                    TextInput::make('date')->disabled()->dehydrated(false),
                    TextInput::make('user')->disabled()->dehydrated(false),
                    TextInput::make('action')->disabled()->dehydrated(false),
                    TextInput::make('record_type')->label('Record Type')->disabled()->dehydrated(false),
                    TextInput::make('record')->disabled()->dehydrated(false),
                    TextInput::make('summary')->disabled()->dehydrated(false)->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('History Data')
                ->schema([
                    Textarea::make('changed_data')
                        ->label('Changed Data')
                        ->rows(8)
                        ->disabled()
                        ->dehydrated(false),

                    Textarea::make('before_data')
                        ->label('Before')
                        ->rows(10)
                        ->disabled()
                        ->dehydrated(false),

                    Textarea::make('after_data')
                        ->label('After')
                        ->rows(10)
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(3),
        ];
    }

    protected function formatHistoryData(?array $data): string
    {
        if (blank($data)) {
            return 'No data';
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'No data';
    }
}
