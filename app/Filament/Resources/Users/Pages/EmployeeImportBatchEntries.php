<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Pages\EmployeeDetails;
use App\Filament\Resources\Users\UserResource;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class EmployeeImportBatchEntries extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = UserResource::class;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $title = 'Employee Import Batch';

    public string $batchId;

    public ?string $returnUrl = null;

    public function mount(string $batchId): void
    {
        $this->batchId = $batchId;
        $this->returnUrl = $this->normalizeReturnUrl(request()->query('returnUrl'));
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(fn (): string => "Batch {$this->batchId} Employees")
            ->query(fn (): Builder => Employee::query()
                ->withTrashed()
                ->with([
                    'user' => fn ($query) => $query->withTrashed(),
                    'branch',
                    'designation',
                    'department',
                ])
                ->where('employee_import_batch_id', $this->batchId)
                ->orderBy('uid'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('company_id')
                    ->label('ID No.')
                    ->badge()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('uid', 'like', '%'.preg_replace('/[^0-9]/', '', $search).'%')),

                TextColumn::make('full_name')
                    ->label('Employee Name')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('lastname', 'like', "%{$search}%")
                        ->orWhere('middlename', 'like', "%{$search}%")
                        ->orWhere('firstname', 'like', "%{$search}%"))
                    ->wrap(),

                TextColumn::make('user.username')
                    ->label('Username')
                    ->badge()
                    ->placeholder('-'),

                TextColumn::make('branch.branch_name')
                    ->label('Branch')
                    ->placeholder('-')
                    ->wrap(),

                TextColumn::make('designation.title')
                    ->label('Designation')
                    ->placeholder('-')
                    ->wrap(),

                TextColumn::make('department.name')
                    ->label('Department')
                    ->placeholder('-')
                    ->wrap(),

                TextColumn::make('rate_type')
                    ->label('Rate Type')
                    ->badge()
                    ->placeholder('-'),

                TextColumn::make('payment_type')
                    ->label('Payment Type')
                    ->badge()
                    ->placeholder('-'),

                TextColumn::make('employee_imported_at')
                    ->label('Date Imported')
                    ->date()
                    ->sortable(),

                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->date()
                    ->placeholder('-')
                    ->sortable(),
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
                ->url(fn (): string => $this->getReturnUrl()),
        ];
    }

    protected function getReturnUrl(): string
    {
        return $this->returnUrl ?: EmployeeDetails::getUrl();
    }

    protected function normalizeReturnUrl(mixed $url): ?string
    {
        if (! is_string($url) || blank($url)) {
            return null;
        }

        $appUrl = url('/');

        return str_starts_with($url, $appUrl) || str_starts_with($url, '/')
            ? $url
            : null;
    }
}
