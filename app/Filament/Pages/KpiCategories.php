<?php

namespace App\Filament\Pages;

use App\Models\Department;
use App\Models\KpiCategory;
use App\Models\KpiIndicator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Override;

class KpiCategories extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'kpi-configuration/categories';
    protected static ?string $title = 'KPI Categories';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Tag;
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
        return 'KPI Categories - '.($this->department()?->name ?: 'Department');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => KpiCategory::query()
                ->whereHas('indicator', fn (Builder $query): Builder => $query->where('department_id', $this->departmentId))
                ->with('indicator')
                ->orderBy(
                    KpiIndicator::query()
                        ->select('name')
                        ->whereColumn('kpi_indicators.id', 'kpi_categories.kpi_indicator_id'),
                )
                ->orderBy('name'))
            ->columns([
                TextColumn::make('index')->label('#')->rowIndex(),
                TextColumn::make('indicator.name')->label('KPI')->searchable()->sortable()->weight('semibold')->wrap(),
                TextColumn::make('name')->label('Category')->searchable()->sortable()->badge()->color('primary'),
                TextColumn::make('weight')->label('Weight')->numeric(decimalPlaces: 2)->suffix('%')->sortable(),
                TextColumn::make('created_at')->label('Created At')->date('M d, Y')->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('edit')->label('Edit')->icon(Heroicon::PencilSquare)
                        ->schema(fn (KpiCategory $record): array => $this->categoryForm($record))
                        ->fillForm(fn (KpiCategory $record): array => [
                            'kpi_indicator_id' => $record->kpi_indicator_id,
                            'name' => $record->name,
                            'weight' => $record->weight,
                        ])
                        ->modalHeading('Edit KPI Category')->modalSubmitActionLabel('Save Changes')
                        ->action(fn (KpiCategory $record, array $data): KpiCategory => $this->saveCategory($data, $record)),
                    DeleteAction::make()->modalHeading('Delete KPI category')
                        ->modalDescription('This permanently deletes the selected category.'),
                ])->icon(Heroicon::EllipsisHorizontal)->tooltip('Actions'),
            ])
            ->emptyStateHeading('No KPI categories added')
            ->emptyStateDescription('Add KPI indicators first, then assign one category and weight to each.')
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
            Action::make('addCategory')->label('Add Category')->icon(Heroicon::Plus)
                ->schema(fn (): array => $this->categoryForm())->modalHeading('Add KPI Category')
                ->modalSubmitActionLabel('Add Category')->action(fn (array $data): KpiCategory => $this->saveCategory($data)),
            Action::make('return')->label('Return')->icon(Heroicon::ArrowLeft)->color('gray')
                ->url(fn (): string => KpiDepartmentConfiguration::getUrl(['department' => $this->department()?->publicKey()])),
        ];
    }

    protected function categoryForm(?KpiCategory $category = null): array
    {
        return [
            Select::make('kpi_indicator_id')->label('Key Performance Indicator')
                ->options($this->availableIndicatorOptions())
                ->searchable()
                ->preload()
                ->native(false)
                ->live()
                ->required(),
            TextInput::make('name')->label('KPI Category')->placeholder('Enter KPI category')->required()->maxLength(191),
            TextInput::make('weight')
                ->label('Weight')
                ->numeric()
                ->minValue(0.01)
                ->maxValue(100)
                ->step(0.01)
                ->suffix('%')
                ->live(debounce: 300)
                ->required(),
            Placeholder::make('weight_warning')
                ->label('Weight Limit Warning')
                ->content(fn (Get $get): string => $this->weightWarningMessage($get, $category))
                ->color('danger')
                ->icon(Heroicon::ExclamationTriangle)
                ->visible(fn (Get $get): bool => $this->weightWouldExceedLimit($get, $category))
                ->columnSpanFull(),
        ];
    }

    protected function saveCategory(array $data, ?KpiCategory $category = null): KpiCategory
    {
        $indicatorId = (int) ($data['kpi_indicator_id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        $weight = round((float) ($data['weight'] ?? 0), 2);
        $indicator = KpiIndicator::query()->where('department_id', $this->departmentId)->find($indicatorId);

        if (! $indicator) {
            throw ValidationException::withMessages(['kpi_indicator_id' => 'Select a KPI from the current department.']);
        }

        $duplicate = KpiCategory::query()
            ->where('kpi_indicator_id', $indicatorId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->when($category, fn (Builder $query): Builder => $query->whereKeyNot($category->getKey()))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'name' => 'This category already exists under the selected KPI.',
            ]);
        }
        if ($weight < 0.01 || $weight > 100) {
            throw ValidationException::withMessages(['weight' => 'The weight must be between 0.01 and 100.']);
        }

        $currentTotal = KpiCategory::query()
            ->where('kpi_indicator_id', $indicatorId)
            ->when($category, fn (Builder $query): Builder => $query->whereKeyNot($category->getKey()))
            ->sum('weight');

        if (round($currentTotal + $weight, 2) > 100) {
            throw ValidationException::withMessages([
                'weight' => 'The total category weight for this KPI cannot exceed 100%. '
                    .'Only '.number_format(max(0, 100 - $currentTotal), 2).'% remains.',
            ]);
        }

        if ($category) {
            abort_unless($category->indicator?->department_id === $this->departmentId, 404);
            $category->update(['kpi_indicator_id' => $indicatorId, 'name' => $name, 'weight' => $weight]);
        } else {
            $category = KpiCategory::query()->create(['kpi_indicator_id' => $indicatorId, 'name' => $name, 'weight' => $weight]);
        }

        Notification::make()->title($category->wasRecentlyCreated ? 'KPI category added' : 'KPI category updated')->success()->send();
        return $category;
    }

    protected function availableIndicatorOptions(): array
    {
        return KpiIndicator::query()
            ->where('department_id', $this->departmentId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    protected function weightWouldExceedLimit(Get $get, ?KpiCategory $category = null): bool
    {
        $indicatorId = (int) $get('kpi_indicator_id');
        $weight = round((float) $get('weight'), 2);

        if ($indicatorId < 1 || $weight < 0.01) {
            return false;
        }

        return round($this->currentCategoryWeight($indicatorId, $category) + $weight, 2) > 100;
    }

    protected function weightWarningMessage(Get $get, ?KpiCategory $category = null): string
    {
        $indicatorId = (int) $get('kpi_indicator_id');
        $weight = round((float) $get('weight'), 2);
        $currentTotal = $this->currentCategoryWeight($indicatorId, $category);
        $remaining = max(0, 100 - $currentTotal);

        return "The entered weight would make this KPI's category total exceed 100%. "
            .'Current total: '.number_format($currentTotal, 2).'%. '
            .'Entered weight: '.number_format($weight, 2).'%. '
            .'Remaining available weight: '.number_format($remaining, 2).'%. ';
    }

    protected function currentCategoryWeight(int $indicatorId, ?KpiCategory $category = null): float
    {
        return round((float) KpiCategory::query()
            ->where('kpi_indicator_id', $indicatorId)
            ->when($category, fn (Builder $query): Builder => $query->whereKeyNot($category->getKey()))
            ->sum('weight'), 2);
    }

    protected function department(): ?Department
    {
        return filled($this->departmentId) ? Department::query()->find($this->departmentId) : null;
    }
}
