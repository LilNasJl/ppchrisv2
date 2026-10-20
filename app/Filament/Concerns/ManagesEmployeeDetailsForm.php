<?php

namespace App\Filament\Concerns;

use App\Models\Deduction;
use App\Models\Employee as ModelsEmployee;
use App\Models\EmployeeDeduction;
use App\Models\EmployeeLoan;
use App\Models\User;
use App\Services\EmployeeDeductionService;
use App\Services\EmployeeProfileCompletionService;
use App\Services\EmploymentTypeChangeService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

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

    protected function dailyRateFromMonthly(mixed $monthlyRate): ?float
    {
        $monthlyRate = filled($monthlyRate) ? (float) $monthlyRate : 0.0;

        return $monthlyRate > 0
            ? round($monthlyRate / ModelsEmployee::REGULAR_WORK_DAYS_PER_MONTH, 2)
            : null;
    }

    protected function normalizeSalaryData(array $data): array
    {
        $data['rate_type'] = ($data['rate_type'] ?? null) === 'daily' ? 'daily' : 'monthly';

        if ($data['rate_type'] === 'monthly') {
            $data['daily_rate'] = $this->dailyRateFromMonthly($data['monthly_rate'] ?? null);

            return $data;
        }

        $data['monthly_rate'] = null;

        return $data;
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

    protected function employmentTypeChangeSummary(?ModelsEmployee $record): HtmlString
    {
        if (! $record) {
            return new HtmlString('<div style="color:#64748b;">No employee selected.</div>');
        }

        $change = $record->employmentTypeChanges()
            ->with('changedBy')
            ->first();

        if (! $change) {
            return new HtmlString(
                '<div style="color:#64748b;font-size:13px;">No dated employment type change has been recorded yet.</div>'
            );
        }

        $changedBy = $change->changedBy?->username ?: $change->changedBy?->name ?: 'System';

        return new HtmlString(
            '<div style="display:grid;gap:5px;padding:12px;border:1px solid rgba(148,163,184,.28);border-radius:8px;">'
            .'<div style="font-size:12px;color:#64748b;">Latest employment type change</div>'
            .'<div style="font-weight:700;">Effective '.e($change->effective_date?->format('M d, Y') ?? '-').'</div>'
            .'<div style="font-size:13px;color:#64748b;">'.nl2br(e($change->explanation ?: 'No explanation provided.')).'</div>'
            .'<div style="font-size:12px;color:#94a3b8;">Recorded by '.e($changedBy).'</div>'
            .'</div>'
        );
    }

    protected function employmentTypeChangeAction(): Action
    {
        return Action::make('changeEmploymentType')
            ->label('Change Employment Type')
            ->icon(Heroicon::ArrowsRightLeft)
            ->color('primary')
            ->modalHeading('Change Employment Type')
            ->modalDescription('Select the new employment type and record when and why the change took effect.')
            ->modalSubmitActionLabel('Save Change')
            ->fillForm(function (): array {
                $latestChange = $this->employeeRecord?->employmentTypeChanges()->first();

                return [
                    'employment_type' => $this->employeeRecord?->employment_type,
                    'effective_date' => $latestChange?->effective_date?->format('Y-m-d') ?? now()->toDateString(),
                    'explanation' => $latestChange?->employment_type === $this->employeeRecord?->employment_type
                        ? $latestChange?->explanation
                        : null,
                ];
            })
            ->schema([
                Select::make('employment_type')
                    ->label('Employment Type')
                    ->options(ModelsEmployee::employmentTypeOptions())
                    ->searchable()
                    ->required(),

                DatePicker::make('effective_date')
                    ->label('Effective Date')
                    ->maxDate(now())
                    ->native(false)
                    ->required(),

                Textarea::make('explanation')
                    ->label('Explanation')
                    ->placeholder('Explain the reason or circumstances for this employment type change.')
                    ->rows(5)
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                if (! $this->employeeRecord) {
                    return;
                }

                try {
                    app(EmploymentTypeChangeService::class)->save(
                        employee: $this->employeeRecord,
                        employmentType: $data['employment_type'],
                        effectiveDate: $data['effective_date'],
                        explanation: $data['explanation'] ?? null,
                        changedBy: auth()->user(),
                    );
                } catch (InvalidArgumentException $exception) {
                    Notification::make()
                        ->title('Employment type was not updated')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    throw new Halt;
                }

                $this->employeeRecord->refresh();
                $this->employeeRecord->unsetRelation('employmentTypeChanges');
                $this->data['employment_type'] = $this->employeeRecord->employment_type;

                Notification::make()
                    ->title('Employment type updated')
                    ->body('The effective date and employment type details were saved in the employee history.')
                    ->success()
                    ->send();
            });
    }

    protected function employmentTypeHistoryAction(): Action
    {
        return Action::make('employmentTypeHistory')
            ->label('Employment History')
            ->icon(Heroicon::Clock)
            ->modalHeading(fn (): string => ($this->employeeRecord?->full_name ?? 'Employee').' Employment History')
            ->modalWidth('5xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(fn () => view('filament.pages.partials.employment-type-history', [
                'changes' => $this->employeeRecord
                    ? $this->employeeRecord->employmentTypeChanges()->with('changedBy')->get()
                    : collect(),
            ]));
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
            '<div class="employee-deductions-table" style="overflow:auto;">'
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

    protected function loanSummary(?ModelsEmployee $record): HtmlString
    {
        if (! $record) {
            return new HtmlString('<div style="color:#64748b;">No employee selected.</div>');
        }

        $loans = EmployeeLoan::query()
            ->with('amortizationStartPayrollPeriod')
            ->where('employee_id', $record->id)
            ->latest('loan_date')
            ->latest('id')
            ->get();

        if ($loans->isEmpty()) {
            return new HtmlString('<div style="color:#64748b;">No loans recorded for this employee.</div>');
        }

        $money = fn (mixed $amount): string => number_format((float) $amount, 2);
        $rows = $loans
            ->map(function (EmployeeLoan $loan) use ($money): string {
                return '<tr>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);font-weight:700;">'.e($loan->loan_type).'</td>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);text-align:right;">'.$money($loan->loan_amount).'</td>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);text-align:right;">'.$money($loan->loan_interest).'</td>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);text-align:right;">'.$money($loan->total_amount).'</td>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);text-align:right;">'.$money($loan->payment_amount).'</td>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);text-align:right;">'.$money($loan->balance_amount).'</td>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);">'.e($loan->loan_terms_months.' '.$loan->terms_label).'</td>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);">'.e($loan->schedule).'</td>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);">'.e(optional($loan->loan_date)->format('M d, Y') ?: '-').'</td>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);">'.e($loan->amortizationStartPayrollPeriod?->title ?: '-').'</td>'
                    .'<td style="padding:8px;border-bottom:1px solid rgba(148,163,184,.25);">'.e($loan->status).'</td>'
                    .'</tr>';
            })
            ->implode('');

        return new HtmlString(
            '<div class="employee-loans-table" style="overflow:auto;">'
            .'<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            .'<thead><tr>'
            .'<th style="padding:8px;text-align:left;border-bottom:1px solid rgba(148,163,184,.35);">Loan Type</th>'
            .'<th style="padding:8px;text-align:right;border-bottom:1px solid rgba(148,163,184,.35);">Amount</th>'
            .'<th style="padding:8px;text-align:right;border-bottom:1px solid rgba(148,163,184,.35);">Total Interest</th>'
            .'<th style="padding:8px;text-align:right;border-bottom:1px solid rgba(148,163,184,.35);">Total</th>'
            .'<th style="padding:8px;text-align:right;border-bottom:1px solid rgba(148,163,184,.35);">Payment</th>'
            .'<th style="padding:8px;text-align:right;border-bottom:1px solid rgba(148,163,184,.35);">Balance</th>'
            .'<th style="padding:8px;text-align:left;border-bottom:1px solid rgba(148,163,184,.35);">Terms</th>'
            .'<th style="padding:8px;text-align:left;border-bottom:1px solid rgba(148,163,184,.35);">Schedule</th>'
            .'<th style="padding:8px;text-align:left;border-bottom:1px solid rgba(148,163,184,.35);">Loan Date</th>'
            .'<th style="padding:8px;text-align:left;border-bottom:1px solid rgba(148,163,184,.35);">Amortization Start</th>'
            .'<th style="padding:8px;text-align:left;border-bottom:1px solid rgba(148,163,184,.35);">Status</th>'
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
        $data['kids'] = $record->kids ?? 0;
        $data['tenure'] = $record->tenure;
        $data['profile_photo_path'] = $record->user?->profile_photo_path;
        $data['account_username'] = $record->user?->username;
        $data['account_password'] = null;
        $data['schedule_type'] = $this->scheduleTypeForRateType($record->rate_type);

        return $data;
    }

    protected function getEmployeeDetailsFormSchema(bool $isReadOnly = false): array
    {
        return [
            Tabs::make('Employee Details')
                ->tabs([
                    Tabs\Tab::make('Account')
                        ->icon(Heroicon::UserCircle)
                        ->columns($isReadOnly ? ['default' => 1, 'xl' => 2] : ['default' => 1, 'xl' => 3])
                        ->schema([
                            Section::make('Profile')
                                ->contained(false)
                                ->schema([
                                    Placeholder::make('profile_card')
                                        ->hiddenLabel()
                                        ->content(fn (?ModelsEmployee $record = null): HtmlString => $this->profilePreview($record ?? ($this->employeeRecord ?? null)))
                                        ->columnSpanFull(),

                                    FileUpload::make('profile_photo_path')
                                        ->label('Profile Picture')
                                        ->hidden($isReadOnly)
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
                                ])
                                ->columnSpan(1),

                            Section::make('Account access')
                                ->contained(false)
                                ->schema([
                                    TextInput::make('account_username')
                                        ->label('Username')
                                        ->readOnly()
                                        ->dehydrated(false)
                                        ->helperText('Automatically generated from the employee ID.'),

                                    TextInput::make('account_password')
                                        ->label('New Password')
                                        ->hidden($isReadOnly)
                                        ->password()
                                        ->revealable()
                                        ->dehydrated(fn (?string $state): bool => filled($state))
                                        ->helperText('Leave empty to keep the current password.'),

                                    Placeholder::make('account_status')
                                        ->label('Account Status')
                                        ->content(fn (?ModelsEmployee $record = null): HtmlString => new HtmlString(match (true) {
                                            $record?->user === null => '<span class="employee-account-status is-missing">No account</span>',
                                            $record->user->is_disabled => '<span class="employee-account-status is-disabled">Disabled</span>',
                                            default => '<span class="employee-account-status is-enabled">Enabled</span>',
                                        })),
                                ])
                                ->columns($isReadOnly ? ['default' => 1, 'md' => 2] : 1)
                                ->columnSpan($isReadOnly ? 1 : ['default' => 1, 'xl' => 2]),
                        ]),

                    Tabs\Tab::make('Basic Info')
                        ->icon('heroicon-s-user')
                        ->badge(fn (): ?string => $this->profileCompletionBadge('basic'))
                        ->badgeColor('warning')
                        ->schema([
                            Section::make('Identity')
                                ->contained(false)
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

                            Section::make('Personal and contact')
                                ->contained(false)
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

                            Section::make('Government and bank IDs')
                                ->contained(false)
                                ->schema([
                                    TextInput::make('gsis')
                                        ->label('GSIS BP #')
                                        ->maxLength(20),

                                    TextInput::make('philhealth')
                                        ->label('PhilHealth ID #')
                                        ->maxLength(20),

                                    TextInput::make('pagibig')
                                        ->label('Pag-IBIG MID #')
                                        ->maxLength(20),

                                    TextInput::make('tin')
                                        ->label('TIN #')
                                        ->maxLength(20),

                                    TextInput::make('sss')
                                        ->label('SSS #')
                                        ->maxLength(20),

                                    TextInput::make('bank_id_no')
                                        ->label('Bank ID No.')
                                        ->maxLength(191),
                                ])
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ]),
                        ]),

                    Tabs\Tab::make('Designation')
                        ->icon('heroicon-s-briefcase')
                        ->badge(fn (): ?string => $this->profileCompletionBadge('designation'))
                        ->badgeColor('warning')
                        ->schema([
                            Section::make('Position and assignment')
                                ->contained(false)
                                ->schema([
                                    TextInput::make('fingerprint_id')
                                        ->label('Fingerprint ID')
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
                                ])
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ]),

                            Section::make('Employment')
                                ->contained(false)
                                ->schema([
                                    DatePicker::make('hired_date')
                                        ->label('Hired Date')
                                        ->required(),

                                    TextInput::make('tenure')
                                        ->label('Tenure')
                                        ->disabled()
                                        ->dehydrated(false),

                                    Select::make('employment_type')
                                        ->label('Employment Type')
                                        ->options(ModelsEmployee::employmentTypeOptions())
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->helperText($isReadOnly
                                            ? 'Employment type changes include an effective date and explanation.'
                                            : 'Use the Change Employment Type action at the top of this page.'),

                                    Placeholder::make('employment_type_change_summary')
                                        ->hiddenLabel()
                                        ->content(fn (?ModelsEmployee $record = null): HtmlString => $this->employmentTypeChangeSummary($record ?? ($this->employeeRecord ?? null)))
                                        ->columnSpanFull(),
                                ])
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ]),
                        ]),

                    Tabs\Tab::make('Education')
                        ->icon(Heroicon::AcademicCap)
                        ->badge(fn (): ?string => $this->profileCompletionBadge('education'))
                        ->badgeColor('warning')
                        ->schema([
                            Section::make('Education background')
                                ->contained(false)
                                ->schema([
                                    TextInput::make('school_name')
                                        ->label('School Name')
                                        ->placeholder('e.g., University of Mindanao')
                                        ->columnSpanFull(),

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
                            Section::make('Active deductions')
                                ->contained(false)
                                ->schema([
                                    Placeholder::make('deduction_summary')
                                        ->hiddenLabel()
                                        ->content(fn (?ModelsEmployee $record = null): HtmlString => $this->deductionSummary($record ?? ($this->employeeRecord ?? null)))
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tabs\Tab::make('Loan Details')
                        ->icon(Heroicon::Banknotes)
                        ->schema([
                            Section::make('Employee loans')
                                ->contained(false)
                                ->schema([
                                    Placeholder::make('loan_summary')
                                        ->hiddenLabel()
                                        ->content(fn (?ModelsEmployee $record = null): HtmlString => $this->loanSummary($record ?? ($this->employeeRecord ?? null)))
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tabs\Tab::make('Leave')
                        ->icon(Heroicon::CalendarDays)
                        ->schema([
                            Section::make('Leave balances')
                                ->contained(false)
                                ->schema([
                                    TextInput::make('leave_credits')
                                        ->label('Leave Count')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->required(),

                                    TextInput::make('birthday_leave_credits')
                                        ->label('Birthday Leave Count')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->required(),

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
                        ->badge(fn (): ?string => $this->profileCompletionBadge('salary'))
                        ->badgeColor('warning')
                        ->schema([
                            Section::make('Compensation and payment')
                                ->contained(false)
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

    protected function profileCompletionBadge(string $section): ?string
    {
        $count = app(EmployeeProfileCompletionService::class)
            ->section($this->employeeRecord ?? null, $section);

        return $count > 0 ? (string) $count : null;
    }

    protected function saveEmployeeDetails(ModelsEmployee $record, array $data): Model
    {
        $profilePhotoPath = $data['profile_photo_path'] ?? null;
        $accountPassword = $data['account_password'] ?? null;

        unset(
            $data['deductions'],
            $data['other_deduction_ids'],
            $data['tenure'],
            $data['profile_photo_path'],
            $data['account_username'],
            $data['account_password'],
            $data['employment_type'],
        );
        $data = $this->normalizeSalaryData($data);
        $data['schedule_type'] = $this->scheduleTypeForRateType($data['rate_type'] ?? $record->rate_type);

        DB::transaction(function () use ($record, $data, $profilePhotoPath, $accountPassword): void {
            foreach (['gsis', 'philhealth', 'pagibig', 'tin', 'sss', 'bank_id_no', 'fingerprint_id'] as $field) {
                $data[$field] = filled($data[$field] ?? null) ? $data[$field] : null;
            }

            $record->update($data);
            $userUpdates = [
                'profile_photo_path' => $profilePhotoPath,
            ];

            $username = User::companyUsernameFromUid($record->uid);

            if (filled($username)) {
                $userUpdates['username'] = $username;
                $userUpdates['name'] = $username;
            }

            if (filled($accountPassword)) {
                $userUpdates['password'] = Hash::make($accountPassword);
            }

            $record->user?->update($userUpdates);

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
