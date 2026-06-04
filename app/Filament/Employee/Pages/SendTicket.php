<?php

namespace App\Filament\Employee\Pages;

use App\Models\Employee;
use App\Models\Ticket;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

class SendTicket extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $slug = 'send-ticket';

    protected static ?string $title = 'Send Ticket';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?int $navigationSort = 7;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Ticket::query()
                ->where('employee_id', $this->employee()->id)
                ->latest('created_at'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('created_at')
                    ->label('Date Sent')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Ticket')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === Ticket::STATUS_DONE ? 'success' : 'warning')
                    ->sortable(),

                TextColumn::make('hr_comment')
                    ->label('HR Comment')
                    ->placeholder('No comment yet')
                    ->limit(70)
                    ->wrap(),

                TextColumn::make('employee_attachment_name')
                    ->label('Attached File')
                    ->placeholder('No file')
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::Eye)
                    ->modalHeading('Ticket Details')
                    ->modalSubmitAction(false)
                    ->modalContent(fn (Ticket $record) => view('filament.pages.partials.ticket-details', [
                        'ticket' => $record,
                    ])),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newTicket')
                ->label('New Ticket')
                ->icon(Heroicon::Plus)
                ->modalHeading('Send Ticket')
                ->modalSubmitActionLabel('Send Ticket')
                ->schema($this->ticketFormSchema())
                ->action(fn (array $data): mixed => $this->createTicket($data)),
        ];
    }

    protected function ticketFormSchema(): array
    {
        return [
            Section::make()
                ->schema([
                    TextInput::make('title')
                        ->label('Ticket Title')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull(),

                    FileUpload::make('employee_attachment_path')
                        ->label('Attached File')
                        ->disk('local')
                        ->directory('tickets/employee')
                        ->previewable(false)
                        ->storeFileNamesIn('employee_attachment_original_name')
                        ->acceptedFileTypes([
                            'image/png',
                            'image/jpeg',
                            'application/pdf',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->maxSize(2048)
                        ->fetchFileInformation(false)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    protected function ticketDetailsSchema(): array
    {
        return [
            Section::make()
                ->schema([
                    TextInput::make('date_sent')->label('Date Sent')->disabled()->dehydrated(false),
                    TextInput::make('status')->disabled()->dehydrated(false),
                    TextInput::make('title')->disabled()->dehydrated(false)->columnSpanFull(),
                    Textarea::make('description')->rows(5)->disabled()->dehydrated(false)->columnSpanFull(),
                    Textarea::make('hr_comment')->label('HR Comment')->rows(4)->disabled()->dehydrated(false)->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    protected function createTicket(array $data): void
    {
        $employee = $this->employee();
        $pendingTickets = Ticket::query()
            ->where('employee_id', $employee->id)
            ->where('status', Ticket::STATUS_PENDING)
            ->count();

        if ($pendingTickets >= 3) {
            Notification::make()
                ->title('Ticket limit reached')
                ->body('You already have 3 pending tickets. Please wait until HR marks one ticket as done before sending another request.')
                ->danger()
                ->send();

            return;
        }

        Ticket::create([
            'employee_id' => $employee->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'employee_attachment_path' => $data['employee_attachment_path'] ?? null,
            'employee_attachment_original_name' => $data['employee_attachment_original_name'] ?? (filled($data['employee_attachment_path'] ?? null)
                ? basename($data['employee_attachment_path'])
                : null),
            'status' => Ticket::STATUS_PENDING,
        ]);

        Notification::make()
            ->title('Ticket sent')
            ->success()
            ->send();
    }

    protected function employee(): Employee
    {
        return auth()->user()->employee()->firstOrFail();
    }
}
