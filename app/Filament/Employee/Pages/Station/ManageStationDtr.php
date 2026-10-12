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

    protected string $view = 'filament.employee.pages.station.manage-station-dtr';

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

    public function getSubheading(): string
    {
        return 'Select an assigned station branch to manage its employee attendance records.';
    }

    public function getStationDtrStats(): array
    {
        $branchIds = $this->assignedBranchIds();
        $branchesCount = count($branchIds);

        $activeEmployeesCount = \App\Models\Employee::query()
            ->whereIn('branch_id', $branchIds)
            ->where(function (Builder $query): Builder {
                return StationManagementAccess::scopeManagedEmployeesQuery(
                    $query->activeEmployment(),
                    auth()->user(),
                );
            })
            ->count();

        $openPeriodsCount = PayrollPeriod::query()
            ->where('is_locked', false)
            ->count();

        return [
            'branches_count' => $branchesCount,
            'active_employees_count' => $activeEmployeesCount,
            'open_periods_count' => $openPeriodsCount,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Assigned Station Branches')
            ->description('Choose a station branch, then select an open payroll period to manage employees and D.T.R records.')
            ->query(fn (): Builder => BranchModel::query()
                ->whereIn('id', $this->assignedBranchIds())
                ->withCount(['employees' => fn (Builder $query): Builder => StationManagementAccess::scopeManagedEmployeesQuery(
                    $query->activeEmployment(),
                    auth()->user(),
                )])
                ->orderBy('branch_name'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('branch_name')
                    ->label('Station / Branch')
                    ->icon('heroicon-m-building-storefront')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('employees_count')
                    ->label('Active Employees')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-users')
                    ->formatStateUsing(fn ($state) => $state . ' ' . \Illuminate\Support\Str::plural('staff', (int) $state)),
            ])
            ->recordActions([
                Action::make('viewEmployees')
                    ->label('Manage D.T.R')
                    ->icon(Heroicon::ArrowRight)
                    ->color('primary')
                    ->modalHeading(fn (BranchModel $record): string => 'Manage '.$record->branch_name.' Attendance')
                    ->modalDescription('Select an open payroll period to view this station branch\'s employees and D.T.R records.')
                    ->modalSubmitActionLabel('Continue to D.T.R')
                    ->schema([
                        Select::make('period_id')
                            ->label('Payroll / D.T.R Period')
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
            ->emptyStateHeading('No assigned station branches')
            ->emptyStateDescription('You do not currently have any station branches assigned to your account.')
            ->emptyStateIcon(Heroicon::BuildingStorefront);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    protected function assignedBranchIds(): array
    {
        return StationManagementAccess::getManagedBranchIds(auth()->user());
    }
}
