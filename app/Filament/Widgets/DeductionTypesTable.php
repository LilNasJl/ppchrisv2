<?php

namespace App\Filament\Widgets;

use App\Models\Deduction;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

abstract class DeductionTypesTable extends TableWidget
{
    use HasWidgetShield;

    protected int|string|array $columnSpan = 'full';

    abstract protected function category(): string;

    public function table(Table $table): Table
    {
        return $table
            ->heading(static::$heading)
            ->query(fn (): Builder => Deduction::query()
                ->where('category', $this->category())
                ->orderBy('title'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->placeholder('-'),

                TextColumn::make('term_type')
                    ->label('Term')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Deduction::termTypeOptions()[$state ?: Deduction::TERM_PERMANENT] ?? 'Permanent')
                    ->color(fn (?string $state): string => $state === Deduction::TERM_FIXED ? 'warning' : 'success'),

                TextColumn::make('term_periods')
                    ->label('Periods')
                    ->placeholder('Permanent'),
            ])
            ->headerActions([
                Action::make('createDeduction')
                    ->label('Add Deduction')
                    ->icon(Heroicon::Plus)
                    ->schema($this->deductionFormSchema())
                    ->modalHeading('Add Deduction')
                    ->modalSubmitActionLabel('Save')
                    ->action(fn (array $data): mixed => Deduction::create($this->normalizeDeductionData($data))),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make('editDeduction')
                        ->label('Edit')
                        ->schema($this->deductionFormSchema())
                        ->fillForm(fn (Deduction $record): array => [
                            'title' => $record->title,
                            'description' => $record->description,
                            'term_type' => $record->term_type ?: Deduction::TERM_PERMANENT,
                            'term_periods' => $record->term_periods,
                        ])
                        ->action(fn (Deduction $record, array $data): mixed => $record->update($this->normalizeDeductionData($data))),

                    DeleteAction::make('deleteDeduction')
                        ->label('Delete'),
                ])
                    ->icon(Heroicon::EllipsisHorizontal),
            ]);
    }

    protected function deductionFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label('Deduction Title')
                ->maxLength(100)
                ->required(),

            Select::make('term_type')
                ->label('Term Type')
                ->options(Deduction::termTypeOptions())
                ->default(Deduction::TERM_PERMANENT)
                ->live()
                ->required(),

            TextInput::make('term_periods')
                ->label('Number of Payroll Periods')
                ->numeric()
                ->minValue(1)
                ->visible(fn (Get $get): bool => $get('term_type') === Deduction::TERM_FIXED)
                ->required(fn (Get $get): bool => $get('term_type') === Deduction::TERM_FIXED),

            Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->maxLength(255)
                ->columnSpanFull(),
        ];
    }

    protected function normalizeDeductionData(array $data): array
    {
        $termType = $data['term_type'] ?? Deduction::TERM_PERMANENT;

        return [
            'title' => str($data['title'] ?? '')->upper()->squish()->toString(),
            'description' => $data['description'] ?? null,
            'category' => $this->category(),
            'term_type' => $termType,
            'term_periods' => $termType === Deduction::TERM_FIXED ? max(1, (int) ($data['term_periods'] ?? 1)) : null,
        ];
    }
}
