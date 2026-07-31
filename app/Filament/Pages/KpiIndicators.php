<?php

namespace App\Filament\Pages;

use App\Models\Department;
use App\Models\KpiIndicator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Override;

class KpiIndicators extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'kpi-configuration/indicators';
    protected static ?string $title = 'Key Performance Indicators';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;
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
        return 'KPIs - '.($this->department()?->name ?: 'Department');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => KpiIndicator::query()->where('department_id', $this->departmentId)->orderBy('name'))
            ->columns([
                TextColumn::make('index')->label('#')->rowIndex(),
                TextColumn::make('name')->label('KPI')->searchable()->sortable()->weight('semibold')->wrap(),
                TextColumn::make('created_at')->label('Date Created')->date('M d, Y')->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('edit')->label('Edit')->icon(Heroicon::PencilSquare)->schema($this->indicatorForm())
                        ->fillForm(fn (KpiIndicator $record): array => ['name' => $record->name])
                        ->modalHeading('Edit Key Performance Indicator')->modalSubmitActionLabel('Save Changes')
                        ->action(fn (KpiIndicator $record, array $data): KpiIndicator => $this->saveIndicator($data, $record)),
                    DeleteAction::make()->modalHeading('Delete KPI')
                        ->modalDescription('This permanently deletes the KPI and its assigned category.'),
                ])->icon(Heroicon::EllipsisHorizontal)->tooltip('Actions'),
            ])
            ->emptyStateHeading('No KPI indicators added')
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
            Action::make('addIndicator')->label('Add KPI')->icon(Heroicon::Plus)->schema($this->indicatorForm())
                ->modalHeading('Add Key Performance Indicator')->modalSubmitActionLabel('Add KPI')
                ->action(fn (array $data): KpiIndicator => $this->saveIndicator($data)),
            Action::make('return')->label('Return')->icon(Heroicon::ArrowLeft)->color('gray')
                ->url(fn (): string => KpiDepartmentConfiguration::getUrl(['department' => $this->department()?->publicKey()])),
        ];
    }

    protected function indicatorForm(): array
    {
        return [TextInput::make('name')->label('Key Performance Indicator')->placeholder('Enter key performance indicator')->required()->maxLength(191)];
    }

    protected function saveIndicator(array $data, ?KpiIndicator $indicator = null): KpiIndicator
    {
        $name = trim((string) ($data['name'] ?? ''));
        $duplicate = KpiIndicator::query()->where('department_id', $this->departmentId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->when($indicator, fn (Builder $query): Builder => $query->whereKeyNot($indicator->getKey()))->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'This KPI already exists for the selected department.']);
        }

        if ($indicator) {
            abort_unless($indicator->department_id === $this->departmentId, 404);
            $indicator->update(['name' => $name]);
        } else {
            $indicator = KpiIndicator::query()->create(['department_id' => $this->departmentId, 'name' => $name]);
        }

        Notification::make()->title($indicator->wasRecentlyCreated ? 'KPI added' : 'KPI updated')->success()->send();
        return $indicator;
    }

    protected function department(): ?Department
    {
        return filled($this->departmentId) ? Department::query()->find($this->departmentId) : null;
    }
}
