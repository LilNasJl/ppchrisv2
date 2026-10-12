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

    protected string $view = 'filament.employee.pages.send-ticket';

    protected static ?string $slug = 'send-ticket';

    protected static ?string $title = 'Send Ticket';

    protected static ?string $navigationLabel = 'Send Ticket';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Ticket;

    protected static string|\UnitEnum|null $navigationGroup = 'Reports & Updates';

    protected static ?int $navigationSort = 2;

    public function getSubheading(): ?string
    {
        return 'Submit inquiries, requests, or concerns to HR and track resolution status in real time.';
    }

    public function getTicketStats(): array
    {
        $employee = $this->employee();
        $total = Ticket::query()->where('employee_id', $employee->id)->count();
        $pending = Ticket::query()->where('employee_id', $employee->id)->where('status', Ticket::STATUS_PENDING)->count();
        $done = Ticket::query()->where('employee_id', $employee->id)->where('status', Ticket::STATUS_DONE)->count();
        $withComments = Ticket::query()
            ->where('employee_id', $employee->id)
            ->whereNotNull('hr_comment')
            ->where('hr_comment', '!=', '')
            ->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'done' => $done,
            'with_comments' => $withComments,
        ];
    }

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
                    ->icon('heroicon-m-calendar')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Ticket Subject')
                    ->icon('heroicon-m-document-text')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === Ticket::STATUS_DONE ? 'success' : 'warning')
                    ->icon(fn (string $state): string => $state === Ticket::STATUS_DONE ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                    ->sortable(),

                TextColumn::make('hr_comment')
                    ->label('HR Response')
                    ->icon(fn ($record) => filled($record->hr_comment) ? 'heroicon-m-chat-bubble-left-ellipsis' : null)
                    ->placeholder('Waiting for HR review')
                    ->limit(65)
                    ->wrap(),

                TextColumn::make('employee_attachment_name')
                    ->label('Attached File')
                    ->icon(fn ($record) => filled($record->employee_attachment_path) ? 'heroicon-m-paper-clip' : null)
                    ->placeholder('None')
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::Eye)
                    ->color('primary')
                    ->modalHeading('Ticket Details & Status')
                    ->modalSubmitAction(false)
                    ->modalContent(fn (Ticket $record) => view('filament.pages.partials.ticket-details', [
                        'ticket' => $record,
                    ])),
            ])
            ->emptyStateHeading('No tickets found')
            ->emptyStateDescription('You have not submitted any inquiries or tickets to HR yet. Click "New Ticket" to send an inquiry.')
            ->emptyStateIcon(Heroicon::Ticket);
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
                ->icon(Heroicon::PlusCircle)
                ->modalHeading('Submit HR Support Ticket')
                ->modalDescription('Send an inquiry, clarification, or document request directly to HR.')
                ->modalSubmitActionLabel('Submit Ticket')
                ->schema($this->ticketFormSchema())
                ->action(fn (array $data): mixed => $this->createTicket($data)),
        ];
    }

    protected function ticketFormSchema(): array
    {
        return [
            Section::make('Ticket Information')
                ->description('Provide the details of your inquiry or concern.')
                ->schema([
                    TextInput::make('title')
                        ->label('Ticket Subject')
                        ->placeholder('e.g., Attendance Discrepancy, COE Request, Payroll Clarification')
                        ->prefixIcon(Heroicon::DocumentText)
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Concern / Inquiries Description')
                        ->placeholder('Describe your concern or inquiry in detail so HR can assist you promptly...')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),

                    FileUpload::make('employee_attachment_path')
                        ->label('Supporting Attachment (Optional)')
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
                        ->helperText('Allowed formats: PDF, JPG, PNG, Excel (Max: 2MB).')
                        ->fetchFileInformation(false)
                        ->columnSpanFull(),
                ])
                ->columns(1),
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
                    Textarea::make('description')->rows(4)->disabled()->dehydrated(false)->columnSpanFull(),
                    Textarea::make('hr_comment')->label('HR Response')->rows(3)->disabled()->dehydrated(false)->columnSpanFull(),
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
                ->body('You already have 3 pending tickets. Please wait until HR resolves a ticket before submitting another.')
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
            ->title('Ticket submitted successfully')
            ->body('Your ticket has been sent to Human Resources.')
            ->success()
            ->send();
    }

    protected function employee(): Employee
    {
        return auth()->user()->employee()->firstOrFail();
    }
}
