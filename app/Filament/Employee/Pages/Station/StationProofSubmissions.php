<?php

namespace App\Filament\Employee\Pages\Station;

use App\Models\DtrSubmission;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\OnFieldDtrService;
use App\Services\StationManagementAccess;
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
use Symfony\Component\HttpKernel\Exception\HttpException;

class StationProofSubmissions extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $slug = 'station/proof-submissions';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'On Field DTR';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentCheck;

    public static function canAccess(): bool
    {
        return StationManagementAccess::canAccessStationManagement(auth()->user());
    }

    public function mount(): void
    {
        if (! StationManagementAccess::canAccessStationManagement(auth()->user())) {
            throw new HttpException(403, 'Unauthorized access to Station Management.');
        }
    }

    public function table(Table $table): Table
    {
        $employeeId = auth()->user()?->employee?->id;

        return $table
            ->query(fn (): Builder => DtrSubmission::query()
                ->with(['payrollPeriod', 'branch'])
                ->where('submitted_by_employee_id', $employeeId)
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
                    ->label('Station / Branch')
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
            Action::make('return')->label('Return')->icon(Heroicon::ArrowLeft)->url(ManageStationDtr::getUrl()),
            Action::make('submitDtr')
                ->label('Submit On Field DTR')
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
                    $employee = $this->employee();

                    if (! $employee) {
                        Notification::make()->title('Employee record not found')->danger()->send();

                        return;
                    }

                    app(OnFieldDtrService::class)->submit($employee, $data);

                    Notification::make()
                        ->title('On Field DTR submitted')
                        ->body('The request is pending HR review. No official D.T.R entry was created yet.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function employee(): ?Employee
    {
        return auth()->user()?->employee;
    }

    protected function hasValidEmployeeBinding(): bool
    {
        $employee = $this->employee();

        return (bool) ($employee && ! $employee->trashed() && $employee->branch && ! $employee->branch->trashed());
    }

    protected function identityDescription(): string
    {
        $employee = $this->employee();

        if (! $employee) {
            return 'No bound employee record was found.';
        }

        $branchName = $employee->branch?->branch_name ?? 'No Branch';

        return "Submitting as: {$employee->full_name} ({$employee->company_id}) - {$branchName}";
    }
}
