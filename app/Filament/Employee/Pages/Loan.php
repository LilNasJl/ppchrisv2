<?php

namespace App\Filament\Employee\Pages;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Loan extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $slug = 'loan';

    protected static ?string $title = 'Loan';

    protected static ?string $navigationLabel = 'Loan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'My Workspace';

    protected static ?int $navigationSort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->heading('My Loans')
            ->description('Loan details, current balance, status, and payment history.')
            ->query(fn (): Builder => EmployeeLoan::query()
                ->with(['amortizationStartPayrollPeriod', 'payments.payrollPeriod'])
                ->where('employee_id', $this->employee()->id)
                ->latest('loan_date')
                ->latest('id'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('loan_type')
                    ->label('Loan Type')
                    ->badge()
                    ->searchable(),

                TextColumn::make('loan_date')
                    ->label('Loan Date')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total Loan')
                    ->alignEnd()
                    ->getStateUsing(fn (EmployeeLoan $record): float => $record->total_amount)
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),

                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->alignEnd()
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),

                TextColumn::make('balance_amount')
                    ->label('Balance')
                    ->alignEnd()
                    ->getStateUsing(fn (EmployeeLoan $record): float => $record->balance_amount)
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),

                TextColumn::make('payment_amount')
                    ->label('Payment')
                    ->alignEnd()
                    ->getStateUsing(fn (EmployeeLoan $record): float => $record->payment_amount)
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2)),

                TextColumn::make('schedule')
                    ->label('Schedule')
                    ->wrap(),

                TextColumn::make('amortizationStartPayrollPeriod.title')
                    ->label('Start Period')
                    ->placeholder('-')
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        EmployeeLoan::STATUS_ACTIVE => 'success',
                        EmployeeLoan::STATUS_PAID => 'info',
                        EmployeeLoan::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('history')
                    ->label('History')
                    ->icon(Heroicon::Clock)
                    ->modalHeading(fn (EmployeeLoan $record): string => 'Loan History - '.$record->loan_type)
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (EmployeeLoan $record) => view('filament.pages.partials.loan-ledger', [
                        'loan' => $record,
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

    protected function employee(): Employee
    {
        return auth()->user()->employee()->firstOrFail();
    }
}
