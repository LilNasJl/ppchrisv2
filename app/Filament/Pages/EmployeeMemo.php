<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\Memo as MemoModel;
use App\Models\MemoType;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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
use Override;

class EmployeeMemo extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Employee Memo';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::DocumentText;

    public ?string $employeeId = null;

    public function mount(): void
    {
        $this->employeeId = request()->query('employeeId');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(fn (): string => $this->employee()?->full_name . ' Memo Records')
            ->query(fn (): Builder => MemoModel::query()
                ->with(['type', 'employee'])
                ->where('employee_id', $this->employeeId)
                ->latest('created_at'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('created_at')
                    ->label('Date Filed')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Memo Title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('type.title')
                    ->label('Memo Type')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('attachment_name')
                    ->label('Attached File')
                    ->placeholder('No file')
                    ->wrap(),
            ])
            ->headerActions([
                Action::make('addMemo')
                    ->label('Add Memo')
                    ->icon(Heroicon::Plus)
                    ->schema($this->memoFormSchema())
                    ->modalHeading('Add Memo')
                    ->modalSubmitActionLabel('Save Memo')
                    ->action(fn (array $data): mixed => $this->createMemo($data)),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->modalSubmitAction(false)
                        ->modalHeading('Memo Details')
                        ->modalContent(fn (MemoModel $record) => view('filament.pages.partials.memo-details', [
                            'memo' => $record,
                        ])),

                    EditAction::make('edit')
                        ->label('Update')
                        ->icon(Heroicon::PencilSquare)
                        ->schema($this->memoFormSchema())
                        ->modalHeading('Update Memo')
                        ->modalSubmitActionLabel('Update Memo')
                        ->using(fn (MemoModel $record, array $data): MemoModel => $this->updateMemo($record, $data)),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions'),
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
                ->url(Memo::getUrl()),
        ];
    }

    protected function memoFormSchema(): array
    {
        return [
            Section::make()
                ->schema([
                    TextInput::make('title')
                        ->label('Memo Title')
                        ->required()
                        ->maxLength(255),

                    Select::make('memo_type_id')
                        ->label('Memo Type')
                        ->options(fn (): array => MemoType::query()->orderBy('title')->pluck('title', 'id')->all())
                        ->searchable()
                        ->required(),

                    Textarea::make('description')
                        ->label('Memo Description')
                        ->rows(5)
                        ->columnSpanFull(),

                    FileUpload::make('attachment_path')
                        ->label('Attached File')
                        ->disk('local')
                        ->directory('memos')
                        ->previewable(false)
                        ->storeFileNamesIn('attachment_original_name')
                        ->acceptedFileTypes([
                            'image/png',
                            'image/jpeg',
                            'application/pdf',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->maxSize(2048)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    protected function createMemo(array $data): void
    {
        MemoModel::create([
            ...$data,
            'employee_id' => $this->employee()->id,
            'attachment_original_name' => $data['attachment_original_name'] ?? (filled($data['attachment_path'] ?? null)
                ? basename($data['attachment_path'])
                : null),
        ]);

        Notification::make()
            ->title('Memo added')
            ->success()
            ->send();
    }

    protected function updateMemo(MemoModel $record, array $data): MemoModel
    {
        $record->update([
            ...$data,
            'attachment_original_name' => $data['attachment_original_name'] ?? (filled($data['attachment_path'] ?? null)
                ? basename($data['attachment_path'])
                : null),
        ]);

        Notification::make()
            ->title('Memo updated')
            ->success()
            ->send();

        return $record->refresh();
    }

    protected function employee(): ?Employee
    {
        if (blank($this->employeeId)) {
            return null;
        }

        return Employee::query()->find($this->employeeId);
    }
}
