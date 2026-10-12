<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\EmployeeLoanRequest;
use App\Services\EmployeeLoanRequestService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;

class EmployeeLoanRequestHistoryTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Loan Request History')
            ->description('Pending, approved, rejected, and cancelled loan requests.')
            ->query(fn (): Builder => EmployeeLoanRequest::query()
                ->with(['preferredStartPayrollPeriod', 'approvedLoan', 'reviewedBy'])
                ->where('employee_id', $this->employee()->id)
                ->latest('request_date')
                ->latest('id'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('request_date')
                    ->label('Date Requested')
                    ->icon('heroicon-m-calendar')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('loan_type')
                    ->label('Loan Type')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-banknotes')
                    ->weight('bold')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('loan_amount')
                    ->label('Requested Amount')
                    ->alignEnd()
                    ->weight('bold')
                    ->formatStateUsing(fn (mixed $state): string => '₱' . number_format((float) $state, 2)),

                TextColumn::make('loan_terms_months')
                    ->label('Terms')
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-m-clock')
                    ->getStateUsing(fn (EmployeeLoanRequest $record): string => $record->loan_terms_months.' '.$record->terms_label)
                    ->alignCenter(),

                TextColumn::make('schedule')
                    ->label('Schedule')
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        EmployeeLoanRequest::STATUS_APPROVED => 'success',
                        EmployeeLoanRequest::STATUS_REJECTED => 'danger',
                        EmployeeLoanRequest::STATUS_CANCELLED => 'gray',
                        default => 'warning',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        EmployeeLoanRequest::STATUS_APPROVED => 'heroicon-m-check-circle',
                        EmployeeLoanRequest::STATUS_REJECTED => 'heroicon-m-x-circle',
                        EmployeeLoanRequest::STATUS_CANCELLED => 'heroicon-m-no-symbol',
                        default => 'heroicon-m-clock',
                    })
                    ->sortable(),

                TextColumn::make('reviewed_at')
                    ->label('Reviewed At')
                    ->dateTime('M d, Y h:i A')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(EmployeeLoanRequest::statusOptions()),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::Eye)
                    ->color('primary')
                    ->modalHeading(fn (EmployeeLoanRequest $record): string => 'Loan Request - '.$record->loan_type)
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (EmployeeLoanRequest $record) => view('filament.pages.partials.loan-request-details', [
                        'request' => $record->load([
                            'preferredStartPayrollPeriod',
                            'approvedLoan.amortizationStartPayrollPeriod',
                            'reviewedBy',
                        ]),
                    ])),

                Action::make('cancel')
                    ->label('Cancel')
                    ->icon(Heroicon::XCircle)
                    ->color('danger')
                    ->visible(fn (EmployeeLoanRequest $record): bool => $record->status === EmployeeLoanRequest::STATUS_PENDING)
                    ->requiresConfirmation()
                    ->modalHeading('Cancel loan request?')
                    ->modalDescription('The request will remain in your history as cancelled.')
                    ->action(function (EmployeeLoanRequest $record): void {
                        app(EmployeeLoanRequestService::class)->cancel($record, $this->employee());

                        $this->resetTable();

                        Notification::make()
                            ->title('Loan request cancelled')
                            ->success()
                            ->send();
                    }),

                Action::make('delete')
                    ->label('Delete')
                    ->icon(Heroicon::Trash)
                    ->color('gray')
                    ->visible(fn (EmployeeLoanRequest $record): bool => in_array($record->status, [
                        EmployeeLoanRequest::STATUS_PENDING,
                        EmployeeLoanRequest::STATUS_REJECTED,
                    ], true))
                    ->requiresConfirmation()
                    ->modalHeading('Delete loan request?')
                    ->modalDescription('This permanently removes this pending or rejected request from your history.')
                    ->modalSubmitActionLabel('Delete Request')
                    ->action(function (EmployeeLoanRequest $record): void {
                        app(EmployeeLoanRequestService::class)->delete($record, $this->employee());

                        $this->resetTable();

                        Notification::make()
                            ->title('Loan request deleted')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No loan requests yet')
            ->emptyStateDescription('You have not filed any loan requests yet.')
            ->emptyStateIcon(Heroicon::Clock);
    }

    #[On('loan-request-created')]
    public function refreshRequests(): void
    {
        $this->resetTable();
    }

    public function render()
    {
        return view('livewire.employee-loan-request-history-table');
    }

    protected function employee(): Employee
    {
        return auth()->user()->employee()->firstOrFail();
    }
}
