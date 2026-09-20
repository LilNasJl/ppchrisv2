<?php

namespace App\Filament\Employee\Pages\Station;

use App\Models\Branch;
use App\Models\DtrSubmission;
use App\Models\PayrollPeriod;
use App\Services\StationManagementAccess;
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
use Symfony\Component\HttpKernel\Exception\HttpException;

class StationSubmissions extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $slug = 'station/submissions';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Submit Station D.T.R';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowUpTray;

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
                    ->label('Station / Branch')
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
                ->url(ManageStationDtr::getUrl()),

            Action::make('submitDtr')
                ->label('Submit D.T.R File')
                ->icon(Heroicon::ArrowUpTray)
                ->modalHeading('Submit Station D.T.R File')
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
                        ->label('Station / Branch')
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
                        ->rows(3)
                        ->maxLength(2000),
                ])
                ->action(function (array $data): void {
                    $employee = auth()->user()?->employee;
                    abort_unless($employee, 403);

                    $filePath = (string) ($data['dtr_file'] ?? '');
                    $fileName = basename($filePath);
                    $fileSize = null;
                    $mimeType = null;

                    if ($filePath && Storage::disk('local')->exists($filePath)) {
                        $fileSize = Storage::disk('local')->size($filePath);
                        $mimeType = Storage::disk('local')->mimeType($filePath) ?: 'application/octet-stream';
                    }

                    DtrSubmission::query()->create([
                        'submitted_by_employee_id' => $employee->id,
                        'payroll_period_id' => (int) $data['payroll_period_id'],
                        'branch_id' => (int) $data['branch_id'],
                        'submission_type' => DtrSubmission::TYPE_DTR,
                        'file_path' => $filePath,
                        'file_name' => $fileName,
                        'mime_type' => $mimeType,
                        'file_size' => $fileSize,
                        'status' => DtrSubmission::STATUS_PENDING,
                        'comments' => $data['comments'] ?? null,
                    ]);

                    Notification::make()
                        ->title('D.T.R file submitted successfully')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function assignedBranchIds(): array
    {
        return StationManagementAccess::getManagedBranchIds(auth()->user());
    }
}
