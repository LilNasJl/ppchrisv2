<?php

namespace App\Filament\Employee\Pages;

use App\Models\Deduction;
use App\Models\Employee;
use App\Services\EmployeeProfileCompletionService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MyProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.employee.pages.my-profile';

    protected static ?string $slug = 'my-profile';

    protected static ?string $title = 'My Profile';

    protected static ?string $navigationLabel = 'My Profile';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserCircle;

    protected static string|\UnitEnum|null $navigationGroup = 'My Profile';

    protected static ?int $navigationSort = 1;

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

    public ?array $data = [];

    public static function getNavigationBadge(): ?string
    {
        $count = app(EmployeeProfileCompletionService::class)
            ->total(auth()->user()?->employee);

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveProfile')
                ->label('Save Changes')
                ->icon(Heroicon::CheckCircle)
                ->action(fn () => $this->save()),
        ];
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function getSubheading(): string
    {
        return 'Review your personal information, employment details, statutory IDs, and account settings.';
    }

    public function mount(): void
    {
        $user = auth()->user();
        $employee = $this->employee();

        $this->form->fill([
            ...$employee->only([
                'lastname', 'middlename', 'firstname', 'birthdate', 'status', 'gender',
                'fingerprint_id', 'hired_date', 'employment_type', 'mobile', 'kids',
                'address', 'gsis', 'philhealth', 'pagibig', 'tin', 'sss',
                'school_name', 'school_level', 'year_grad', 'rate_type', 'payment_type',
                'daily_rate', 'monthly_rate', 'allowance',
            ]),
            'account_employee_id' => Employee::companyIdFromUid($employee->uid),
            'account_fingerprint_id' => $employee->fingerprint_id,
            'account_full_name' => $employee->full_name ?? $user?->name,
            'email' => $user?->email,
            'profile_photo_path' => $user?->profile_photo_path,
            'designation' => $employee->designation?->title,
            'department' => $employee->department?->name,
            'branch' => $employee->branch?->branch_name,
            'tenure' => $employee->tenure,
            'deductions' => $this->getDeductionState($employee),
        ]);
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return [
            Tabs::make('Profile Details')
                ->tabs([
                    Tabs\Tab::make('My Account')
                        ->icon(Heroicon::UserCircle)
                        ->schema([
                            View::make('filament.employee.pages.partials.profile-account-card')
                                ->columnSpanFull(),

                            Section::make('Account Credentials & Sign-In Details')
                                ->description('Manage your login identity and update your system notification email.')
                                ->schema([
                                    TextInput::make('account_full_name')
                                        ->label('Full Name')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->prefixIcon(Heroicon::User),

                                    TextInput::make('account_employee_id')
                                        ->label('Employee ID')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->prefixIcon(Heroicon::Identification),

                                    TextInput::make('account_fingerprint_id')
                                        ->label('Biometric / Fingerprint ID')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->prefixIcon(Heroicon::FingerPrint),

                                    TextInput::make('email')
                                        ->label('Account Email Address')
                                        ->email()
                                        ->required()
                                        ->prefixIcon(Heroicon::Envelope)
                                        ->rules(fn () => [
                                            Rule::unique('users', 'email')->ignore(auth()->id()),
                                        ])
                                        ->helperText('Used for system sign-in and communications.')
                                        ->columnSpan(['default' => 1, 'sm' => 2, 'xl' => 2]),

                                    FileUpload::make('profile_photo_path')
                                        ->label('Update Profile Photo')
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
                                        ->helperText('Supported formats: PNG, JPG (Max 3MB).')
                                        ->columnSpan(['default' => 1, 'sm' => 2, 'xl' => 1]),
                                ])
                                ->columns(['default' => 1, 'sm' => 2, 'xl' => 3]),

                            Section::make('Security & Password')
                                ->description('Leave blank to retain your existing login password. Minimum 8 characters.')
                                ->schema([
                                    TextInput::make('password')
                                        ->label('New Password')
                                        ->password()
                                        ->revealable()
                                        ->confirmed()
                                        ->minLength(8)
                                        ->prefixIcon(Heroicon::LockClosed)
                                        ->placeholder('Enter new password (min. 8 characters)')
                                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                                        ->dehydrated(fn (?string $state): bool => filled($state)),

                                    TextInput::make('password_confirmation')
                                        ->label('Confirm New Password')
                                        ->password()
                                        ->revealable()
                                        ->prefixIcon(Heroicon::ShieldCheck)
                                        ->placeholder('Confirm your new password')
                                        ->dehydrated(false),
                                ])
                                ->columns(['default' => 1, 'sm' => 2]),
                        ]),

                    Tabs\Tab::make('Basic Info')
                        ->icon(Heroicon::Identification)
                        ->badge(fn (): ?string => $this->profileCompletionBadge('basic'))
                        ->badgeColor('warning')
                        ->schema([
                            Section::make('Personal Details & Permanent Address')
                                ->description('Keep your personal details, emergency contact, and residence records current.')
                                ->schema([
                                    Fieldset::make('Official Legal Name')
                                        ->schema([
                                            TextInput::make('firstname')
                                                ->label('First Name')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->prefixIcon(Heroicon::User),

                                            TextInput::make('middlename')
                                                ->label('Middle Name')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->prefixIcon(Heroicon::User),

                                            TextInput::make('lastname')
                                                ->label('Last Name')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->prefixIcon(Heroicon::User),
                                        ])
                                        ->columns(['default' => 1, 'sm' => 3]),

                                    Fieldset::make('Demographics & Dependents')
                                        ->schema([
                                            DatePicker::make('birthdate')
                                                ->label('Date of Birth')
                                                ->required()
                                                ->prefixIcon(Heroicon::Calendar),

                                            TextInput::make('gender')
                                                ->label('Gender')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->prefixIcon(Heroicon::UserGroup),

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
                                                ->required()
                                                ->prefixIcon(Heroicon::Heart),

                                            TextInput::make('kids')
                                                ->label('No. of Kids / Dependents')
                                                ->numeric()
                                                ->placeholder('0')
                                                ->prefixIcon(Heroicon::UserPlus),
                                        ])
                                        ->columns(['default' => 1, 'sm' => 2, 'xl' => 4]),

                                    Fieldset::make('Contact & Permanent Residence')
                                        ->schema([
                                            TextInput::make('mobile')
                                                ->label('Mobile Number')
                                                ->placeholder('e.g. 09123456789')
                                                ->prefixIcon(Heroicon::Phone)
                                                ->columnSpan(['default' => 1, 'lg' => 1]),

                                            Textarea::make('address')
                                                ->label('Permanent Address')
                                                ->required()
                                                ->rows(2)
                                                ->placeholder('House / Unit No., Street, Barangay, City, Province')
                                                ->columnSpan(['default' => 1, 'lg' => 2]),
                                        ])
                                        ->columns(['default' => 1, 'lg' => 3]),
                                ]),
                        ]),

                    Tabs\Tab::make('Designation')
                        ->icon(Heroicon::Briefcase)
                        ->badge(fn (): ?string => $this->profileCompletionBadge('designation'))
                        ->badgeColor('warning')
                        ->schema([
                            Section::make('Employment & Company Designation')
                                ->description('Official company role, organizational placement, and service record.')
                                ->schema([
                                    Fieldset::make('Organizational Placement')
                                        ->schema([
                                            TextInput::make('designation')
                                                ->label('Job Designation / Role')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->prefixIcon(Heroicon::Briefcase),

                                            TextInput::make('department')
                                                ->label('Department')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->prefixIcon(Heroicon::BuildingOffice2),

                                            TextInput::make('branch')
                                                ->label('Branch / Location')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->prefixIcon(Heroicon::BuildingStorefront),
                                        ])
                                        ->columns(['default' => 1, 'sm' => 3]),

                                    Fieldset::make('Employment Records & Biometrics')
                                        ->schema([
                                            TextInput::make('employment_type')
                                                ->label('Employment Status')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->prefixIcon(Heroicon::CheckBadge),

                                            DatePicker::make('hired_date')
                                                ->label('Date Hired')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->prefixIcon(Heroicon::CalendarDays),

                                            TextInput::make('tenure')
                                                ->label('Length of Service')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->prefixIcon(Heroicon::Clock),

                                            TextInput::make('fingerprint_id')
                                                ->label('Biometric / Fingerprint ID')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->prefixIcon(Heroicon::FingerPrint),
                                        ])
                                        ->columns(['default' => 1, 'sm' => 2, 'xl' => 4]),
                                ]),
                        ]),

                    Tabs\Tab::make("Government ID's")
                        ->icon(Heroicon::BuildingLibrary)
                        ->schema([
                            Section::make('Statutory & Government Identification Numbers')
                                ->description('Mandatory Philippine statutory numbers and government fund IDs.')
                                ->schema([
                                    Fieldset::make('Primary Statutory Agency Numbers')
                                        ->schema([
                                            TextInput::make('sss')
                                                ->label('SSS Number')
                                                ->placeholder('e.g., 33-1234567-8')
                                                ->prefixIcon(Heroicon::CreditCard)
                                                ->helperText('Social Security System'),

                                            TextInput::make('philhealth')
                                                ->label('PhilHealth ID Number')
                                                ->placeholder('e.g., 01-234567890-1')
                                                ->prefixIcon(Heroicon::Heart)
                                                ->helperText('Philippine Health Insurance Corp.'),

                                            TextInput::make('pagibig')
                                                ->label('Pag-IBIG / HDMF MID')
                                                ->placeholder('e.g., 1234-5678-9012')
                                                ->prefixIcon(Heroicon::Home)
                                                ->helperText('Home Development Mutual Fund'),

                                            TextInput::make('tin')
                                                ->label('TIN (Tax Identification No.)')
                                                ->placeholder('e.g., 000-000-000')
                                                ->prefixIcon(Heroicon::DocumentText)
                                                ->helperText('Bureau of Internal Revenue'),
                                        ])
                                        ->columns(['default' => 1, 'sm' => 2, 'xl' => 4]),

                                    Fieldset::make('Additional Government Agency')
                                        ->schema([
                                            TextInput::make('gsis')
                                                ->label('GSIS BP Number (Optional)')
                                                ->placeholder('e.g., 20012345678')
                                                ->prefixIcon(Heroicon::BuildingLibrary)
                                                ->helperText('Government Service Insurance System (if applicable)'),
                                        ])
                                        ->columns(1),
                                ]),
                        ]),

                    Tabs\Tab::make('Education')
                        ->icon(Heroicon::AcademicCap)
                        ->badge(fn (): ?string => $this->profileCompletionBadge('education'))
                        ->badgeColor('warning')
                        ->schema([
                            Section::make('Educational Attainment')
                                ->description('Highest educational level attended and graduation year.')
                                ->schema([
                                    TextInput::make('school_name')
                                        ->label('School / University / College Name')
                                        ->placeholder('e.g., University of Southeastern Philippines')
                                        ->prefixIcon(Heroicon::AcademicCap)
                                        ->columnSpan(['default' => 1, 'sm' => 2, 'lg' => 2]),

                                    Select::make('school_level')
                                        ->label('Highest Level Attended')
                                        ->options([
                                            'Elementary' => 'Elementary',
                                            'Secondary' => 'Secondary (High School)',
                                            'Vocational' => 'Vocational / Trade Course',
                                            'College' => 'College (Tertiary)',
                                            'Graduate Studies' => 'Graduate Studies',
                                        ])
                                        ->prefixIcon(Heroicon::Bookmark)
                                        ->columnSpan(['default' => 1, 'sm' => 1, 'lg' => 1]),

                                    DatePicker::make('year_grad')
                                        ->label('Year / Date Graduated')
                                        ->prefixIcon(Heroicon::Calendar)
                                        ->columnSpan(['default' => 1, 'sm' => 1, 'lg' => 1]),
                                ])
                                ->columns(['default' => 1, 'sm' => 2, 'lg' => 4]),
                        ]),

                    Tabs\Tab::make('Deductions')
                        ->icon(Heroicon::Calculator)
                        ->schema([
                            View::make('filament.employee.pages.partials.deductions-tab')
                                ->columnSpanFull(),
                        ]),

                    Tabs\Tab::make('My Salary')
                        ->icon(Heroicon::Banknotes)
                        ->badge(fn (): ?string => $this->profileCompletionBadge('salary'))
                        ->badgeColor('warning')
                        ->schema([
                            View::make('filament.employee.pages.partials.salary-cards')
                                ->columnSpanFull(),

                            Section::make('Salary Structure & Rate Records')
                                ->description('Official compensation package and payment channel recorded by HR.')
                                ->schema([
                                    TextInput::make('rate_type')
                                        ->label('Rate Basis')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->prefixIcon(Heroicon::Tag),

                                    TextInput::make('daily_rate')
                                        ->label('Daily Rate')
                                        ->prefix('PHP')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->prefixIcon(Heroicon::CurrencyDollar),

                                    TextInput::make('monthly_rate')
                                        ->label('Monthly Basic Rate')
                                        ->prefix('PHP')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->prefixIcon(Heroicon::CurrencyDollar),

                                    TextInput::make('allowance')
                                        ->label('Monthly Allowance')
                                        ->prefix('PHP')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->prefixIcon(Heroicon::Gift),

                                    TextInput::make('payment_type')
                                        ->label('Payment Channel')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->prefixIcon(Heroicon::CreditCard),
                                ])
                                ->columns(['default' => 1, 'sm' => 2, 'xl' => 3]),
                        ]),
                ])
                ->persistTabInQueryString()
                ->columnSpanFull(),
        ];
    }

    protected function profileCompletionBadge(string $section): ?string
    {
        $count = app(EmployeeProfileCompletionService::class)
            ->section($this->employee(), $section);

        return $count > 0 ? (string) $count : null;
    }

    public function save(): void
    {
        $data = $this->form->getState();

        auth()->user()->update(collect($data)
            ->only(['email', 'password', 'profile_photo_path'])
            ->filter(fn (mixed $value): bool => filled($value))
            ->all());

        $payload = collect($data)->only([
            'birthdate', 'status', 'mobile', 'kids', 'address', 'gsis', 'philhealth', 'pagibig', 'tin', 'sss',
            'school_name', 'school_level', 'year_grad',
        ])->all();

        foreach (['gsis', 'philhealth', 'pagibig', 'tin', 'sss'] as $field) {
            $payload[$field] = $payload[$field] ?? '';
        }

        $this->employee()->update($payload);

        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }

    protected function getDeductionState(Employee $employee): array
    {
        $this->ensureDefaultDeductions();

        $saved = $employee->employeeDeductions()
            ->pluck('amount', 'deduction_id')
            ->toArray();

        return Deduction::query()
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [(string) $id => $saved[$id] ?? 0])
            ->toArray();
    }

    protected function ensureDefaultDeductions(): void
    {
        foreach (Deduction::defaultTitles() as $title) {
            Deduction::query()->firstOrCreate(
                ['title' => $title],
                ['description' => $title],
            );
        }
    }

    protected function employee(): Employee
    {
        return auth()->user()->employee()->with(['designation', 'department', 'branch', 'employeeDeductions.deduction'])->firstOrFail();
    }
}
