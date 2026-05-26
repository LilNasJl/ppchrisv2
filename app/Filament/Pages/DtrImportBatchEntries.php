<?php

namespace App\Filament\Pages;

use App\Models\Dtr as ModelsDtr;
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

class DtrImportBatchEntries extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'D.T.R Batch Entries';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::QueueList;

    public ?string $batchId = null;

    public function mount(): void
    {
        $this->batchId = request()->query('batchId');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(fn (): string => filled($this->batchId)
                ? "Batch {$this->batchId} D.T.R Entries"
                : 'Batch D.T.R Entries')
            ->query(fn (): Builder => ModelsDtr::query()
                ->with(['payrollPeriod', 'branch'])
                ->when(
                    filled($this->batchId),
                    fn (Builder $query) => $query->where('batch_id', $this->batchId),
                    fn (Builder $query) => $query->whereRaw('1 = 0')
                )
                ->orderBy('date_in')
                ->orderBy('time_in'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('fingerprint_id')
                    ->label('Fingerprint ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payroll_period_id')
                    ->label('Period ID')
                    ->sortable(),

                TextColumn::make('branch_id')
                    ->label('Branch ID')
                    ->sortable(),

                TextColumn::make('date_in')
                    ->label('Date In')
                    ->date()
                    ->sortable(),

                TextColumn::make('time_in')
                    ->label('Time In')
                    ->placeholder('-'),

                TextColumn::make('date_out')
                    ->label('Date Out')
                    ->date()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('time_out')
                    ->label('Time Out')
                    ->placeholder('-'),

                TextColumn::make('schedule_type')
                    ->label('Schedule Type')
                    ->badge()
                    ->placeholder('-')
                    ->color(fn (?string $state): string => match ($state) {
                        'Saturday' => 'info',
                        'Overtime' => 'warning',
                        'Forgot to Punch', 'Absent' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('schedule_start')
                    ->label('Schedule Start')
                    ->placeholder('-'),

                TextColumn::make('schedule_end')
                    ->label('Schedule End')
                    ->placeholder('-'),

                TextColumn::make('late')
                    ->label('Late')
                    ->numeric(),

                TextColumn::make('undertime')
                    ->label('Undertime')
                    ->numeric(),

                TextColumn::make('overtime')
                    ->label('Overtime')
                    ->numeric(),

                TextColumn::make('early_clock_in')
                    ->label('Early Clock In')
                    ->numeric(),

                TextColumn::make('overtime_status')
                    ->label('OT Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Approved' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Date Imported')
                    ->date()
                    ->sortable(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                ->url(DtrImport::getUrl()),
        ];
    }
}
