<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\EditEmployeeDetails;
use App\Filament\Pages\EmployeeDetails;
use App\Filament\Pages\ViewEmployeeDetails;
use App\Models\AccountStatusHistory;
use App\Models\Deduction;
use App\Models\Employee as ModelsEmployee;
use App\Models\EmployeeDeduction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use STS\FilamentImpersonate\Actions\Impersonate;

class EmployeeDetailsTable extends TableWidget
{
    protected static ?string $heading = '';

    protected static bool $isDiscovered = false;

    protected const DEDUCTION_GROUPS = [
        'Deductions' => [
            'SHORTAGES',
            'COMPANY UNIFORM',
        ],
        'REMITTANCES' => [
            'SSS LOAN',
            'SSS EE',
            'HDMF LOAN',
            'HDMF EE',
            'PHIC EE',
        ],
    ];

    protected function ensureDefaultDeductions(): void
    {
        foreach (collect(self::DEDUCTION_GROUPS)->flatten() as $title) {
            Deduction::query()->firstOrCreate(
                ['title' => $title],
                ['description' => $title],
            );
        }
    }

    protected function makeDeductionInput(Deduction $deduction): TextInput
    {
        return TextInput::make("deductions.{$deduction->id}")
            ->label($deduction->title)
            ->numeric()
            ->prefix('₱')
            ->default(0)
            ->minValue(0);
    }

    protected function scheduleTypeForRateType(?string $rateType): string
    {
        return $rateType === 'daily' ? 'daily' : 'regular';
    }

    protected function dailyRateFromMonthly(mixed $monthlyRate): ?float
    {
        $monthlyRate = filled($monthlyRate) ? (float) $monthlyRate : 0.0;

        return $monthlyRate > 0
            ? round($monthlyRate / ModelsEmployee::REGULAR_WORK_DAYS_PER_MONTH, 2)
            : null;
    }

    protected function profilePreview(?ModelsEmployee $record): HtmlString
    {
        if (! $record) {
            return new HtmlString('');
        }

        $photoUrl = $record->user?->profile_photo_url;
        $initials = collect([$record->firstname, $record->lastname])
            ->filter()
            ->map(fn (string $name): string => strtoupper(substr($name, 0, 1)))
            ->take(2)
            ->implode('');
        $age = $record->birthdate ? $record->birthdate->age.' year/s old' : 'Age not set';
        $name = e($record->full_name);

        $avatar = $photoUrl
            ? '<img src="'.e($photoUrl).'" alt="'.$name.'" style="width: 120px; height: 120px; border-radius: 999px; object-fit: cover; border: 2px solid rgba(96, 165, 250, .7);">'
            : '<div style="width: 120px; height: 120px; border-radius: 999px; display: grid; place-items: center; background: rgba(37, 99, 235, .18); color: #60a5fa; font-size: 32px; font-weight: 800;">'.e($initials ?: 'NA').'</div>';

        $photoActions = $photoUrl
            ? '<div style="display: flex; flex-wrap: wrap; gap: 8px; justify-content: center;">'
                .'<a href="'.e($photoUrl).'" target="_blank" rel="noopener noreferrer" style="border: 1px solid rgba(37, 99, 235, .45); border-radius: 6px; color: #2563eb; font-size: 12px; font-weight: 750; padding: 6px 11px; text-decoration: none;">View</a>'
                .'<a href="'.e($photoUrl).'" download style="background: #2563eb; border: 1px solid #2563eb; border-radius: 6px; color: #fff; font-size: 12px; font-weight: 750; padding: 6px 11px; text-decoration: none;">Download</a>'
                .'</div>'
            : '';

        return new HtmlString(
            '<div style="display: grid; place-items: center; gap: 10px; padding: 12px;">'
            .$avatar
            .'<div style="text-align: center;"><div style="font-weight: 800;">'.$name.'</div>'
            .'<div style="color: #94a3b8; font-size: 13px;">'.e($age).'</div></div>'
            .$photoActions
            .'</div>'
        );
    }

