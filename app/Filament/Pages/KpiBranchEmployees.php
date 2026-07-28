<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Employee;
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

class KpiBranchEmployees extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Branch Employees';

    public ?int $branchId = null;

    public function mount(): void
    {
        $this->branchId = (int) request()->query('branch');

        abort_unless($this->branch(), 404);
    }

    public function getTitle(): string
    {
        return ($this->branch()?->branch_name ?: 'Branch').' Employees';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Employee::query()
                ->activeEmployment()
                ->where('branch_id', $this->branchId)
                ->with(['department', 'designation'])
                ->orderBy('lastname')
                ->orderBy('middlename')
                ->orderBy('firstname'))
            ->columns([
                TextColumn::make('index')->label('#')->rowIndex(),
                TextColumn::make('company_id')->label('Employee ID')->searchable(),
                TextColumn::make('full_name')->label('Employee')->searchable(['lastname', 'middlename', 'firstname'])->wrap(),
                TextColumn::make('department.name')->label('Department')->placeholder('-')->wrap(),
                TextColumn::make('designation.title')->label('Designation')->placeholder('-')->wrap(),
            ])
            ->recordActions([
                Action::make('rate')
                    ->label('KPI Rate')
                    ->icon(Heroicon::ChartBarSquare)
                    ->modalHeading(fn (Employee $record): string => 'KPI Rating - '.$record->full_name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (Employee $record) => view('filament.pages.partials.kpi-rating-placeholder', [
                        'title' => $record->full_name,
                    ])),
            ]);
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
                ->url(Kpi::getUrl(['view' => 'branches'])),
        ];
    }

    protected function branch(): ?Branch
    {
        return Branch::query()->find($this->branchId);
    }
}
