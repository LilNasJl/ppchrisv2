<?php

namespace App\Filament\SicRc\Pages;

use App\Models\Branch;
use App\Models\PayrollPeriod;
use App\Models\SicRcAccount;
use App\Models\SicRcDtrImport;
use App\Services\SicRcDtrImportDeletionService;
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

class DtrImportHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'dtr-import-history';

    protected static ?string $title = 'D.T.R Import History';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clock;

    public ?int $branchId = null;

    public ?int $periodId = null;

    public ?Branch $branch = null;

    public ?PayrollPeriod $period = null;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function mount(): void
    {
        $this->branchId = Branch::resolvePublicId(request()->query('branchId'));
        $this->periodId = PayrollPeriod::resolvePublicId(request()->query('periodId'));

        $this->branch = $this->branchId ? Branch::query()->find($this->branchId) : null;
        $this->period = $this->periodId ? PayrollPeriod::query()->find($this->periodId) : null;

        if (! $this->branch || ! in_array($this->branch->id, $this->assignedBranchIds(), true)) {
            throw new HttpException(403, 'This branch is not attached to your SIC/RC account.');
        }

        if (! $this->period) {
            throw new HttpException(404, 'No payroll period was selected.');
        }
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
                ->with('account')
                ->where('branch_id', $this->branchId)
                ->where('payroll_period_id', $this->periodId)
                ->latest('imported_at')
                ->latest('id'))
            ->heading($this->period?->title ?: 'Selected Payroll Period')
            ->description('Imports for the selected branch and payroll period only.')
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

                TextColumn::make('account.username')
                    ->label('Imported By')
                    ->placeholder('-'),

                TextColumn::make('imported_at')
                    ->label('Imported At')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('deleteImport')
                        ->label('Delete Permanently')
                        ->icon(Heroicon::Trash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete D.T.R import permanently?')
                        ->modalDescription(fn (SicRcDtrImport $record): string => $record->status === SicRcDtrImport::STATUS_COMPLETED && $record->imported_rows > 0
                            ? "This permanently deletes the imported D.T.R data for batch {$record->batch_id} and its matching history records. This cannot be undone."
                            : 'This permanently deletes this import-history record. No D.T.R data was created by this attempt.')
                        ->modalSubmitActionLabel('Delete Permanently')
                        ->action(fn (SicRcDtrImport $record) => $this->deleteImport($record)),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions'),
            ])
            ->striped()
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('No imports yet')
            ->emptyStateDescription('Completed and failed import attempts for this branch and period will appear here.')
            ->emptyStateIcon(Heroicon::Clock);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return to Import D.T.R')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => DtrImportUpload::getUrl([
                    'branchId' => $this->branch?->publicKey(),
                    'periodId' => $this->period?->publicKey(),
                ], panel: 'sicrc')),
        ];
    }

    protected function deleteImport(SicRcDtrImport $record): void
    {
        abort_unless(
            $record->branch_id === $this->branchId
            && $record->payroll_period_id === $this->periodId,
            403,
        );

        $deleted = app(SicRcDtrImportDeletionService::class)->delete($record);

        Notification::make()
            ->title('D.T.R import deleted permanently')
            ->body("Deleted {$deleted['entries']} imported D.T.R entr".($deleted['entries'] === 1 ? 'y' : 'ies')." and {$deleted['histories']} history record".($deleted['histories'] === 1 ? '' : 's').'.')
            ->success()
            ->send();
    }

    protected function account(): ?SicRcAccount
    {
        $account = auth('sicrc')->user();

        return $account instanceof SicRcAccount ? $account : null;
    }

    /** @return array<int, int> */
    protected function assignedBranchIds(): array
    {
        return $this->account()?->assignedBranchIds() ?? [];
    }
}
