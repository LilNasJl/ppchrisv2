<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\KpiAccount;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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

class KpiAccounts extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected string $view = 'filament-panels::pages.page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'KPI Accounts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => KpiAccount::query()
                ->with(['branches', 'departments', 'employees'])
                ->orderBy('username'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('scope_label')
                    ->label('Account Scope')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('scope_summary')
                    ->label('Assigned Coverage')
                    ->wrap()
                    ->limit(90),

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
                        ->modalHeading(fn (KpiAccount $record): string => 'KPI Account - '.$record->username)
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalContent(fn (KpiAccount $record) => view('filament.pages.partials.kpi-account-details', [
                            'account' => $record->loadMissing(['branches', 'departments', 'employees']),
                        ])),

                    Action::make('edit')
                        ->label('Edit')
                        ->icon(Heroicon::PencilSquare)
                        ->schema($this->accountFormSchema(false))
                        ->fillForm(fn (KpiAccount $record): array => [
                            'username' => $record->username,
                            'scope_type' => $record->scope_type,
                            'scope_ids' => $this->selectedScopeIds($record),
                            'is_active' => $record->is_active,
                        ])
                        ->modalHeading('Edit KPI Account')
                        ->modalSubmitActionLabel('Save Changes')
                        ->action(fn (KpiAccount $record, array $data): mixed => $this->saveAccount($data, $record)),

                    DeleteAction::make()
                        ->modalHeading('Delete KPI account')
                        ->modalDescription('This prevents access and removes the account from the active KPI account list. Existing rating-cycle snapshots remain intact until the account is permanently removed.'),
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
                ->label('Create KPI Account')
                ->icon(Heroicon::Plus)
                ->schema($this->accountFormSchema(true))
                ->modalHeading('Create KPI Account')
                ->modalSubmitActionLabel('Create Account')
                ->action(fn (array $data): mixed => $this->saveAccount($data)),

            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->color('gray')
                ->url(Kpi::getUrl()),
        ];
    }

    protected function accountFormSchema(bool $creating): array
    {
        return [
            Section::make('Sign-in Details')
                ->description('KPI credentials are isolated from HR Portal and Employee Self-Service accounts.')
                ->schema([
                    TextInput::make('username')
                        ->label('Username')
                        ->placeholder('Enter KPI username')
                        ->required()
                        ->maxLength(191)
                        ->rule('regex:/^\S+$/')
                        ->unique(table: KpiAccount::class, column: 'username', ignoreRecord: true)
                        ->validationMessages([
                            'regex' => 'The username must not contain spaces.',
                            'unique' => 'This KPI username is already in use.',
                        ]),

                    TextInput::make('password')
                        ->label($creating ? 'Password' : 'New Password')
                        ->helperText($creating ? null : 'Leave blank to keep the current password.')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->required($creating),

                    TextInput::make('password_confirmation')
                        ->label('Confirm Password')
                        ->password()
                        ->revealable()
                        ->required($creating)
                        ->dehydrated(),

                    Toggle::make('is_active')
                        ->label('Enable KPI portal access')
                        ->default(true),
                ])
                ->columns(2),

            Section::make('Rating Coverage')
                ->description('Use one scope type per account. Branch scopes rate each selected branch as one target.')
                ->schema([
                    Select::make('scope_type')
                        ->label('Scope Type')
                        ->options(KpiAccount::scopeOptions())
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('scope_ids', []))
                        ->required(),

                    Select::make('scope_ids')
                        ->label(fn (Get $get): string => match ($get('scope_type')) {
                            KpiAccount::SCOPE_BRANCH => 'Selected Branches',
                            KpiAccount::SCOPE_DEPARTMENT => 'Selected Departments',
                            KpiAccount::SCOPE_EMPLOYEE => 'Selected Employees',
                            default => 'Assigned Coverage',
                        })
                        ->options(fn (Get $get): array => $this->scopeOptions((string) $get('scope_type')))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    protected function saveAccount(array $data, ?KpiAccount $account = null): KpiAccount
    {
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $passwordConfirmation = (string) ($data['password_confirmation'] ?? '');
        $scopeType = (string) ($data['scope_type'] ?? '');
        $scopeIds = collect($data['scope_ids'] ?? [])->filter()->map(fn ($id): int => (int) $id)->unique()->values();

        if (User::query()->whereRaw('LOWER(username) = ?', [mb_strtolower($username)])->exists()) {
            throw ValidationException::withMessages([
                'username' => 'This username belongs to an HR or employee account. Use a different KPI username.',
            ]);
        }

        if ($password !== '' && $password !== $passwordConfirmation) {
            throw ValidationException::withMessages([
                'password_confirmation' => 'The password confirmation does not match.',
            ]);
        }

        if (! $account && $password === '') {
            throw ValidationException::withMessages([
                'password' => 'A password is required for a new KPI account.',
            ]);
        }

        if (! array_key_exists($scopeType, KpiAccount::scopeOptions()) || $scopeIds->isEmpty()) {
            throw ValidationException::withMessages([
                'scope_ids' => 'Select at least one valid rating assignment.',
            ]);
        }

        if ($this->validScopeIdCount($scopeType, $scopeIds->all()) !== $scopeIds->count()) {
            throw ValidationException::withMessages([
                'scope_ids' => 'One or more selected assignments are unavailable. Refresh the form and select again.',
            ]);
        }

        $account = DB::transaction(function () use ($account, $data, $username, $password, $scopeType, $scopeIds): KpiAccount {
            $attributes = [
                'username' => $username,
                'scope_type' => $scopeType,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ];

            if ($password !== '') {
                $attributes['password'] = $password;
            }

            if ($account) {
                $account->update($attributes);
            } else {
                $account = KpiAccount::query()->create($attributes);
            }

            $account->branches()->sync($scopeType === KpiAccount::SCOPE_BRANCH ? $scopeIds->all() : []);
            $account->departments()->sync($scopeType === KpiAccount::SCOPE_DEPARTMENT ? $scopeIds->all() : []);
            $account->employees()->sync($scopeType === KpiAccount::SCOPE_EMPLOYEE ? $scopeIds->all() : []);

            return $account;
        });

        Notification::make()
            ->title($account->wasRecentlyCreated ? 'KPI account created' : 'KPI account updated')
            ->body('The account can sign in only through the KPI Portal.')
            ->success()
            ->send();

        return $account;
    }

    protected function selectedScopeIds(KpiAccount $account): array
    {
        return match ($account->scope_type) {
            KpiAccount::SCOPE_BRANCH => $account->branches->modelKeys(),
            KpiAccount::SCOPE_DEPARTMENT => $account->departments->modelKeys(),
            KpiAccount::SCOPE_EMPLOYEE => $account->employees->modelKeys(),
            default => [],
        };
    }

    protected function scopeOptions(string $scopeType): array
    {
        return match ($scopeType) {
            KpiAccount::SCOPE_BRANCH => Branch::query()
                ->orderBy('branch_name')
                ->pluck('branch_name', 'id')
                ->all(),
            KpiAccount::SCOPE_DEPARTMENT => Department::query()
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn (Department $department): array => [
                    $department->id => ($department->acronym ? $department->acronym.' - ' : '').$department->name,
                ])
                ->all(),
            KpiAccount::SCOPE_EMPLOYEE => Employee::query()
                ->activeEmployment()
                ->with('branch')
                ->orderBy('lastname')
                ->orderBy('middlename')
                ->orderBy('firstname')
                ->get()
                ->mapWithKeys(fn (Employee $employee): array => [
                    $employee->id => trim(($employee->company_id ?: 'No ID').' - '.$employee->full_name.' | '.($employee->branch?->branch_name ?: 'No branch')),
                ])
                ->all(),
            default => [],
        };
    }

    protected function validScopeIdCount(string $scopeType, array $scopeIds): int
    {
        return match ($scopeType) {
            KpiAccount::SCOPE_BRANCH => Branch::query()->whereKey($scopeIds)->count(),
            KpiAccount::SCOPE_DEPARTMENT => Department::query()->whereKey($scopeIds)->count(),
            KpiAccount::SCOPE_EMPLOYEE => Employee::query()
                ->activeEmployment()
                ->whereKey($scopeIds)
                ->count(),
            default => 0,
        };
    }
}
