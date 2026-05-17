<?php

namespace App\Filament\Employee\Pages;

use App\Models\Deduction;
use App\Models\Employee;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
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
use Filament\Support\Icons\Heroicon;

class MyProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.employee.pages.my-profile';

    protected static ?string $slug = 'my-profile';

    protected static ?string $title = 'My Info';

    protected static ?string $navigationLabel = 'My Info';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserCircle;

    protected static ?int $navigationSort = 2;

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

    public function mount(): void
    {
        $employee = $this->employee();

        $this->form->fill([
            ...$employee->only([
                'lastname', 'middlename', 'firstname', 'birthdate', 'status', 'gender',
                'fingerprint_id', 'hired_date', 'employment_type', 'mobile', 'kids',
                'address', 'gsis', 'philhealth', 'pagibig', 'tin', 'sss',
                'school_name', 'school_level', 'year_grad', 'rate_type', 'payment_type',
                'daily_rate', 'monthly_rate', 'allowance',
            ]),
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
                    Tabs\Tab::make('Basic Info')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('lastname')->disabled()->dehydrated(false),
                                    TextInput::make('middlename')->disabled()->dehydrated(false),
                                    TextInput::make('firstname')->disabled()->dehydrated(false),
                                    DatePicker::make('birthdate')
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
                                    TextInput::make('gender')->disabled()->dehydrated(false),
                                    TextInput::make('mobile')->label('Mobile Number')->required(),
                                    TextInput::make('kids')->label('No. of Kids')->numeric(),
                                    Textarea::make('address')->label('Permanent Address')->required()->columnSpanFull(),
                                ])
                                ->columns(3),
                        ]),

                    Tabs\Tab::make('Designation')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('fingerprint_id')->label('Fingerprint ID')->disabled()->dehydrated(false),
                                    TextInput::make('designation')->disabled()->dehydrated(false),
                                    TextInput::make('department')->disabled()->dehydrated(false),
                                    TextInput::make('branch')->disabled()->dehydrated(false),
                                    DatePicker::make('hired_date')->disabled()->dehydrated(false),
                                    TextInput::make('tenure')->label('Employee Tenure')->disabled()->dehydrated(false),
                                    TextInput::make('employment_type')->disabled()->dehydrated(false),
                                ])
                                ->columns(2),
                        ]),

                    Tabs\Tab::make("Government ID's")
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('gsis')
                                        ->label('GSIS BP #')
                                        ->placeholder('e.g., 20012345678'),
                                    TextInput::make('philhealth')
                                        ->label('PhilHealth ID #')
                                        ->placeholder('e.g., 01-234567890-1'),
                                    TextInput::make('pagibig')
                                        ->label('Pag-IBIG MID #')
                                        ->placeholder('e.g., 1234-5678-9012'),
                                    TextInput::make('tin')
                                        ->label('TIN #')
                                        ->placeholder('e.g., 000-000-000'),
                                    TextInput::make('sss')
                                        ->label('SSS #')
                                        ->placeholder('e.g., 33-1234567-8'),
                                ])
                                ->columns(2),
                        ]),

                    Tabs\Tab::make('Education')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('school_name')->label('School Name'),
                                    Select::make('school_level')
                                        ->label('Highest Level Attended')
                                        ->options([
                                            'Elementary' => 'Elementary',
                                            'Secondary' => 'Secondary (High School)',
                                            'Vocational' => 'Vocational / Trade Course',
                                            'College' => 'College (Tertiary)',
                                            'Graduate Studies' => 'Graduate Studies',
                                        ]),
                                    DatePicker::make('year_grad')->label('Year Graduated'),
                                ])
                                ->columns(2),
                        ]),

                    Tabs\Tab::make('Deductions')
                        ->schema([
                            Fieldset::make('Deductions')
                                ->schema(fn (): array => $this->getDeductionFields(self::DEDUCTION_GROUPS['Deductions']))
                                ->columns(2),

                            Fieldset::make('REMITTANCES')
                                ->schema(fn (): array => $this->getDeductionFields(self::DEDUCTION_GROUPS['REMITTANCES']))
                                ->columns(2),

                            Fieldset::make('Other Deductions')
                                ->schema(fn (): array => $this->getOtherDeductionFields())
                                ->columns(2),
                        ]),

                    Tabs\Tab::make('My Salary')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('rate_type')->label('Rate Type')->disabled()->dehydrated(false),
                                    TextInput::make('payment_type')->label('Payment Type')->disabled()->dehydrated(false),
                                    TextInput::make('daily_rate')->label('Daily Rate')->prefix('PHP')->disabled()->dehydrated(false),
                                    TextInput::make('allowance')->label('Monthly Allowance')->prefix('PHP')->disabled()->dehydrated(false),
                                ])
                                ->columns(2),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $payload = collect($data)->only([
            'birthdate', 'status', 'mobile', 'kids', 'address', 'gsis', 'philhealth', 'pagibig', 'tin', 'sss',
            'school_name', 'school_level', 'year_grad',
        ])->all();

        foreach (['gsis', 'philhealth', 'pagibig', 'tin', 'sss'] as $field) {
            $payload[$field] = $payload[$field] ?? '';
        }

        $this->employee()->update($payload);

        Notification::make()
            ->title('Profile updated')
            ->success()
            ->send();
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

    protected function getOtherDeductionFields(): array
    {
        $this->ensureDefaultDeductions();

        $deductionIds = $this->employee()
            ->employeeDeductions()
            ->whereHas('deduction', fn ($query) => $query->whereNotIn('title', Deduction::defaultTitles()))
            ->where('amount', '>', 0)
            ->pluck('deduction_id')
            ->all();

        if (empty($deductionIds)) {
            return [
                Placeholder::make('no_other_deductions')
                    ->hiddenLabel()
                    ->content('No other deductions linked.')
                    ->columnSpanFull(),
            ];
        }

        return Deduction::query()
            ->whereIn('id', $deductionIds)
            ->whereNotIn('title', Deduction::defaultTitles())
            ->orderBy('title')
            ->get()
            ->map(fn (Deduction $deduction) => $this->makeDeductionInput($deduction))
            ->all();
    }

    protected function makeDeductionInput(Deduction $deduction): TextInput
    {
        return TextInput::make("deductions.{$deduction->id}")
            ->label($deduction->title)
            ->prefix('PHP')
            ->disabled()
            ->dehydrated(false)
            ->default(0);
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

    protected function employee(): Employee
    {
        return auth()->user()->employee()->with(['designation', 'department', 'branch', 'employeeDeductions.deduction'])->firstOrFail();
    }
}
