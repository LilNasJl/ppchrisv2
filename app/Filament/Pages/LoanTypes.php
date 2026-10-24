<?php

namespace App\Filament\Pages;

use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRequest;
use App\Models\LoanType;
use App\Services\LoanAccessService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class LoanTypes extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.loan-types';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Loan Types';

    protected static ?string $slug = 'loan-types';

    public static function canAccess(): bool
    {
        return app(LoanAccessService::class)->canManage(auth()->user());
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addType')
                ->label('Add Loan Type')
                ->icon(Heroicon::Plus)
                ->schema([TextInput::make('name')->label('Loan Type')->required()->maxLength(191)
                    ->rule(Rule::unique('loan_types', 'name'))])
                ->action(function (array $data): void {
                    app(LoanAccessService::class)->authorizeManagement(auth()->user());
                    LoanType::create(['name' => trim($data['name'])]);
                }),
            Action::make('return')
                ->label('Return to Loans')
                ->icon(Heroicon::ArrowLeft)
                ->url(LoanManagement::getUrl()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table->query(LoanType::query()->orderBy('name'))
            ->columns([
                TextColumn::make('name')->label('Loan Type')->searchable(),
                TextColumn::make('created_at')->label('Added')->dateTime('M d, Y'),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon(Heroicon::PencilSquare)
                    ->disabled(fn (LoanType $record): bool => $this->isUsed($record))
                    ->schema([TextInput::make('name')->required()->maxLength(191)
                        ->rule(fn (LoanType $record) => Rule::unique('loan_types', 'name')->ignore($record->id))])
                    ->fillForm(fn (LoanType $record): array => ['name' => $record->name])
                    ->action(function (LoanType $record, array $data): void {
                        app(LoanAccessService::class)->authorizeManagement(auth()->user());
                        abort_if($this->isUsed($record), 403);
                        $record->update(['name' => trim($data['name'])]);
                    }),
                Action::make('delete')
                    ->label('Delete')
                    ->icon(Heroicon::Trash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->disabled(fn (LoanType $record): bool => $this->isUsed($record))
                    ->action(function (LoanType $record): void {
                        app(LoanAccessService::class)->authorizeManagement(auth()->user());
                        abort_if($this->isUsed($record), 403);
                        $record->delete();
                    }),
            ]);
    }

    protected function isUsed(LoanType $type): bool
    {
        return EmployeeLoan::withTrashed()->where('loan_type', $type->name)->exists()
            || EmployeeLoanRequest::query()->where('loan_type', $type->name)->exists();
    }
}
