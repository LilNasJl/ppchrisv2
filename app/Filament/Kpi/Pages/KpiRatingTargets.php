<?php

namespace App\Filament\Kpi\Pages;

use App\Models\KpiRatingCycle;
use App\Models\KpiRatingTarget;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class KpiRatingTargets extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'KPI Rating Targets';

    public ?string $cycleKey = null;

    public function mount(): void
    {
        $this->cycleKey = (string) request()->query('cycle');

        abort_unless($this->cycle(), 404);
    }

    public function getTitle(): string
    {
        return $this->cycle()?->title ?: static::$title;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => KpiRatingTarget::query()
                ->whereHas('cycle', fn (Builder $query): Builder => $query
                    ->where('kpi_account_id', auth('kpi')->id())
                    ->where('uuid', $this->cycleKey))
                ->orderBy('target_name'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('target_name')
                    ->label(fn (): string => $this->cycle()?->scope_type === 'branch' ? 'Branch' : 'Employee')
                    ->searchable()
                    ->weight('semibold')
                    ->wrap(),

                TextColumn::make('branch_name')
                    ->label('Branch')
                    ->placeholder('-')
                    ->visible(fn (): bool => $this->cycle()?->scope_type !== 'branch')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('department_name')
                    ->label('Department')
                    ->placeholder('-')
                    ->visible(fn (): bool => $this->cycle()?->scope_type !== 'branch')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('designation_name')
                    ->label('Designation')
                    ->placeholder('-')
                    ->visible(fn (): bool => $this->cycle()?->scope_type !== 'branch')
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'rated' ? 'success' : 'warning'),
            ])
            ->recordActions([
                Action::make('rate')
                    ->label('Rate KPI')
                    ->icon(Heroicon::ChartBarSquare)
                    ->modalHeading(fn (KpiRatingTarget $record): string => 'KPI Rating - '.$record->target_name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (KpiRatingTarget $record) => view('filament.kpi.partials.rating-placeholder', [
                        'target' => $record,
                    ])),
            ])
            ->defaultPaginationPageOption(10);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(KpiRatings::getUrl()),
        ];
    }

    protected function cycle(): ?KpiRatingCycle
    {
        return KpiRatingCycle::query()
            ->where('kpi_account_id', auth('kpi')->id())
            ->where('uuid', $this->cycleKey)
            ->first();
    }
}
