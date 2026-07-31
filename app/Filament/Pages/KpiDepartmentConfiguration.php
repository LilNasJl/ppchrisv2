<?php

namespace App\Filament\Pages;

use App\Models\Department;
use App\Models\KpiIndicator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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

class KpiDepartmentConfiguration extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'kpi-configuration/department';
    protected static ?string $title = 'Department KPI Configuration';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingOffice2;
    public ?int $departmentId = null;

    public function mount(): void
    {
        $this->departmentId = Department::resolvePublicId(request()->query('department'));
        abort_unless($this->departmentId, 404);
    }

    public static function canAccess(): bool
    {
        return KpiConfiguration::canAccess();
    }

    public function getTitle(): string
    {
        return ($this->department()?->name ?: 'Department').' KPI Configuration';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => KpiIndicator::query()->where('department_id', $this->departmentId)->with('categories')->orderBy('name'))
            ->columns([
                TextColumn::make('index')->label('#')->rowIndex(),
                TextColumn::make('name')->label('Key Performance Indicator')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('category_breakdown')
                    ->label('Categories and Weights')
                    ->getStateUsing(fn (KpiIndicator $record): array => $record->categories
                        ->map(fn ($category): string => $category->name.' - '.number_format((float) $category->weight, 2).'%')
                        ->all())
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->placeholder('Not assigned'),
                TextColumn::make('total_weight')
                    ->label('Total Weight')
                    ->getStateUsing(fn (KpiIndicator $record): float => round((float) $record->categories->sum('weight'), 2))
                    ->formatStateUsing(fn (float $state): string => number_format($state, 2).'%')
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state === 0.0 => 'gray',
                        $state === 100.0 => 'success',
                        $state > 100 => 'danger',
                        default => 'warning',
                    })
                    ->alignCenter(),
                TextColumn::make('created_at')->label('Date Created')->date('M d, Y')->sortable(),
            ])
            ->emptyStateHeading('No KPI indicators configured')
            ->emptyStateDescription('Use Manage KPI to add indicators and assign their categories.')
            ->defaultPaginationPageOption(10);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('indicators')->label('Key Performance Indicators')->icon(Heroicon::ChartBar)
                    ->url(fn (): string => KpiIndicators::getUrl(['department' => $this->department()?->publicKey()])),
                Action::make('categories')->label('KPI Category')->icon(Heroicon::Tag)
                    ->url(fn (): string => KpiCategories::getUrl(['department' => $this->department()?->publicKey()])),
            ])->label('Manage KPI')->icon(Heroicon::Cog6Tooth)->button(),
            Action::make('return')->label('Return')->icon(Heroicon::ArrowLeft)->color('gray')->url(KpiConfiguration::getUrl()),
        ];
    }

    protected function department(): ?Department
    {
        return filled($this->departmentId) ? Department::query()->find($this->departmentId) : null;
    }
}
