<?php

namespace App\Filament\Employee\Pages\Station;

use App\Models\Branch;
use App\Models\PayrollPeriod;
use App\Models\SicRcDtrImport;
use App\Services\SicRcDtrImportDeletionService;
use App\Services\StationManagementAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StationDtrImportHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'station/dtr-import-history';

    protected static ?string $title = 'Station D.T.R Import History';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clock;

    public ?int $branchId = null;

    public ?int $periodId = null;

    public ?Branch $branch = null;

    public ?PayrollPeriod $period = null;

    public static function canAccess(): bool
    {
        return false;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function mount(): void
    {
        throw new HttpException(403, 'Station D.T.R Import History deletion is disabled to protect synchronized D.T.R records.');
    }

    public function getTitle(): string
    {
        return 'D.T.R Import History - '.($this->branch?->branch_name ?: 'Branch');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => SicRcDtrImport::query()
                ->with(['importedByEmployee.user'])
                ->where('branch_id', $this->branchId)
                ->where('payroll_period_id', $this->periodId)
                ->latest('imported_at')
                ->latest('id'))
            ->heading($this->period?->title ?: 'Selected Payroll Period')
            ->description('Imports for the selected station branch and payroll period.')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('import_name')
                    ->label('Import Name')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('batch_id')
                    ->label('Batch ID')
                    ->badge()
                    ->copyable()
                    ->searchable(),

                TextColumn::make('source_filename')
                    ->label('Source File')
                    ->placeholder('-')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('imported_rows')
                    ->label('Imported')
                    ->numeric(),

                TextColumn::make('skipped_rows')
                    ->label('Skipped')
                    ->numeric(),

                TextColumn::make('failed_rows')
                    ->label('Failed')
                    ->numeric(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        SicRcDtrImport::STATUS_COMPLETED => 'Completed',
                        SicRcDtrImport::STATUS_NO_CHANGES => 'No New Rows',
                        default => 'Failed',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        SicRcDtrImport::STATUS_COMPLETED => 'success',
                        SicRcDtrImport::STATUS_NO_CHANGES => 'gray',
                        default => 'danger',
                    }),

                TextColumn::make('importedByEmployee.full_name')
                    ->label('Imported By')
                    ->placeholder('-'),

                TextColumn::make('imported_at')
                    ->label('Imported At')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
            ])
            ->recordActions([])
            ->striped()
            ->defaultPaginationPageOption(10);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => StationEmployees::getUrl([
                    'branchId' => $this->branch?->publicKey(),
                    'periodId' => $this->period?->publicKey(),
                ])),
        ];
    }
}
