<?php

namespace App\Filament\Kpi\Pages;

use App\Models\KpiAccount;
use App\Models\KpiRatingCycle;
use App\Services\KpiRatingRosterService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
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
use Override;

class KpiRatings extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $title = 'KPI';

    protected static ?string $navigationLabel = 'KPI';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBarSquare;

    protected static ?int $navigationSort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => KpiRatingCycle::query()
                ->where('kpi_account_id', auth('kpi')->id())
                ->withCount('targets')
                ->latest('rating_date')
                ->latest('id'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('rating_date')
                    ->label('Rating Date')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('KPI Rating')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('scope_type')
                    ->label('Scope')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => KpiAccount::scopeOptions()[$state] ?? ucfirst($state))
                    ->color('primary'),

                TextColumn::make('targets_count')
                    ->label('Rating Targets')
                    ->numeric(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'completed' ? 'success' : 'warning'),
            ])
            ->recordActions([
                Action::make('viewTargets')
                    ->label('View Rating Targets')
                    ->icon(Heroicon::Eye)
                    ->url(fn (KpiRatingCycle $record): string => KpiRatingTargets::getUrl([
                        'cycle' => $record->uuid,
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
            Action::make('newRating')
                ->label('New KPI Rating')
                ->icon(Heroicon::Plus)
                ->schema([
                    DatePicker::make('rating_date')
                        ->label('Date of KPI Rating')
                        ->default(now())
                        ->native(false)
                        ->required(),
                ])
                ->modalHeading('Create KPI Rating')
                ->modalSubmitActionLabel('Create Rating')
                ->action(function (array $data, KpiRatingRosterService $service): mixed {
                    /** @var KpiAccount $account */
                    $account = auth('kpi')->user();
                    $alreadyExists = $account->ratingCycles()
                        ->whereDate('rating_date', $data['rating_date'])
                        ->exists();

                    $cycle = $service->createCycle($account, $data['rating_date']);

                    Notification::make()
                        ->title($alreadyExists ? 'KPI rating already exists' : 'KPI rating created')
                        ->body($alreadyExists
                            ? 'The existing rating roster for this date was opened.'
                            : 'The assigned rating targets were captured for this cycle.')
                        ->color($alreadyExists ? 'warning' : 'success')
                        ->send();

                    return redirect()->to(KpiRatingTargets::getUrl([
                        'cycle' => $cycle->uuid,
                    ]));
                }),
        ];
    }
}
