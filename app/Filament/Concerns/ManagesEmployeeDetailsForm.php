<?php

namespace App\Filament\Concerns;

use App\Models\Deduction;
use App\Models\Employee as ModelsEmployee;
use App\Models\EmployeeDeduction;
use App\Services\EmployeeDeductionService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

trait ManagesEmployeeDetailsForm
{
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
                [
                    'description' => $title,
                    'category' => Deduction::categoryForTitle($title),
                    'term_type' => Deduction::TERM_PERMANENT,
                ],
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

        return new HtmlString(
            '<div style="display: grid; place-items: center; gap: 10px; padding: 12px;">'
            .$avatar
            .'<div style="text-align: center;"><div style="font-weight: 800;">'.$name.'</div>'
            .'<div style="color: #94a3b8; font-size: 13px;">'.e($age).'</div></div>'
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

    protected function deductionSummary(?ModelsEmployee $record): HtmlString
    {
        if (! $record) {
            return new HtmlString('<div style="color:#64748b;">No employee selected.</div>');
        }

        $deductions = app(EmployeeDeductionService::class)->activeEmployeeDeductions($record);

        if ($deductions->isEmpty()) {
            return new HtmlString('<div style="color:#64748b;">No deductions linked to this employee.</div>');
        }

        $rows = $deductions
            ->sortBy(fn (EmployeeDeduction $employeeDeduction): string => ($employeeDeduction->deduction?->category ?? '').($employeeDeduction->deduction?->title ?? ''))
            ->map(function (EmployeeDeduction $employeeDeduction): string {
                $deduction = $employeeDeduction->deduction;
                $category = Deduction::categoryOptions()[$deduction?->category ?? Deduction::CATEGORY_OTHER] ?? 'Other Deductions';
                $term = $employeeDeduction->term_type === Deduction::TERM_FIXED
                    ? "{$employeeDeduction->remaining_terms} of {$employeeDeduction->term_periods} payroll period(s) remaining"
                    : 'Permanent';

                return '<tr>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);">'.e($category).'</td>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);font-weight:700;">'.e($deduction?->title ?? 'Unknown').'</td>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);">'.e($deduction?->description ?: '-').'</td>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);text-align:right;">'.number_format((float) $employeeDeduction->amount, 2).'</td>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);">'.e($term).'</td>'
                    .'</tr>';
            })
            ->implode('');

        return new HtmlString(
            '<div style="overflow:auto;">'
            .'<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            .'<thead><tr>'
            .'<th style="padding:8px;text-align:left;border-bottom:1px solid rgba(148,163,184,.35);">Group</th>'
            .'<th style="padding:8px;text-align:left;border-bottom:1px solid rgba(148,163,184,.35);">Deduction</th>'
            .'<th style="padding:8px;text-align:left;border-bottom:1px solid rgba(148,163,184,.35);">Description</th>'
            .'<th style="padding:8px;text-align:right;border-bottom:1px solid rgba(148,163,184,.35);">Amount</th>'
            .'<th style="padding:8px;text-align:left;border-bottom:1px solid rgba(148,163,184,.35);">Terms</th>'
            .'</tr></thead><tbody>'.$rows.'</tbody></table></div>'
        );
    }

    protected function getEmployeeDetailsFormData(ModelsEmployee $record): array
    {
        $record->resetLeaveCreditsIfNeeded();
        $record->refresh();

        $data = $record->attributesToArray();
        $data['leave_credits'] = $record->leave_credits;
        $data['birthday_leave_credits'] = $record->birthday_leave_credits;
        $data['leave_credits_year'] = $record->leave_credits_year;
        $data['allowance'] = $record->allowance ?? 0;
        $data['salary_adjustment'] = $record->salary_adjustment ?? 0;
        $data['kids'] = $record->kids ?? 0;
        $data['tenure'] = $record->tenure;
        $data['profile_photo_path'] = $record->user?->profile_photo_path;
        $data['schedule_type'] = $this->scheduleTypeForRateType($record->rate_type);

        return $data;
    }

    protected function getEmployeeDetailsFormSchema(bool $isReadOnly = false): array
    {
        return [
            Tabs::make('Employee Details')
                ->tabs([
                    Tabs\Tab::make('Profile Picture')
                        ->icon(Heroicon::UserCircle)
                        ->schema([
                            Placeholder::make('profile_card')
                                ->hiddenLabel()
                                ->content(fn (?ModelsEmployee $record = null): HtmlString => $this->profilePreview($record ?? ($this->employeeRecord ?? null)))
                                ->columnSpanFull(),

                            FileUpload::make('profile_photo_path')
                                ->label('Profile Picture')
                                ->image()
                                ->previewable()
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
                                ->columns([
                                    'default' => 1,
                                    'md' => 3,
                                ]),

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
                                ->columns([
                                    'default' => 1,
                                    'md' => 3,
                                ]),

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
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ]),
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
                        ->columns([
                            'default' => 1,
                            'md' => 2,
                        ]),

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
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ]),
                        ]),

                    Tabs\Tab::make('Deductions')
                        ->icon(Heroicon::MinusCircle)
                        ->schema([
                            Placeholder::make('deduction_summary')
                                ->hiddenLabel()
                                ->content(fn (?ModelsEmployee $record = null): HtmlString => $this->deductionSummary($record ?? ($this->employeeRecord ?? null)))
                                ->columnSpanFull(),
                        ]),

                    Tabs\Tab::make('Leave')
                        ->icon(Heroicon::CalendarDays)
                        ->schema([
                            Group::make()
                                ->schema([
                                    TextInput::make('leave_credits')
                                        ->label('Leave Count')
                                        ->disabled()
                                        ->dehydrated(false),

                                    TextInput::make('birthday_leave_credits')
                                        ->label('Birthday Leave Count')
                                        ->disabled()
                                        ->dehydrated(false),

                                    TextInput::make('leave_credits_year')
                                        ->label('Leave Year')
                                        ->disabled()
                                        ->dehydrated(false),
                                ])
                                ->columns([
                                    'default' => 1,
                                    'md' => 3,
                                ]),
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
                                        ->numeric()
                                        ->prefix('₱')
                                        ->required(),

                                    TextInput::make('allowance')
                                        ->label('Monthly Allowance')
                                        ->numeric()
                                        ->prefix('₱')
                                        ->dehydrateStateUsing(fn ($state) => $state ?? 0)
                                        ->default(0),

                                    TextInput::make('salary_adjustment')
                                        ->label('Salary Adjustment')
                                        ->numeric()
                                        ->prefix('₱')
                                        ->dehydrateStateUsing(fn ($state) => $state ?? 0)
                                        ->default(0),
                                ])
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ]),
                        ]),
                ])
                ->disabled($isReadOnly)
                ->columnSpanFull(),
        ];
    }

    protected function saveEmployeeDetails(ModelsEmployee $record, array $data): Model
    {
        $profilePhotoPath = $data['profile_photo_path'] ?? null;

        unset($data['deductions'], $data['other_deduction_ids'], $data['tenure'], $data['profile_photo_path']);
        $data['schedule_type'] = $this->scheduleTypeForRateType($data['rate_type'] ?? $record->rate_type);

        DB::transaction(function () use ($record, $data, $profilePhotoPath): void {
            foreach (['gsis', 'philhealth', 'pagibig', 'tin', 'sss', 'bank_id_no', 'fingerprint_id'] as $field) {
                $data[$field] = filled($data[$field] ?? null) ? $data[$field] : null;
            }

            $record->update($data);
            $record->user?->update([
                'profile_photo_path' => $profilePhotoPath,
            ]);

        });

        return $record->refresh();
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
