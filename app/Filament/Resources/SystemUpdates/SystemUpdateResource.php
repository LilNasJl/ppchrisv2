<?php

namespace App\Filament\Resources\SystemUpdates;

use App\Filament\Resources\SystemUpdates\Pages\CreateSystemUpdate;
use App\Filament\Resources\SystemUpdates\Pages\EditSystemUpdate;
use App\Filament\Resources\SystemUpdates\Pages\ListSystemUpdates;
use App\Models\SystemUpdate;
use App\Services\SystemUpdateService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class SystemUpdateResource extends Resource
{
    protected static ?string $model = SystemUpdate::class;

    protected static ?string $recordRouteKeyName = 'uuid';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Sparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Updates & Notes';

    protected static ?string $modelLabel = 'Update / Note';

    protected static ?string $pluralModelLabel = 'Updates & Notes';

    protected static ?string $slug = 'settings/updates-notes';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Update & Note Details')
                    ->description('Select who sees this update or note on login and its display order in the popup.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->placeholder('e.g., Attendance & Payroll Workflow Updates')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('audience')
                            ->label('Target Audience')
                            ->options(SystemUpdate::audienceOptions())
                            ->default(SystemUpdate::AUDIENCE_ALL)
                            ->required()
                            ->native(false),

                        Select::make('category')
                            ->label('Primary Category')
                            ->options(SystemUpdate::categoryOptions())
                            ->default(SystemUpdate::CATEGORY_UPDATE)
                            ->required()
                            ->native(false),

                        TextInput::make('sort_order')
                            ->label('Display Order')
                            ->numeric()
                            ->integer()
                            ->default(0)
                            ->helperText('Lower numbers appear first after pinned items.'),

                        Toggle::make('is_pinned')
                            ->label('Pin to Top')
                            ->helperText('Pinned items stay highlighted at the top of the modal.')
                            ->default(false)
                            ->inline(false),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),

                Section::make('Summary, Bullet Items & Important Notes')
                    ->description('Add an overview, structured release items, and an optional highlighted reminder note.')
                    ->schema([
                        Textarea::make('summary')
                            ->label('Overview Summary')
                            ->rows(3)
                            ->placeholder('Brief explanation of what changed or what users should know...')
                            ->columnSpanFull(),

                        Repeater::make('items')
                            ->label('Detailed Updates / Notes List')
                            ->addActionLabel('Add Item')
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => filled($state['title'] ?? null)
                                ? '['.strtoupper((string) ($state['type'] ?? 'update')).'] '.$state['title']
                                : 'New Item')
                            ->schema([
                                Select::make('type')
                                    ->label('Tag')
                                    ->options([
                                        'update' => 'Update',
                                        'feature' => 'New Feature',
                                        'policy' => 'Policy / Rule',
                                        'fix' => 'Fix & Performance',
                                        'note' => 'Important Note',
                                    ])
                                    ->default('update')
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(1),

                                TextInput::make('title')
                                    ->label('Item Title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),

                                Textarea::make('description')
                                    ->label('Item Details')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(['default' => 1, 'md' => 3])
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Highlighted Note / Reminder (Optional)')
                            ->rows(3)
                            ->placeholder('Optional operational note or reminder shown in a highlighted callout box...')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('acknowledgments'))
            ->defaultSort('is_pinned', 'desc')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('title')
                    ->label('Title & Summary')
                    ->searchable(['title', 'summary', 'notes'])
                    ->sortable()
                    ->weight('bold')
                    ->wrap()
                    ->description(fn (SystemUpdate $record): ?string => filled($record->summary)
                        ? Str::limit((string) $record->summary, 95)
                        : null),

                TextColumn::make('audience')
                    ->label('Audience')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => SystemUpdate::audienceLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        SystemUpdate::AUDIENCE_HR => 'warning',
                        SystemUpdate::AUDIENCE_EMPLOYEE => 'info',
                        SystemUpdate::AUDIENCE_STATION_MANAGER => 'purple',
                        default => 'primary',
                    })
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => SystemUpdate::categoryLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        SystemUpdate::CATEGORY_POLICY, SystemUpdate::CATEGORY_NOTE => 'warning',
                        SystemUpdate::CATEGORY_FIX => 'success',
                        SystemUpdate::CATEGORY_FEATURE => 'info',
                        default => 'primary',
                    })
                    ->sortable(),

                IconColumn::make('is_pinned')
                    ->label('Pinned')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('acknowledgments_count')
                    ->label('Read By')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state): string => ((int) $state).' '.Str::plural('user', (int) $state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('audience')
                    ->options(SystemUpdate::audienceOptions()),
                SelectFilter::make('category')
                    ->options(SystemUpdate::categoryOptions()),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('preview')
                        ->label('Preview')
                        ->icon(Heroicon::Eye)
                        ->color('info')
                        ->modalHeading('Update & Note Preview')
                        ->modalWidth('2xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalContent(fn (SystemUpdate $record) => view('filament.components.system-updates-feed', [
                            'updates' => collect([$record]),
                        ])),

                    EditAction::make(),

                    Action::make('reannounce')
                        ->label('Re-announce to Users')
                        ->icon(Heroicon::ArrowPath)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Re-announce this Update?')
                        ->modalDescription('Clears existing read confirmations for this update so users see it again on their next login.')
                        ->visible(fn (): bool => auth()->user()?->can('Update:SystemUpdate') ?? false)
                        ->action(function (SystemUpdate $record): void {
                            app(SystemUpdateService::class)->resetAcknowledgments((int) $record->id);

                            Notification::make()
                                ->success()
                                ->title('Read confirmations cleared for this update')
                                ->send();
                        }),

                    DeleteAction::make(),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->color('gray'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSystemUpdates::route('/'),
            'create' => CreateSystemUpdate::route('/create'),
            'edit' => EditSystemUpdate::route('/{record}/edit'),
        ];
    }
}
