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
            Action::make('importDtr')
                ->label('Import D.T.R')
                ->icon(Heroicon::ArrowUpTray)
                ->url(fn (): string => StationDtrImport::getUrl([
                    'branchId' => $this->branch?->publicKey(),
                    'periodId' => $this->period?->publicKey(),
                ])),

            Action::make('downloadDtr')
                ->label('Download D.T.R')
                ->icon(Heroicon::ArrowDownTray)
                ->action(function (BiometricDtrBinCodec $codec, DtrOvertimeTransferService $overtimeTransfer): ?StreamedResponse {
                    if (! $this->branch || ! $this->period) {
                        Notification::make()
                            ->title('Invalid Selection')
                            ->danger()
                            ->body('Please select a valid branch and payroll period.')
                            ->send();

                        return null;
                    }

                    $periodId = (int) $this->period->id;
                    $branchId = (int) $this->branch->id;

                    $query = EmployeeVisibleDtr::query()
                        ->with('employee:id,fingerprint_id,lastname,middlename,firstname')
                        ->where('payroll_period_id', $periodId)
                        ->where('branch_id', $branchId)
                        ->orderBy('fingerprint_id')
                        ->orderBy('date_in')
                        ->orderBy('time_in');

                    if (! (clone $query)->exists()) {
                        $fallbackQuery = Dtr::query()
                            ->with('employee:id,fingerprint_id,lastname,middlename,firstname')
                            ->where('payroll_period_id', $periodId)
                            ->where('branch_id', $branchId)
                            ->orderBy('fingerprint_id')
                            ->orderBy('date_in')
                            ->orderBy('time_in');

                        if ((clone $fallbackQuery)->exists()) {
                            $query = $fallbackQuery;
                        } else {
                            Notification::make()
                                ->title('No D.T.R Records Found')
                                ->warning()
                                ->body("There are no D.T.R records available to download for {$this->branch->branch_name} in {$this->period->title}. Please import D.T.R first.")
                                ->send();

                            return null;
                        }
                    }

                    $filename = 'station-dtr-'.str($this->branch->branch_name)->slug().'-'.now()->format('Ymd-His').'.bin';

                    return response()->streamDownload(function () use ($query, $codec, $overtimeTransfer): void {
                        $query->chunk(500, function ($records) use ($codec, $overtimeTransfer): void {
                            $names = Employee::query()
                                ->whereIn('fingerprint_id', $records->pluck('fingerprint_id')->filter()->unique()->values())
                                ->get(['fingerprint_id', 'lastname', 'middlename', 'firstname'])
                                ->mapWithKeys(fn (Employee $employee): array => [
                                    (string) $employee->fingerprint_id => $employee->full_name,
                                ]);

                            foreach ($records as $record) {
                                echo $codec->encodeRecord([
                                    'uid' => is_numeric($record->fingerprint_id)
                                        ? (int) $record->fingerprint_id
                                        : (string) $record->fingerprint_id,
                                    'name' => $record->employee?->full_name ?: $names->get((string) $record->fingerprint_id, ''),
                                    'date_in' => (string) ($record->date_in ?? ''),
                                    'time_in' => (string) ($record->time_in ?? ''),
                                    'date_out' => (string) ($record->date_out ?? ''),
                                    'time_out' => (string) ($record->time_out ?? ''),
                                    'sched' => (string) ($record->schedule_type ?? ''),
                                    'sched_start' => (string) ($record->schedule_start ?? ''),
                                    'sched_end' => (string) ($record->schedule_end ?? ''),
                                    'session_id' => filled($record->source_session_id)
                                        ? (string) $record->source_session_id
                                        : 'hris-'.$record->getKey(),
                                    ...$overtimeTransfer->exportPayload($record),
                                ]);
                            }
                        });
                    }, $filename, [
                        'Content-Type' => 'application/octet-stream',
                        'X-Content-Type-Options' => 'nosniff',
                    ]);
                }),

            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(ManageStationDtr::getUrl()),
        ];
    }
}
