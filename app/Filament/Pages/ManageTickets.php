<?php

namespace App\Filament\Pages;

use App\Models\Ticket;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
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
use UnitEnum;

class ManageTickets extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $title = 'Manage Tickets';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Reports and Documents';

    protected static ?int $navigationSort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Ticket::query()
                ->with(['employee.user', 'handledBy'])
                ->latest('created_at'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('employee.full_name')
                    ->label('From'),

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
                    ->placeholder('No comment')
                    ->limit(60)
                    ->wrap(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->modalHeading('Ticket Details')
                        ->modalSubmitAction(false)
                        ->schema($this->ticketDetailsSchema())
                        ->fillForm(fn (Ticket $record): array => $this->ticketDetailsData($record)),

                    Action::make('updateStatus')
                        ->label('Update Status')
                        ->icon(Heroicon::PencilSquare)
                        ->modalHeading('Update Ticket Status')
                        ->modalSubmitActionLabel('Save Status')
                        ->schema($this->ticketStatusSchema())
                        ->fillForm(fn (Ticket $record): array => [
                            'status' => $record->status,
                            'hr_comment' => $record->hr_comment,
                        ])
                        ->action(fn (Ticket $record, array $data): mixed => $this->updateTicketStatus($record, $data)),

                    DeleteAction::make(),
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

    protected function ticketStatusSchema(): array
    {
        return [
            Select::make('status')
                ->options([
                    Ticket::STATUS_PENDING => Ticket::STATUS_PENDING,
                    Ticket::STATUS_DONE => Ticket::STATUS_DONE,
                ])
                ->required(),

            Textarea::make('hr_comment')
                ->label('HR Comment')
                ->rows(4)
                ->columnSpanFull(),
        ];
    }

    protected function ticketDetailsSchema(): array
    {
        return [
            Section::make()
                ->schema([
                    TextInput::make('from')->disabled()->dehydrated(false),
                    TextInput::make('date_sent')->label('Date Sent')->disabled()->dehydrated(false),
                    TextInput::make('status')->disabled()->dehydrated(false),
                    TextInput::make('handled_by')->label('Handled By')->disabled()->dehydrated(false),
                    TextInput::make('title')->disabled()->dehydrated(false)->columnSpanFull(),
                    Textarea::make('description')->rows(5)->disabled()->dehydrated(false)->columnSpanFull(),
                    Textarea::make('hr_comment')->label('HR Comment')->rows(4)->disabled()->dehydrated(false)->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    protected function ticketDetailsData(Ticket $ticket): array
    {
        return [
            'from' => $ticket->employee?->full_name ?: 'Employee',
            'date_sent' => optional($ticket->created_at)->format('M d, Y h:i A'),
            'status' => $ticket->status,
            'handled_by' => $ticket->handledBy?->username ?: $ticket->handledBy?->name ?: 'Not handled yet',
            'title' => $ticket->title,
            'description' => $ticket->description,
            'hr_comment' => $ticket->hr_comment ?: 'No comment',
        ];
    }

    protected function updateTicketStatus(Ticket $ticket, array $data): void
    {
        $isDone = $data['status'] === Ticket::STATUS_DONE;

        $ticket->update([
            'status' => $data['status'],
            'hr_comment' => $data['hr_comment'] ?? null,
            'handled_by_user_id' => auth()->id(),
            'done_at' => $isDone ? now() : null,
        ]);

        Notification::make()
            ->title('Ticket status updated')
            ->success()
            ->send();
    }
}
