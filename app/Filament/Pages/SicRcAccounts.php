<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\KpiAccount;
use App\Models\SicRcAccount;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Override;
use UnitEnum;

class SicRcAccounts extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static ?string $title = 'SIC/RC Accounts';

    protected static ?string $navigationLabel = 'SIC/RC Accounts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => SicRcAccount::query()->with(['employee.branch'])->orderBy('username'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('employee.full_name')
                    ->label('Employee Name')
                    ->getStateUsing(fn (SicRcAccount $record): string => $record->employee?->full_name ?? 'Not bound')
                    ->description(fn (SicRcAccount $record): ?string => $record->employee
                        ? trim(($record->employee->company_id ?? '').' | '.($record->employee->branch?->branch_name ?? 'No branch'), ' |')
                        : null)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'employee',
                        fn (Builder $employeeQuery): Builder => $employeeQuery->where(function (Builder $nameQuery) use ($search): void {
                            $nameQuery
                                ->where('lastname', 'like', "%{$search}%")
                                ->orWhere('firstname', 'like', "%{$search}%")
                                ->orWhere('uid', 'like', "%{$search}%");
                        }),
                    ))
                    ->wrap(),

                TextColumn::make('station_biometrics')
                    ->label('Branches / Stations')
                    ->getStateUsing(fn (SicRcAccount $record): string => $this->branchDisplayInline($record))
                    ->placeholder('Not configured')
                    ->badge()
                    ->color('gray')
                    ->wrap()
                    ->limit(110)
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Enabled')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->modalHeading(fn (SicRcAccount $record): string => 'SIC/RC Account - '.$record->username)
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->schema([
                            TextInput::make('username')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('employee_name')
                                ->label('Employee Name')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('employee_id_display')
                                ->label('Employee ID')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('employee_branch')
                                ->label('Employee Branch')
                                ->disabled()
                                ->dehydrated(false),

                            Textarea::make('branches_display')
                                ->label('Assigned Branches / Stations')
                                ->rows(5)
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('status')
                                ->disabled()
                                ->dehydrated(false),
                        ])
                        ->fillForm(fn (SicRcAccount $record): array => [
                            'username' => $record->username,
                            'employee_name' => $record->employee?->full_name ?? 'Not bound',
                            'employee_id_display' => $record->employee?->company_id ?? 'Not available',
                            'employee_branch' => $record->employee?->branch?->branch_name ?? 'Not available',
                            'branches_display' => $this->branchDisplayList($record),
                            'status' => $record->is_active ? 'Enabled' : 'Disabled',
                        ]),

                    Action::make('edit')
                        ->label('Edit')
                        ->icon(Heroicon::PencilSquare)
                        ->schema($this->accountFormSchema(false))
                        ->fillForm(fn (SicRcAccount $record): array => [
                            'username' => $record->username,
                            'employee_id' => $record->employee_id,
                            'branch_assignments' => $this->assignmentFormRows($record),
                            'is_active' => $record->is_active,
                        ])
                        ->modalHeading('Edit SIC/RC Account')
                        ->modalSubmitActionLabel('Save Changes')
                        ->action(fn (SicRcAccount $record, array $data): SicRcAccount => $this->saveAccount($data, $record)),

                    Action::make('delete')
                        ->label('Delete')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Permanently delete SIC/RC account')
                        ->modalDescription('This will permanently delete the SIC/RC account. This action cannot be undone.')
                        ->modalSubmitActionLabel('Delete permanently')
                        ->action(function (SicRcAccount $record): void {
                            $record->forceDelete();

                            Notification::make()
                                ->title('SIC/RC account deleted')
                                ->body('The account was permanently deleted.')
                                ->success()
                                ->send();
                        }),
                ])
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->tooltip('Actions'),
            ])
            ->defaultPaginationPageOption(10);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('createAccount')
                ->label('Create SIC/RC Account')
                ->icon(Heroicon::Plus)
                ->schema($this->accountFormSchema(true))
                ->modalHeading('Create SIC/RC Account')
                ->modalSubmitActionLabel('Create Account')
                ->action(fn (array $data): SicRcAccount => $this->saveAccount($data)),
        ];
    }

    protected function accountFormSchema(bool $creating): array
    {
        return [
            Section::make('Employee Identity')
                ->description('This employee is the authoritative identity for On Field DTR submissions made by this account.')
                ->schema([
                    Select::make('employee_id')
                        ->label('Employee Name')
                        ->options(fn (?SicRcAccount $record = null): array => $this->employeeOptions($record))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Each employee can only be bound to one SIC/RC account.'),
                ]),

            Section::make('Sign-in Details')
                ->description('SIC/RC credentials are isolated from HR Portal, Self-Service, and KPI Portal accounts.')
                ->schema([
                    TextInput::make('username')
                        ->label('Username')
                        ->placeholder('Enter SIC/RC username')
                        ->required()
                        ->minLength(5)
                        ->maxLength(191)
                        ->rule('regex:/^\S+$/')
                        ->unique(table: SicRcAccount::class, column: 'username', ignoreRecord: true)
                        ->validationMessages([
                            'regex' => 'The username must not contain spaces.',
                            'unique' => 'This SIC/RC username is already in use.',
                        ]),

                    TextInput::make('password')
                        ->label($creating ? 'Password' : 'New Password')
                        ->helperText($creating ? null : 'Leave blank to keep the current password.')
                        ->password()
                        ->revealable()
                        ->minLength(5)
                        ->required($creating),

                    TextInput::make('password_confirmation')
                        ->label('Confirm Password')
                        ->password()
                        ->revealable()
                        ->required($creating)
                        ->dehydrated(),

                    Toggle::make('is_active')
                        ->label('Enable SIC/RC portal access')
                        ->default(true),
                ])
                ->columns(2),

            Section::make('Branches / Stations')
                ->description('Select the branch or station that this SIC/RC account can access.')
                ->schema([
                    Repeater::make('branch_assignments')
                        ->label('Assigned Branches / Stations')
                        ->helperText('Each branch can only be assigned to one SIC/RC account.')
                        ->schema([
                            Select::make('branch_id')
                                ->label('Branch / Station')
                                ->options(fn (Get $get): array => $this->branchOptions($get('branch_id')))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                        ])
                        ->columns(1)
                        ->defaultItems(1)
                        ->addActionLabel('Add Branch / Station')
                        ->reorderable(false)
                        ->itemLabel(fn (array $state): ?string => $this->assignmentItemLabel($state))
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected function saveAccount(array $data, ?SicRcAccount $account = null): SicRcAccount
    {
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $passwordConfirmation = (string) ($data['password_confirmation'] ?? '');

        if (User::query()->whereRaw('LOWER(username) = ?', [mb_strtolower($username)])->exists()) {
            throw ValidationException::withMessages([
                'username' => 'This username belongs to an HR or employee account. Use a different SIC/RC username.',
            ]);
        }

        if (KpiAccount::query()->whereRaw('LOWER(username) = ?', [mb_strtolower($username)])->exists()) {
            throw ValidationException::withMessages([
                'username' => 'This username belongs to a KPI account. Use a different SIC/RC username.',
            ]);
        }

        if (SicRcAccount::query()
            ->when($account, fn (Builder $query): Builder => $query->whereKeyNot($account->getKey()))
            ->whereRaw('LOWER(username) = ?', [mb_strtolower($username)])
            ->exists()) {
            throw ValidationException::withMessages([
                'username' => 'This SIC/RC username is already in use.',
            ]);
        }

        if ($password !== '' && $password !== $passwordConfirmation) {
            throw ValidationException::withMessages([
                'password_confirmation' => 'The password confirmation does not match.',
            ]);
        }

        if (! $account && $password === '') {
            throw ValidationException::withMessages([
                'password' => 'A password is required for a new SIC/RC account.',
            ]);
        }

        $employeeId = (int) ($data['employee_id'] ?? 0);

        $account = DB::transaction(function () use ($account, $data, $employeeId, $password, $username): SicRcAccount {
            $employee = Employee::query()
                ->with('branch')
                ->lockForUpdate()
                ->find($employeeId);

            if (! $employee || $employee->trashed()) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Select an available employee.',
                ]);
            }

            $alreadyBound = SicRcAccount::query()
                ->when($account, fn (Builder $query): Builder => $query->whereKeyNot($account->getKey()))
                ->where('employee_id', $employee->getKey())
                ->lockForUpdate()
                ->exists();

            if ($alreadyBound) {
                throw ValidationException::withMessages([
                    'employee_id' => 'This employee is already bound to another SIC/RC account.',
                ]);
            }

            if (! $employee->branch_id || ! $employee->branch) {
                throw ValidationException::withMessages([
                    'employee_id' => 'The selected employee must have an assigned branch.',
                ]);
            }

            $assignments = $this->resolveSelectedBranches(
                (array) ($data['branch_assignments'] ?? []),
                $account,
            );

            if (! collect($assignments)->pluck('branch_id')->contains((int) $employee->branch_id)) {
                throw ValidationException::withMessages([
                    'branch_assignments' => 'The bound employee\'s current branch must be assigned to this SIC/RC account.',
                ]);
            }

            $attributes = [
                'employee_id' => $employee->getKey(),
                'username' => $username,
                'station_biometrics' => collect($assignments)
                    ->pluck('branch_name')
                    ->join(', ') ?: null,
                'biometric_devices' => $assignments ?: null,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ];

            if ($password !== '') {
                $attributes['password'] = $password;
            }

            if ($account) {
                $account->update($attributes);

                return $account->refresh();
            }

            return SicRcAccount::query()->create($attributes);
        });

        Notification::make()
            ->title($account->wasRecentlyCreated ? 'SIC/RC account created' : 'SIC/RC account updated')
            ->body('The employee identity and selected branches are now attached to this SIC/RC Portal account.')
            ->success()
            ->send();

        return $account;
    }

    protected function employeeOptions(?SicRcAccount $account = null): array
    {
        return Employee::query()
            ->with('branch')
            ->where(function (Builder $query) use ($account): void {
                $query->whereDoesntHave('sicRcAccount');

                if ($account?->employee_id) {
                    $query->orWhereKey($account->employee_id);
                }
            })
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
            ->mapWithKeys(fn (Employee $employee): array => [
                $employee->id => trim(sprintf(
                    '%s - %s | %s',
                    $employee->company_id ?? 'No ID',
                    $employee->full_name,
                    $employee->branch?->branch_name ?? 'No branch',
                )),
            ])
            ->all();
    }

    protected function branchOptions(mixed $selectedBranchId = null): array
    {
        $usedBranchIds = $this->usedBranchIds();

        if (filled($selectedBranchId)) {
            $usedBranchIds = array_values(array_diff($usedBranchIds, [(int) $selectedBranchId]));
        }

        return Branch::query()
            ->when($usedBranchIds !== [], fn (Builder $query): Builder => $query->whereNotIn('id', $usedBranchIds))
            ->orderBy('branch_name')
            ->pluck('branch_name', 'id')
            ->all();
    }

    protected function assignmentFormRows(SicRcAccount $record): array
    {
        return collect($record->branchAssignments())
            ->filter(fn (array $assignment): bool => filled($assignment['branch_id'] ?? null))
            ->map(fn (array $assignment): array => [
                'branch_id' => (int) $assignment['branch_id'],
            ])
            ->values()
            ->all();
    }

    protected function resolveSelectedBranches(array $rows, ?SicRcAccount $account): array
    {
        $rows = collect($rows)
            ->filter(fn (mixed $row): bool => is_array($row) && filled($row['branch_id'] ?? null))
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'branch_assignments' => 'Select at least one branch or station.',
            ]);
        }

        $branchIds = $rows->pluck('branch_id')->map(fn (mixed $branchId): int => (int) $branchId)->values();

        if ($branchIds->unique()->count() !== $branchIds->count()) {
            throw ValidationException::withMessages([
                'branch_assignments' => 'Each branch can only be selected once.',
            ]);
        }

        if ($branchIds->intersect($this->usedBranchIds($account))->isNotEmpty()) {
            throw ValidationException::withMessages([
                'branch_assignments' => 'One or more selected branches are already attached to another SIC/RC account.',
            ]);
        }

        $branches = Branch::query()
            ->whereIn('id', $branchIds)
            ->pluck('branch_name', 'id');

        if ($branches->count() !== $branchIds->unique()->count()) {
            throw ValidationException::withMessages([
                'branch_assignments' => 'Some selected branches are no longer available.',
            ]);
        }

        return $branchIds
            ->map(fn (int $branchId): array => [
                'branch_id' => $branchId,
                'branch_name' => (string) $branches->get($branchId),
            ])
            ->values()
            ->all();
    }

    protected function usedBranchIds(?SicRcAccount $except = null): array
    {
        return SicRcAccount::query()
            ->when($except, fn (Builder $query): Builder => $query->whereKeyNot($except->getKey()))
            ->whereNotNull('biometric_devices')
            ->get(['id', 'biometric_devices'])
            ->flatMap(fn (SicRcAccount $account): array => collect($account->branchAssignments())
                ->pluck('branch_id')
                ->filter()
                ->values()
                ->all())
            ->map(fn (mixed $branchId): int => (int) $branchId)
            ->unique()
            ->values()
            ->all();
    }

    protected function assignmentItemLabel(array $state): ?string
    {
        return filled($state['branch_id'] ?? null)
            ? Branch::query()->whereKey((int) $state['branch_id'])->value('branch_name')
            : null;
    }

    protected function branchDisplayInline(SicRcAccount $record): string
    {
        return str_replace(PHP_EOL, ', ', $this->branchDisplayList($record));
    }

    protected function branchDisplayList(SicRcAccount $record): string
    {
        $assignments = collect($record->branchAssignments());

        if ($assignments->isEmpty()) {
            return 'Not configured';
        }

        $branchIds = $assignments
            ->pluck('branch_id')
            ->filter()
            ->map(fn (mixed $branchId): int => (int) $branchId)
            ->unique()
            ->values();

        $branches = Branch::query()
            ->whereIn('id', $branchIds)
            ->pluck('branch_name', 'id');

        return $assignments
            ->map(fn (array $assignment): string => (string) ($branches->get((int) $assignment['branch_id']) ?: ($assignment['branch_name'] ?? 'Unknown branch')))
            ->filter()
            ->unique()
            ->join(PHP_EOL);
    }
}
