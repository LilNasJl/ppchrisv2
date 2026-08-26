<?php

namespace App\Filament\SicRc\Pages;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\SicRcAccount;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BranchEmployees extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Branch Employees';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    public ?int $branchId = null;

    public ?Branch $branch = null;

    public ?int $periodId = null;

    public ?PayrollPeriod $period = null;

    public function mount(): void
    {
        $this->branchId = Branch::resolvePublicId(request()->query('branchId'));
        $this->periodId = PayrollPeriod::resolvePublicId(request()->query('periodId'));
        $this->branch = $this->branchId ? Branch::query()->find($this->branchId) : null;
        $this->period = $this->periodId ? PayrollPeriod::query()->find($this->periodId) : null;

        if (! $this->branch || ! in_array($this->branch->id, $this->assignedBranchIds(), true)) {
            throw new HttpException(403, 'This branch is not attached to your SIC/RC account.');
        }

        if (! $this->period) {
            throw new HttpException(404, 'No payroll period was selected.');
        }
    }

    public function getTitle(): string
    {
        return ($this->branch?->branch_name ?: 'Branch').' - '.($this->period?->title ?: 'D.T.R');
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Employee::query()
                ->with(['user', 'designation', 'department', 'branch'])
                ->activeEmployment()
                ->where('branch_id', $this->branchId)
                ->whereHas('user', fn (Builder $query): Builder => $query->where('role', 'employee'))
                ->orderBy('lastname')
                ->orderBy('firstname'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                ImageColumn::make('profile_photo')
                    ->label('Profile')
                    ->getStateUsing(fn (Employee $record): ?string => $record->user?->profile_photo_url)
                    ->defaultImageUrl(fn (): string => asset('image/default-profile.png'))
                    ->circular(),

                TextColumn::make('uid')
                    ->label('ID No.')
                    ->formatStateUsing(fn (Employee $record): string => $record->company_id ?? 'N/A')
                    ->badge()
                    ->searchable(),

                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['lastname', 'middlename', 'firstname'])
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('lastname', $direction)
                        ->orderBy('middlename', $direction)
                        ->orderBy('firstname', $direction)),

                TextColumn::make('designation.title')
                    ->label('Designation')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable()
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('manageDtr')
                    ->label('Manage D.T.R')
                    ->icon(Heroicon::Cog6Tooth)
                    ->url(fn (Employee $record): string => ManageDtr::getUrl([
                        'branchId' => $record->branch?->publicKey(),
                        'employeeId' => $record->publicKey(),
                        'periodId' => $this->period?->publicKey(),
                    ]))
                    ->disabled(fn (): bool => blank($this->period))
                    ->tooltip(fn (): ?string => blank($this->period) ? 'No payroll period is selected.' : null),
            ])
            ->striped()
            ->paginationPageOptions([10, 25, 50])
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
            Action::make('importDtr')
                ->label('Import D.T.R')
                ->icon(Heroicon::ArrowUpTray)
                ->url(fn (): string => DtrImportUpload::getUrl([
                    'branchId' => $this->branch?->publicKey(),
                    'periodId' => $this->period?->publicKey(),
                ])),

            Action::make('downloadDtr')
                ->label('Download D.T.R')
                ->icon(Heroicon::ArrowDownTray)
                ->url(fn (): string => route('sicrc_tools.export.dtr_preview', [
                    'period_id' => $this->period?->publicKey(),
                    'branch_id' => $this->branch?->publicKey(),
                ]))
                ->openUrlInNewTab(),

            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(Branches::getUrl()),
        ];
    }

    protected function account(): ?SicRcAccount
    {
        $account = auth('sicrc')->user();

        return $account instanceof SicRcAccount ? $account : null;
    }

    protected function assignedBranchIds(): array
    {
        return $this->account()?->assignedBranchIds() ?? [];
    }
}
