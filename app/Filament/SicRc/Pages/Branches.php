<?php

namespace App\Filament\SicRc\Pages;

use App\Models\Branch as BranchModel;
use App\Models\PayrollPeriod;
use App\Models\SicRcAccount;
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

class Branches extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.sicrc.pages.branches';

    protected static ?string $title = 'D.T.R Management';

    protected static ?string $navigationLabel = 'D.T.R Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingStorefront;

    protected static ?int $navigationSort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Assigned Branches')
            ->description('Choose a branch, then select the open payroll period you want to manage.')
            ->query(fn (): Builder => BranchModel::query()
                ->whereIn('id', $this->assignedBranchIds())
                ->withCount(['employees' => fn (Builder $query): Builder => $query->activeEmployment()])
                ->orderBy('branch_name'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('branch_name')
                    ->label('Branch / Station')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('employees_count')
                    ->label('Employees')
                    ->alignCenter(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewEmployees')
                        ->label('View Employees')
                        ->icon(Heroicon::Users)
                        ->modalHeading(fn (BranchModel $record): string => 'View '.$record->branch_name.' D.T.R')
                        ->modalDescription('Select an open payroll period to view this branch\'s employees and D.T.R records.')
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

                            $this->redirect(BranchEmployees::getUrl([
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
                ->label('Submit D.T.R')
                ->icon(Heroicon::ArrowUpTray)
                ->url(DtrSubmissions::getUrl()),

            Action::make('submitDtrProof')
                ->label('On Field DTR')
                ->icon(Heroicon::DocumentCheck)
                ->url(DtrProofSubmissions::getUrl()),
        ];
    }

    protected function account(): ?SicRcAccount
    {
        $account = auth('sicrc')->user();

        return $account instanceof SicRcAccount ? $account : null;
    }

    protected function assignedBranchIds(): array
    {
        return $this->account()?->assignedBranchIds() ?? [];
    }
}
