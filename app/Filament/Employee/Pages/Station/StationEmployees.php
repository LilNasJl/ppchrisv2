<?php

namespace App\Filament\Employee\Pages\Station;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
use App\Services\Biometrics\BiometricDtrBinCodec;
use App\Services\DtrOvertimeTransferService;
use App\Services\StationManagementAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StationEmployees extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $slug = 'station/employees';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Station Employees';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    public ?int $branchId = null;

    public ?Branch $branch = null;

    public ?int $periodId = null;

    public ?PayrollPeriod $period = null;

    public static function canAccess(): bool
    {
        return StationManagementAccess::canAccessStationManagement(auth()->user());
    }

    public function mount(): void
    {
        if (! StationManagementAccess::canAccessStationManagement(auth()->user())) {
            throw new HttpException(403, 'Unauthorized access to Station Management.');
        }

        $this->branchId = Branch::resolvePublicId(request()->query('branchId'));
        $this->periodId = PayrollPeriod::resolvePublicId(request()->query('periodId'));
        $this->branch = $this->branchId ? Branch::query()->find($this->branchId) : null;
        $this->period = $this->periodId ? PayrollPeriod::query()->find($this->periodId) : null;

        if (! $this->branch || ! StationManagementAccess::canManageBranch(auth()->user(), $this->branch->id)) {
            throw new HttpException(403, 'This station branch is not assigned to your management profile.');
        }

        if (! $this->period) {
            throw new HttpException(404, 'No payroll period was selected.');
        }
    }

    public function getTitle(): string
    {
        return ($this->branch?->branch_name ?: 'Station').' - '.($this->period?->title ?: 'D.T.R');
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => StationManagementAccess::scopeManagedEmployeesQuery(
                Employee::query()
                    ->with(['user', 'designation', 'department', 'branch'])
                    ->activeEmployment()
                    ->where('branch_id', $this->branchId)
                    ->whereHas('user', fn (Builder $query): Builder => $query->where('role', 'employee')),
                auth()->user(),
            )
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
                    ->label('Company ID')
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
                    ->url(fn (Employee $record): string => ManageEmployeeDtr::getUrl([
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
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(ManageStationDtr::getUrl()),
        ];
    }
}