    protected function getDeductionFields(array $titles): array
    {
        $this->ensureDefaultDeductions();

        return Deduction::query()
            ->whereIn('title', $titles)
            ->orderByRaw('FIELD(title, "'.implode('","', $titles).'")')
            ->get()
            ->map(fn (Deduction $deduction) => $this->makeDeductionInput($deduction))
            ->all();
    }

    protected function getOtherDeductionFields(array $selectedDeductionIds = []): array
    {
        $this->ensureDefaultDeductions();

        $selectedDeductionIds = $this->validOtherDeductionIds($selectedDeductionIds);

        if (empty($selectedDeductionIds)) {
            return [
                Placeholder::make('no_other_deductions_selected')
                    ->hiddenLabel()
                    ->content('Select other deductions to link them to this employee.')
                    ->columnSpanFull(),
            ];
        }

        return Deduction::query()
            ->whereIn('id', $selectedDeductionIds)
            ->orderBy('title')
            ->get()
            ->map(fn (Deduction $deduction) => $this->makeDeductionInput($deduction))
            ->all();
    }

    protected function getOtherDeductionOptions(): array
    {
        return Deduction::query()
            ->whereNotIn('title', Deduction::defaultTitles())
            ->orderBy('title')
            ->pluck('title', 'id')
            ->all();
    }

    protected function getSelectedOtherDeductionIds(ModelsEmployee $record): array
    {
        return $record->employeeDeductions()
            ->whereHas('deduction', fn (Builder $query) => $query->whereNotIn('title', Deduction::defaultTitles()))
            ->where('amount', '>', 0)
            ->pluck('deduction_id')
            ->map(fn (int $id): string => (string) $id)
            ->values()
            ->all();
    }

    protected function validOtherDeductionIds(array $deductionIds): array
    {
        $deductionIds = collect($deductionIds)
            ->filter(fn ($id): bool => filled($id))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($deductionIds)) {
            return [];
        }

