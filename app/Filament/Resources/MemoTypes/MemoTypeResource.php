<?php

namespace App\Filament\Resources\MemoTypes;

use App\Filament\Resources\MemoTypes\Pages\CreateMemoType;
use App\Filament\Resources\MemoTypes\Pages\EditMemoType;
use App\Filament\Resources\MemoTypes\Pages\ListMemoTypes;
use App\Models\MemoType;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MemoTypeResource extends Resource
{
    protected static ?string $model = MemoType::class;

    protected static ?string $recordRouteKeyName = 'uuid';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Memo Type')
                    ->schema([
                        TextInput::make('title')
                            ->label('Memo Title Type')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Textarea::make('description')
                            ->label('Memo Type Description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('title')
                    ->label('Memo Title Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->limit(100),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMemoTypes::route('/'),
            'create' => CreateMemoType::route('/create'),
            'edit' => EditMemoType::route('/{record}/edit'),
        ];
    }
}
