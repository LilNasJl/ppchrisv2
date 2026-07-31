<?php

namespace App\Filament\Pages;

use App\Models\Department;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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

class KpiConfiguration extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'KPI Configuration';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::AdjustmentsHorizontal;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Department::query()
                ->withCount([
                    'employees as employees_count' => fn (Builder $query): Builder => $query->activeEmployment(),
                    'kpiIndicators',
                ])->orderBy('name'))
            ->columns([
                TextColumn::make('index')->label('#')->rowIndex(),
                TextColumn::make('name')->label('Department')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('acronym')->label('Acronym')->placeholder('-')->badge()->color('primary'),
                TextColumn::make('employees_count')->label('Employees')->numeric()->sortable(),
                TextColumn::make('kpi_indicators_count')->label('KPIs')->numeric()->sortable(),
            ])
            ->recordActions([
                Action::make('configure')->label('Configure')->icon(Heroicon::Cog6Tooth)
                    ->url(fn (Department $record): string => KpiDepartmentConfiguration::getUrl([
                        'department' => $record->publicKey(),
                    ])),
            ])
            ->emptyStateHeading('No departments available')
            ->emptyStateDescription('Add departments before configuring KPI indicators.')
            ->defaultPaginationPageOption(10);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [Action::make('return')->label('Return')->icon(Heroicon::ArrowLeft)->color('gray')->url(Kpi::getUrl())];
    }
}