        return Deduction::query()
            ->whereIn('id', $deductionIds)
            ->whereNotIn('title', Deduction::defaultTitles())
            ->pluck('id')
            ->all();
    }

    protected function getDefaultDeductionIds(): array
    {
        $this->ensureDefaultDeductions();

        return Deduction::query()
            ->whereIn('title', Deduction::defaultTitles())
            ->pluck('id')
            ->all();
    }

    protected function getDeductionState(ModelsEmployee $record): array
    {
        $saved = $record->employeeDeductions()
            ->pluck('amount', 'deduction_id')
            ->toArray();

        return Deduction::query()
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [(string) $id => $saved[$id] ?? 0])
            ->toArray();
    }

    protected function getEmployeeFormSchema(): array
    {
        return [
            Tabs::make('Employee Details')
                ->tabs([
                    Tabs\Tab::make('Profile Picture')
                        ->icon(Heroicon::UserCircle)
                        ->schema([
                            Placeholder::make('profile_card')
                                ->hiddenLabel()
                                ->content(fn (?ModelsEmployee $record): HtmlString => $this->profilePreview($record))
                                ->columnSpanFull(),

                            FileUpload::make('profile_photo_path')
                                ->label('Profile Picture')
                                ->image()
                                ->previewable()
                                ->openable()
                                ->downloadable()
                                ->acceptedFileTypes(['image/png', 'image/jpeg'])
                                ->maxSize(3072)
                                ->disk('public')
                                ->directory('profile-photos')
                                ->visibility('public')
                                ->fetchFileInformation(false)
                                ->columnSpanFull(),
                        ]),

                    Tabs\Tab::make('Basic Info')
                        ->icon('heroicon-s-user')
                        ->schema([
                            Group::make()
                                ->schema([
                                    TextInput::make('firstname')
                                        ->placeholder('e.g., Juan')
                                        ->required(),

                                    TextInput::make('middlename')
                                        ->placeholder('e.g., Dela | Leave it empty if not applicable'),

                                    TextInput::make('lastname')
                                        ->placeholder('e.g., Cruz')
                                        ->required(),
                                ])
                                ->columns(3),

                            Group::make()
                                ->schema([
                                    DatePicker::make('birthdate')
                                        ->placeholder('Birth Date')
                                        ->required(),

                                    Select::make('status')
                                        ->label('Civil Status')
                                        ->options([
                                            'single' => 'Single',
                                            'married' => 'Married',
                                            'divorce' => 'Divorce',
                                            'widowed' => 'Widowed',
                                            'separated' => 'Separated',
                                            'annuled' => 'Annuled',
                                        ])
                                        ->required(),

                                    Select::make('gender')
                                        ->label('Gender')
                                        ->options([
                                            'male' => 'male',
                                            'female' => 'female',
                                        ])
                                        ->required(),

                                    TextInput::make('mobile')
                                        ->label('Mobile #')
                                        ->tel()
                                        ->mask('99999999999')
                                        ->placeholder('09123456789')
                                        ->regex('/^09\d{9}$/')
                                        ->validationMessages([
                                            'regex' => 'The mobile number must start with 09 and be exactly 11 digits.',
                                        ]),

                                    TextInput::make('kids')
                                        ->label('No. of Kids')
                                        ->placeholder('0 if not applicable')
                                        ->integer()
                                        ->default(0)
                                        ->dehydrateStateUsing(fn ($state) => $state ?? 0)
                                        ->maxValue(20)
                                        ->required(),

                                    TextInput::make('address')
                                        ->label('Permanent Address')
                                        ->placeholder('e.g., Tagum City, Philippines')
                                        ->required()
                                        ->columnSpanFull(),

                                ])
                                ->columns(3),

                            Placeholder::make('divider2')
                                ->label('GOVERNMENT AND BANK ID')
                                ->content(new HtmlString('<hr class="my-4">')),

                            Group::make()
                                ->schema([
                                    TextInput::make('gsis')
                                        ->label('GSIS BP #')
                                        ->mask('99999999999')
                                        ->placeholder('e.g., 20012345678')
                                        ->maxLength(11),

                                    TextInput::make('philhealth')
                                        ->label('PhilHealth ID #')
                                        ->mask('99-999999999-9')
                                        ->placeholder('e.g., 01-234567890-1')
                                        ->regex('/^\d{2}-\d{9}-\d{1}$/')
                                        ->validationMessages([
                                            'regex' => 'The PhilHealth number must follow the 00-000000000-0 format.',
                                        ]),

                                    TextInput::make('pagibig')
                                        ->label('Pag-IBIG MID #')
                                        ->mask('9999-9999-9999')
                                        ->placeholder('e.g., 1234-5678-9012')
                                        ->regex('/^\d{4}-\d{4}-\d{4}$/')
                                        ->validationMessages([
                                            'regex' => 'The Pag-IBIG number must be exactly 12 digits (0000-0000-0000).',
                                        ]),

                                    TextInput::make('tin')
                                        ->label('TIN #')
                                        ->mask('999-999-999')
                                        ->placeholder('e.g., 000-000-000')
                                        ->regex('/^\d{3}-\d{3}-\d{3}$/')
                                        ->validationMessages([
                                            'regex' => 'The TIN format must be 000-000-000.',
                                        ]),

                                    TextInput::make('sss')
                                        ->label('SSS #')
                                        ->mask('99-9999999-9')
                                        ->placeholder('e.g., 33-1234567-8')
                                        ->regex('/^\d{2}-\d{7}-\d{1}$/')
                                        ->validationMessages([
                                            'regex' => 'The SSS number must follow the 00-0000000-0 format.',
                                        ]),

                                    TextInput::make('bank_id_no')
                                        ->label('Bank ID No.')
                                        ->placeholder('e.g., 1234567890')
                                        ->maxLength(191),
                                ])
                                ->columns(2),
                        ]),

                    Tabs\Tab::make('Designation')
                        ->icon('heroicon-s-briefcase')
                        ->schema([
                            TextInput::make('fingerprint_id')
                                ->placeholder('e.g., 1234567'),

                            Select::make('designation_id')
                                ->relationship('designation', 'title')
                                ->required(),

                            Select::make('department_id')
                                ->relationship('department', 'name')
                                ->required(),

                            Select::make('branch_id')
                                ->relationship('branch', 'branch_name')
                                ->required(),

                            DatePicker::make('hired_date')
                                ->label('Hired Date')
                                ->required(),

                            TextInput::make('tenure')
                                ->label('Tenure')
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('employment_type')
                                ->label('Employment Type')
                                ->options([
                                    'Permanent' => 'Permanent',
                                    'Probationary' => 'Probationary',
                                    'Temporary' => 'Temporary',
                                    'Coterminous' => 'Coterminous',
                                    'Contractual' => 'Contractual',
                                    'Casual' => 'Casual',
                                    'Job Order' => 'Job Order',
                                    'Contract of Service' => 'Contract of Service',
                                    'Substitute' => 'Substitute',
                                    'Resigned' => 'Resigned',
                                    'Terminated' => 'Terminated',
                                    'Force Resigned' => 'Force Resigned',
                                    'Death of Employee' => 'Death of Employee',
                                ])
                                ->required(),

                        ])
                        ->columns(2),

                    Tabs\Tab::make('Education')
                        ->icon(Heroicon::AcademicCap)
                        ->schema([
                            TextInput::make('school_name')
                                ->label('School Name')
                                ->placeholder('e.g., University of Mindanao'),

                            Group::make()
                                ->schema([
                                    Select::make('school_level')
                                        ->label('Highest Level Attended')
                                        ->options([
                                            'Elementary' => 'Elementary',
                                            'Secondary' => 'Secondary (High School)',
                                            'Vocational' => 'Vocational / Trade Course',
                                            'College' => 'College (Tertiary)',
                                            'Graduate Studies' => 'Graduate Studies (Master\'s/Doctorate)',
                                        ]),

                                    DatePicker::make('year_grad')
                                        ->label('Year Graduated'),
                                ])
                                ->columns(2),
                        ]),

                    Tabs\Tab::make('Deductions')
                        ->icon(Heroicon::MinusCircle)
                        ->schema([
                            Fieldset::make('Deductions')
                                ->schema(fn (): array => $this->getDeductionFields(self::DEDUCTION_GROUPS['Deductions']))
                                ->columns(2),

                            Fieldset::make('REMITTANCES')
                                ->schema(fn (): array => $this->getDeductionFields(self::DEDUCTION_GROUPS['REMITTANCES']))
                                ->columns(2),

                            Fieldset::make('Other Deductions')
                                ->schema(fn (Get $get): array => [
                                    Select::make('other_deduction_ids')
                                        ->label('Linked Other Deductions')
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->options(fn (): array => $this->getOtherDeductionOptions())
                                        ->helperText('Only selected deductions will be linked to this employee.')
                                        ->columnSpanFull(),

                                    ...$this->getOtherDeductionFields($get('other_deduction_ids') ?? []),
                                ])
                                ->columns(2),
                        ]),

                    Tabs\Tab::make('Leave')
                        ->icon(Heroicon::CalendarDays)
                        ->schema([
                            Group::make()
                                ->schema([
                                    TextInput::make('leave_credits')
                                        ->label('Leave Count')
                                        ->numeric()
                                        ->minValue(0),

                                    TextInput::make('birthday_leave_credits')
                                        ->label('Birthday Leave Count')
                                        ->numeric()
                                        ->minValue(0),

                                    TextInput::make('leave_credits_year')
                                        ->label('Leave Year')
                                        ->disabled()
                                        ->dehydrated(false),
                                ])
                                ->columns(3),
                        ]),

                    Tabs\Tab::make('Salary')
                        ->icon(Heroicon::Banknotes)
                        ->schema([
                            Group::make()
                                ->schema([
                                    Select::make('rate_type')
                                        ->label('Rate Type')
                                        ->options([
                                            'daily' => 'Daily',
                                            'monthly' => 'Monthly',
                                        ])
                                        ->live()
                                        ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('schedule_type', $this->scheduleTypeForRateType($state)))
                                        ->required(),

                                    Select::make('schedule_type')
                                        ->label('Schedule Type')
                                        ->options([
                                            'regular' => 'Regular Sched',
                                            'daily' => 'Daily Sched',
                                        ])
                                        ->default('regular')
                                        ->disabled()
                                        ->dehydrated(true),

                                    Select::make('payment_type')
                                        ->label('Payment Type')
                                        ->options([
                                            'cash' => 'Cash',
                                            'atm' => 'ATM',
                                        ])
                                        ->required(),

                                    TextInput::make('daily_rate')
                                        ->label('Daily Rate')
                                        ->numeric()
                                        ->prefix('₱')
                                        ->readOnly(fn (Get $get): bool => $get('rate_type') === 'monthly')
                                        ->dehydrated(true)
                                        ->required(fn (Get $get): bool => $get('rate_type') === 'daily'),

                                    TextInput::make('monthly_rate')
                                        ->label('Basic Monthly')
                                        ->numeric()
                                        ->prefix('₱')
                                        ->live(onBlur: true)
                                        ->visible(fn (Get $get): bool => $get('rate_type') === 'monthly')
                                        ->afterStateUpdated(fn (Set $set, mixed $state): mixed => $set('daily_rate', $this->dailyRateFromMonthly($state)))
                                        ->required(fn (Get $get): bool => $get('rate_type') === 'monthly'),

                                    TextInput::make('allowance')
                                        ->label('Monthly Allowance')
                                        ->numeric()
                                        ->prefix('₱')
                                        ->dehydrateStateUsing(fn ($state) => $state ?? 0)
                                        ->default(0),

                                ])
                                ->columns(2),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => User::query()
                ->with(['employee.designation', 'employee.department', 'employee.branch'])
                ->where('role', 'employee')
                ->whereHas('employee')
                ->leftJoin('employees as account_employees', 'account_employees.user_id', '=', 'users.id')
                ->select('users.*'))
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderBy('account_employees.uid'))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                ImageColumn::make('profile_photo')
                    ->label('Profile')
                    ->getStateUsing(fn (User $record): ?string => $record->profile_photo_url)
                    ->defaultImageUrl(fn (): string => asset('image/ppc-circle-white.png'))
                    ->circular(),

                TextColumn::make('employee.uid')
                    ->label('ID No.')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => ModelsEmployee::companyIdFromUid($state) ?? 'N/A')
                    ->searchable()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('account_employees.uid', $direction)),

                TextColumn::make('employee.lastname')
                    ->label('Name')
                    ->formatStateUsing(fn (User $record): string => $record->employee?->full_name ?? 'N/A')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('employee', fn (Builder $employeeQuery): Builder => $employeeQuery
                            ->where('lastname', 'like', "%{$search}%")
                            ->orWhere('middlename', 'like', "%{$search}%")
                            ->orWhere('firstname', 'like', "%{$search}%")))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('account_employees.lastname', $direction)
                        ->orderBy('account_employees.middlename', $direction)
                        ->orderBy('account_employees.firstname', $direction)),

                TextColumn::make('employee.designation.title')
                    ->label('Designation')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.department.name')
                    ->label('Department')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('employee.branch.branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('employee.employment_type')
                    ->label('Employment Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, User $record): string => $record->employee?->hasEndedEmployment()
                            ? "Employment End: {$state}"
                            : ($state ?: 'N/A')
                    )
                    ->color(fn (User $record): string => $record->employee?->hasEndedEmployment() ? 'danger' : 'success'),

                TextColumn::make('is_disabled')
                    ->label('Account')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Disabled' : 'Enabled')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success'),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('users.created_at', 'like', "%{$search}%"))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('users.created_at', $direction)),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewEmployeeDetails')
                        ->label('View')
                        ->icon(Heroicon::Eye)
                        ->url(fn (User $record): string => ViewEmployeeDetails::getUrl([
                            'employeeId' => $record->employee?->publicKey(),
                            'returnUrl' => $this->tableReturnUrl(EmployeeDetails::getUrl()),
                        ])),

                    Action::make('editEmployeeDetails')
                        ->label('Edit')
                        ->icon(Heroicon::PencilSquare)
                        ->url(fn (User $record): string => EditEmployeeDetails::getUrl([
                            'employeeId' => $record->employee?->publicKey(),
                            'returnUrl' => $this->tableReturnUrl(EmployeeDetails::getUrl()),
                        ])),

                    Action::make('toggleAccountStatus')
                        ->label(fn (User $record): string => $record->is_disabled ? 'Enable Account' : 'Disable Account')
                        ->icon(fn (User $record): Heroicon => $record->is_disabled ? Heroicon::CheckCircle : Heroicon::NoSymbol)
                        ->color(fn (User $record): string => $record->is_disabled ? 'success' : 'danger')
                        ->requiresConfirmation()
                        ->modalHeading(fn (User $record): string => $record->is_disabled ? 'Enable employee account?' : 'Disable employee account?')
                        ->modalDescription('Please add remarks for this account status change. This will be saved in the account history.')
                        ->schema([
                            Textarea::make('remarks')
                                ->label('Remarks')
                                ->required()
                                ->rows(4)
                                ->maxLength(1000)
                                ->columnSpanFull(),
                        ])
                        ->action(function (User $record, array $data): void {
                            $newState = ! (bool) $record->is_disabled;

                            $record->forceFill([
                                'is_disabled' => $newState,
                            ])->save();

                            AccountStatusHistory::create([
                                'user_id' => $record->id,
                                'changed_by_user_id' => auth()->id(),
                                'is_disabled' => $newState,
                                'remarks' => $data['remarks'] ?? null,
                            ]);

                            Notification::make()
                                ->title($newState ? 'Account disabled' : 'Account enabled')
                                ->success()
                                ->send();
                        }),

                    Action::make('accountHistory')
                        ->label('Account History')
                        ->icon(Heroicon::Clock)
                        ->modalHeading(fn (User $record): string => ($record->employee?->full_name ?? 'Employee').' Account History')
                        ->modalSubmitAction(false)
                        ->modalContent(fn (User $record) => view('filament.resources.users.partials.account-history', [
                            'histories' => $record->accountStatusHistories()
                                ->with('changedBy')
                                ->get(),
                        ])),

                    Impersonate::make('impersonateEmployee')
                        ->label('Impersonate')
                        ->guard('web')
                        ->redirectTo(url('/employee'))
                        ->backTo(fn (): string => $this->tableReturnUrl(EmployeeDetails::getUrl()))
                        ->withoutSpa()
                        ->hidden(fn (User $record): bool => ! auth()->user()?->can('Impersonate:Employee')
                            || (bool) $record->is_disabled
                            || $record->trashed()
                            || ! $record->employee
                            || $record->employee->trashed()
                            || $record->employee->hasEndedEmployment()),
                ])
                    ->icon(Heroicon::EllipsisHorizontal),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function tableReturnUrl(string $baseUrl): string
    {
        $query = request()->query();
        unset($query['returnUrl']);

        $query[$this->getTablePaginationPageName()] = $this->getTablePage();

        foreach (['tableRecordsPerPage', 'tableSearch', 'tableFilters', 'tableSort'] as $property) {
            if (filled($this->{$property})) {
                $query[$property] = $this->{$property};
            }
        }

        return $baseUrl.(blank($query) ? '' : '?'.Arr::query($query));
    }

    protected function syncEmployeeDeductions(ModelsEmployee $record, array $deductions, array $otherDeductionIds): void
    {
        $defaultDeductionIds = $this->getDefaultDeductionIds();
        $selectedOtherDeductionIds = $this->validOtherDeductionIds($otherDeductionIds);

        EmployeeDeduction::query()
            ->where('employee_id', $record->id)
            ->whereNotIn('deduction_id', $defaultDeductionIds)
            ->delete();

        foreach ([...$defaultDeductionIds, ...$selectedOtherDeductionIds] as $deductionId) {
            $amount = $deductions[$deductionId] ?? 0;

            EmployeeDeduction::updateOrCreate(
                [
                    'employee_id' => $record->id,
                    'deduction_id' => $deductionId,
                ],
                [
                    'amount' => filled($amount) ? $amount : 0,
                ],
            );
        }
    }
}
