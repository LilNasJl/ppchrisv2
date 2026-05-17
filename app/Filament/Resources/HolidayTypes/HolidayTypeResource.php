<?php

namespace App\Filament\Resources\HolidayTypes;

use App\Filament\Resources\HolidayTypes\Pages\CreateHolidayType;
use App\Filament\Resources\HolidayTypes\Pages\EditHolidayType;
use App\Filament\Resources\HolidayTypes\Pages\ListHolidayTypes;
use App\Models\HolidayType;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HolidayTypeResource extends Resource
{
    protected static ?string $model = HolidayType::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'type';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Holiday Type')
                    ->schema([
                        TextInput::make('type')
                            ->label('Holiday Type')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('rate')
                            ->label('Holiday Rate (%)')
                            ->numeric()
                            ->minValue(0)
                            ->required(),

                        TextInput::make('description')
                            ->label('Description')
                            ->maxLength(255)
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

                TextColumn::make('type')
                    ->label('Holiday Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('rate')
                    ->label('Rate (%)')
                    ->sortable(),

                TextColumn::make('description')
                    ->wrap(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHolidayTypes::route('/'),
            'create' => CreateHolidayType::route('/create'),
            'edit' => EditHolidayType::route('/{record}/edit'),
        ];
    }
}
