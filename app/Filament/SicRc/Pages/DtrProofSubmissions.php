<?php

namespace App\Filament\SicRc\Pages;

use App\Models\DtrSubmission;
use App\Models\PayrollPeriod;
use App\Models\SicRcAccount;
use App\Services\OnFieldDtrService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class DtrProofSubmissions extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'On Field DTR';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentCheck;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => DtrSubmission::query()
                ->with(['payrollPeriod', 'branch'])
                ->where('sic_rc_account_id', $this->account()?->id)
                ->where('submission_type', DtrSubmission::TYPE_PROOF)
                ->latest())
            ->heading('On Field DTR Requests')
            ->description('Submitted records remain pending until HR reviews them.')
            ->columns([
                TextColumn::make('index')->label('#')->rowIndex(),
                TextColumn::make('payrollPeriod.title')->label('Payroll Period')->searchable()->sortable()->wrap(),
                TextColumn::make('date_in')->label('Date In')->date('M d, Y')->sortable(),
                TextColumn::make('time_in')->label('Time In')->time('h:i A'),
                TextColumn::make('date_out')->label('Date Out')->date('M d, Y')->sortable(),
                TextColumn::make('time_out')->label('Time Out')->time('h:i A'),
                TextColumn::make('branch_name_snapshot')
                    ->label('Branch')
                    ->getStateUsing(fn (DtrSubmission $record): string => $record->submittedBranchName())
                    ->searchable()
                    ->wrap(),
                TextColumn::make('created_at')->label('Date Submitted')->dateTime('M d, Y h:i A')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        DtrSubmission::STATUS_APPROVED => 'success',
                        DtrSubmission::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('description')->label('Description')->placeholder('None')->limit(80)->wrap()->toggleable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->modalHeading('On Field DTR Details')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalWidth('4xl')
                        ->modalContent(fn (DtrSubmission $record) => view('filament.pages.partials.on-field-dtr-details', [
                            'submission' => $record,
                            'showReviewer' => true,
                        ])),
                    DeleteAction::make()
                        ->label('Delete')
                        ->visible(fn (DtrSubmission $record): bool => $record->isPending())
                        ->requiresConfirmation()
                        ->modalDescription('Only this pending request and its proof file will be deleted.')
                        ->before(function (DtrSubmission $record): void {
                            if ($record->file_path && Storage::disk('local')->exists($record->file_path)) {
                                Storage::disk('local')->delete($record->file_path);
                            }
                        }),
                ])->icon(Heroicon::EllipsisHorizontal)->tooltip('Actions'),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->poll('15s');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')->label('Return')->icon(Heroicon::ArrowLeft)->url(Branches::getUrl()),
            Action::make('submitDtr')
                ->label('Submit DTR')
                ->icon(Heroicon::ArrowUpTray)
                ->modalHeading('Submit On Field DTR')
                ->modalDescription(fn (): string => $this->identityDescription())
                ->modalSubmitActionLabel('Submit DTR')
                ->disabled(fn (): bool => ! $this->hasValidEmployeeBinding())
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

                    Section::make('DTR Entries')
                        ->schema([
                            DatePicker::make('date_in')->label('Date In')->required(),
                            TimePicker::make('time_in')->label('Time In')->required(),
                            DatePicker::make('date_out')->label('Date Out')->required(),
                            TimePicker::make('time_out')->label('Time Out')->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),

                    FileUpload::make('proof_file')
                        ->label('Proof File')
                        ->disk('local')
                        ->directory('dtr-proof-submissions')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg'])
                        ->maxSize(20480)
                        ->required()
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $account = $this->account();

                    if (! $account) {
                        Notification::make()->title('SIC/RC account not found')->danger()->send();

                        return;
                    }

                    app(OnFieldDtrService::class)->submit($account, $data);

                    Notification::make()
                        ->title('On Field DTR submitted')
                        ->body('The request is pending HR review. No official D.T.R entry was created yet.')
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

    protected function hasValidEmployeeBinding(): bool
    {
        return (bool) $this->account()?->employee()->whereNotNull('branch_id')->exists();
    }

    protected function identityDescription(): string
    {
        $employee = $this->account()?->employee?->loadMissing('branch');

        if (! $employee) {
            return 'This account has no bound employee. Ask HR to configure the employee binding first.';
        }

        return trim(sprintf(
            'Submitting for %s (%s), %s.',
            $employee->full_name,
            $employee->company_id ?? 'No employee ID',
            $employee->branch?->branch_name ?? 'No branch',
        ));
    }
}
