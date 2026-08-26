<?php

namespace App\Filament\SicRc\Pages;

use App\Models\Branch;
use App\Models\DtrSubmission;
use App\Models\PayrollPeriod;
use App\Models\SicRcAccount;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Illuminate\Support\Facades\Storage;

class DtrSubmissions extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Submit D.T.R';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowUpTray;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => DtrSubmission::query()
                ->with(['payrollPeriod', 'branch'])
                ->where('sic_rc_account_id', $this->account()?->id)
                ->where('submission_type', DtrSubmission::TYPE_DTR)
                ->latest())
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('payrollPeriod.title')
                    ->label('Payroll Period')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('branch.branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('file_name')
                    ->label('File')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Date Submitted')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('comments')
                    ->label('Comments')
                    ->placeholder('None')
                    ->limit(80)
                    ->wrap()
                    ->toggleable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    DeleteAction::make()
                        ->before(function (DtrSubmission $record): void {
                            if ($record->file_path && Storage::disk('local')->exists($record->file_path)) {
                                Storage::disk('local')->delete($record->file_path);
                            }
                        }),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions'),
            ])
            ->defaultPaginationPageOption(10);
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
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(Branches::getUrl()),

            Action::make('submitDtr')
                ->label('Submit D.T.R')
                ->icon(Heroicon::ArrowUpTray)
                ->modalHeading('Submit D.T.R File')
                ->modalSubmitActionLabel('Submit')
                ->schema([
                    Select::make('payroll_period_id')
                        ->label('Payroll Period')
                        ->options(fn (): array => PayrollPeriod::query()
                            ->where('is_locked', false)
                            ->newestFirst()
                            ->pluck('title', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('branch_id')
                        ->label('Branch')
                        ->options(fn (): array => Branch::query()
                            ->whereIn('id', $this->assignedBranchIds())
                            ->orderBy('branch_name')
                            ->pluck('branch_name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),

                    FileUpload::make('dtr_file')
                        ->label('D.T.R File')
                        ->disk('local')
                        ->directory('dtr-submissions')
                        ->visibility('private')
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'text/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/octet-stream',
                        ])
                        ->maxSize(51200)
                        ->required(),

                    Textarea::make('comments')
                        ->label('Comments')
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $path = (string) $data['dtr_file'];

                    DtrSubmission::query()->create([
                        'sic_rc_account_id' => $this->account()?->id,
                        'payroll_period_id' => $data['payroll_period_id'],
                        'branch_id' => $data['branch_id'],
                        'file_path' => $path,
                        'file_name' => basename($path),
                        'file_size' => Storage::disk('local')->exists($path) ? Storage::disk('local')->size($path) : 0,
                        'mime_type' => Storage::disk('local')->exists($path) ? Storage::disk('local')->mimeType($path) : null,
                        'file_hash' => Storage::disk('local')->exists($path) ? hash_file('sha256', Storage::disk('local')->path($path)) : null,
                        'comments' => $data['comments'] ?? null,
                        'is_new' => true,
                        'submission_type' => DtrSubmission::TYPE_DTR,
                    ]);

                    Notification::make()
                        ->title('D.T.R submitted')
                        ->body('The submitted file is now available in the HR D.T.R Submission inbox.')
                        ->success()
                        ->send();
                }),
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
