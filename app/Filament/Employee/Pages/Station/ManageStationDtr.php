<?php

namespace App\Filament\Employee\Pages\Station;

use App\Models\Branch as BranchModel;
use App\Models\PayrollPeriod;
use App\Services\StationManagementAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ManageStationDtr extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $slug = 'station/dtr';

    protected static ?string $title = 'Station D.T.R Management';

    protected static ?string $navigationLabel = 'Station D.T.R';

    protected static string|UnitEnum|null $navigationGroup = 'Station Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingStorefront;

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return StationManagementAccess::canAccessStationManagement(auth()->user());
    }

    public function mount(): void
    {
        if (! StationManagementAccess::canAccessStationManagement(auth()->user())) {
            abort(403, 'Unauthorized access to Station Management.');
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Assigned Station Branches')
            ->description('Choose a station branch, then select an open payroll period to manage employees and D.T.R records.')
            ->query(fn (): Builder => BranchModel::query()
                ->whereIn('id', $this->assignedBranchIds())
                ->withCount(['employees' => fn (Builder $query): Builder => $query->activeEmployment()])
                ->orderBy('branch_name'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('branch_name')
                    ->label('Station / Branch')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('employees_count')
                    ->label('Active Employees')
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewEmployees')
                        ->label('View Employees')
                        ->icon(Heroicon::Users)
                        ->modalHeading(fn (BranchModel $record): string => 'View '.$record->branch_name.' D.T.R')
                        ->modalDescription('Select an open payroll period to view this station branch\'s employees and D.T.R records.')
                        ->modalSubmitActionLabel('Continue')
                        ->schema([
                            Select::make('period_id')
                                ->label('D.T.R Period')
                                ->options(fn (): array => PayrollPeriod::query()
                                    ->where('is_locked', false)
                                    ->newestFirst()
                                    ->pluck('title', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->placeholder('Select an open payroll period')
                                ->required(),
                        ])
                        ->action(function (BranchModel $record, array $data): void {
                            $period = PayrollPeriod::query()
                                ->where('is_locked', false)
                                ->findOrFail((int) $data['period_id']);

                            $this->redirect(StationEmployees::getUrl([
                                'branchId' => $record->publicKey(),
                                'periodId' => $period->publicKey(),
                            ]));
                        }),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions'),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submitDtr')
                ->label('Submit Station D.T.R')
                ->icon(Heroicon::ArrowUpTray)
                ->url(StationSubmissions::getUrl()),

            Action::make('submitDtrProof')
                ->label('On Field DTR')
                ->icon(Heroicon::DocumentCheck)
                ->url(StationProofSubmissions::getUrl()),
        ];
    }

    protected function assignedBranchIds(): array
    {
        return StationManagementAccess::getManagedBranchIds(auth()->user());
    }
}
