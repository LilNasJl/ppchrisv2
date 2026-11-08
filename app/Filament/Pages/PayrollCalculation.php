<?php

namespace App\Filament\Pages;

use App\Models\PayrollCalculationSetting;
use App\Models\PayrollPeriod;
use App\Services\AccountLogService;
use App\Services\DtrDailyAggregationService;
use App\Services\PayrollCalculator;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Override;
use UnitEnum;

class PayrollCalculation extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.pages.payroll-calculation';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Payroll Management';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Period Calculation Rules';

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'hr'], true);
    }

    #[Override]
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public int|string|null $periodId = null;

    public ?PayrollPeriod $period = null;

    public ?string $period_display = null;

    public ?string $regular_work_days_per_month = null;

    public ?string $regular_half_month_days = null;

    public ?string $work_hours_per_day = null;

    public ?string $late_grace_minutes = null;

    public ?string $half_day_work_day_value = null;

    public ?string $overtime_rate_multiplier = null;

    public ?string $regular_holiday_rate = null;

    public ?string $special_holiday_rate = null;

    public ?string $holiday_overtime_premium_rate = null;

    public ?bool $unworked_regular_holiday_pay_enabled = true;

    public function mount(): void
    {
        $requestedPeriodId = PayrollPeriod::resolvePublicId($this->periodId ?: request()->query('periodId'));
        $defaultPeriodId = app(PayrollCalculator::class)->defaultPeriod()?->id;

        $resolved = $requestedPeriodId ?: $defaultPeriodId;
        $this->periodId = $resolved ? (int) $resolved : null;
        $this->period = filled($this->periodId) ? PayrollPeriod::query()->find($this->periodId) : null;
        $this->period_display = $this->period?->title ?? 'No payroll period selected';

        $this->fillFormWithSettings();
    }

    public function updatedPeriodId(mixed $value): void
    {
        $resolvedId = PayrollPeriod::resolvePublicId($value);
        $this->periodId = $resolvedId ? (int) $resolvedId : null;
        $this->period = filled($this->periodId) ? PayrollPeriod::query()->find($this->periodId) : null;
        $this->period_display = $this->period?->title ?? 'No payroll period selected';

        $this->fillFormWithSettings();
    }

    public function fillFormWithSettings(): void
    {
        $setting = $this->currentSettings();

        $this->form->fill([
            'regular_work_days_per_month' => $setting->regular_work_days_per_month,
            'regular_half_month_days' => $setting->regular_half_month_days,
            'work_hours_per_day' => $setting->work_hours_per_day,
            'late_grace_minutes' => $setting->late_grace_minutes,
            'half_day_work_day_value' => $setting->half_day_work_day_value,
            'overtime_rate_multiplier' => $setting->overtime_rate_multiplier,
            'regular_holiday_rate' => $setting->regular_holiday_rate,
            'special_holiday_rate' => $setting->special_holiday_rate,
            'holiday_overtime_premium_rate' => $setting->holiday_overtime_premium_rate,
            'unworked_regular_holiday_pay_enabled' => $setting->unworked_regular_holiday_pay_enabled,
        ]);
    }

    /**
     * @return Collection<int, PayrollPeriod>
     */
    public function payrollPeriods(): Collection
    {
        return PayrollPeriod::query()
            ->newestFirst()
            ->get();
    }

    public function currentSettings(): PayrollCalculationSetting
    {
        return $this->period
            ? PayrollCalculationSetting::forPeriod($this->period)
            : new PayrollCalculationSetting(PayrollCalculationSetting::DEFAULTS);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Workday & Daily Rate Baselines')
                ->description('Divisors used to convert monthly salaries to daily rates and establish standard cutoff payable days.')
                ->icon(Heroicon::CalendarDays)
                ->schema([
                    TextInput::make('regular_work_days_per_month')
                        ->label('Regular Work Days / Month')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(31)
                        ->suffix('Days')
                        ->helperText('Divisor for converting monthly salary to daily rate: Monthly Rate / Work Days.'),

                    TextInput::make('regular_half_month_days')
                        ->label('Regular Half-Month Days')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->maxValue(31)
                        ->suffix('Days')
                        ->helperText('Base payable days for regular monthly employees in half-month cutoff.'),

                    TextInput::make('work_hours_per_day')
                        ->label('Work Hours / Day')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(24)
                        ->suffix('Hours')
                        ->helperText('Standard daily hours divisor: Rate Per Day / Work Hours.'),
                ])
                ->columns(3),

            Section::make('Attendance & Overtime Rules')
                ->description('Thresholds for attendance deductions, half-day calculations, and overtime multipliers.')
                ->icon(Heroicon::Clock)
                ->schema([
                    TextInput::make('late_grace_minutes')
                        ->label('Late Grace (Minutes)')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(0)
                        ->maxValue(60)
                        ->suffix('Mins')
                        ->helperText('Arrivals within this grace period are not penalized as late.'),

                    TextInput::make('half_day_work_day_value')
                        ->label('Half-Day Value')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->maxValue(1)
                        ->suffix('Day')
                        ->helperText('Example: 0.50 means half day deducts half of one daily rate.'),

                    TextInput::make('overtime_rate_multiplier')
                        ->label('Overtime Multiplier')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->maxValue(5)
                        ->suffix('x Rate')
                        ->helperText('Multiplier applied to normal hourly rate for credited overtime (e.g. 1.00).'),
                ])
                ->columns(3),

            Section::make('Holiday Compensation Policies')
                ->description('Default premium percentages and holiday compensation rules applied across this period.')
                ->icon(Heroicon::Sparkles)
                ->schema([
                    TextInput::make('regular_holiday_rate')
                        ->label('Default Regular Holiday Rate (%)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->suffix('%')
                        ->helperText('Applied when the D.T.R row has no stored holiday rate (e.g. 200%).'),

                    TextInput::make('special_holiday_rate')
                        ->label('Default Special Holiday Rate (%)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->suffix('%')
                        ->helperText('Applied when the D.T.R row has no stored holiday rate (e.g. 30%).'),

                    TextInput::make('holiday_overtime_premium_rate')
                        ->label('Holiday Overtime Premium (%)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->suffix('%')
                        ->helperText('Applied to credited overtime minutes on holidays (e.g. 30%).'),

                    Toggle::make('unworked_regular_holiday_pay_enabled')
                        ->label('Pay Unworked Regular Holidays for Daily Employees')
                        ->helperText('Monthly employees are covered by fixed half-month base pay. When enabled, daily employees with no punch receive 1 daily rate on regular holidays.')
                        ->columnSpanFull(),
                ])
                ->columns(3),
        ];
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $resolvedPeriodId = PayrollPeriod::resolvePublicId($this->periodId);
        $this->period = filled($resolvedPeriodId) ? PayrollPeriod::query()->find($resolvedPeriodId) : null;
        if (! $this->period) {
            Notification::make()
                ->title('No payroll period selected')
                ->danger()
                ->send();

            return;
        }

        if ((bool) $this->period->is_locked) {
            Notification::make()
                ->title('Payroll period is locked')
                ->body('Calculation settings cannot be changed for locked payroll periods.')
                ->danger()
                ->send();

            return;
        }

        $state = collect($this->form->getState())->only([
            'regular_work_days_per_month', 'regular_half_month_days', 'work_hours_per_day',
            'late_grace_minutes', 'half_day_work_day_value', 'overtime_rate_multiplier',
            'regular_holiday_rate', 'special_holiday_rate', 'holiday_overtime_premium_rate',
        ]);
        $data = $state
            ->map(fn ($value): float => round((float) $value, 2))
            ->all();
        $data['unworked_regular_holiday_pay_enabled'] = (bool) ($this->form->getState()['unworked_regular_holiday_pay_enabled'] ?? true);
        if ($data['regular_half_month_days'] > $data['regular_work_days_per_month']) {
            throw ValidationException::withMessages(['regular_half_month_days' => 'Cutoff days cannot exceed monthly work days.']);
        }
        DB::transaction(function () use ($data, $resolvedPeriodId): void {
            $period = PayrollPeriod::query()->whereKey($resolvedPeriodId)->lockForUpdate()->firstOrFail();
            if ($period->is_locked) {
                throw ValidationException::withMessages(['regular_work_days_per_month' => 'This payroll period has been locked.']);
            }
            $setting = PayrollCalculationSetting::forPeriod($period);
            $before = $setting->only(array_keys($data));
            $setting->fill($data)->save();
            app(DtrDailyAggregationService::class)->recalculatePeriod($period->id);
            app(AccountLogService::class)->record('payroll_settings_updated', auth()->user(), auth()->user(), 'hr');
            \Illuminate\Support\Facades\Log::info('Payroll settings changed', [
                'period_id' => $period->id, 'actor_id' => auth()->id(), 'before' => $before, 'after' => $data,
            ]);
        });

        Notification::make()
            ->title('Period calculation rules saved')
            ->body('Calculation parameters updated and attendance records recalculated for this period.')
            ->success()
            ->send();
    }

    public function resetToDefaults(): void
    {
        if (! $this->period || $this->period->is_locked) {
            return;
        }

        $defaults = PayrollCalculationSetting::DEFAULTS;
        $this->form->fill([
            'regular_work_days_per_month' => $defaults['regular_work_days_per_month'],
            'regular_half_month_days' => $defaults['regular_half_month_days'],
            'work_hours_per_day' => $defaults['work_hours_per_day'],
            'late_grace_minutes' => $defaults['late_grace_minutes'],
            'half_day_work_day_value' => $defaults['half_day_work_day_value'],
            'overtime_rate_multiplier' => $defaults['overtime_rate_multiplier'],
            'regular_holiday_rate' => $defaults['regular_holiday_rate'],
            'special_holiday_rate' => $defaults['special_holiday_rate'],
            'holiday_overtime_premium_rate' => $defaults['holiday_overtime_premium_rate'],
            'unworked_regular_holiday_pay_enabled' => $defaults['unworked_regular_holiday_pay_enabled'],
        ]);

        Notification::make()
            ->title('Reset to standard baseline')
            ->body('Default baseline values loaded into the form. Click "Save Changes" to apply.')
            ->info()
            ->send();
    }

    public function getFormulaRowsProperty(): array
    {
        $settings = $this->currentSettings();

        $daysPerMonth = $settings->divisor('regular_work_days_per_month');
        $halfMonthDays = $settings->value('regular_half_month_days');
        $workHours = $settings->divisor('work_hours_per_day');
        $lateGrace = (int) round($settings->value('late_grace_minutes'));
        $overtimeMultiplier = $settings->value('overtime_rate_multiplier');
        $holidayOvertimePremium = $settings->value('holiday_overtime_premium_rate');
        $unworkedHolidayPolicy = $settings->enabled('unworked_regular_holiday_pay_enabled') ? 'enabled' : 'disabled';

        return [
            [
                'name' => 'Rate per day',
                'category' => 'Rates & Divisors',
                'badge_color' => 'sky',
                'expression' => "Monthly: Monthly Rate / {$daysPerMonth} | Daily: Daily Rate snapshot",
                'formula' => "Monthly employee: Monthly Rate / {$daysPerMonth}. Daily employee: Employee Daily Rate or D.T.R daily-rate snapshot.",
            ],
            [
                'name' => 'Rate per hour',
                'category' => 'Rates & Divisors',
                'badge_color' => 'sky',
                'expression' => "Rate Per Day / {$workHours}",
                'formula' => "Rate Per Day / {$workHours}.",
            ],
            [
                'name' => 'Late grace',
                'category' => 'Attendance & Hours',
                'badge_color' => 'indigo',
                'expression' => "Punch Time <= Schedule Start + {$lateGrace} min(s)",
                'formula' => "Daily Regular attendance arriving within {$lateGrace} minute(s) of schedule start has no late deduction.",
            ],
            [
                'name' => 'Monthly employee days worked',
                'category' => 'Attendance & Hours',
                'badge_color' => 'indigo',
                'expression' => "{$halfMonthDays} - (Absence & Unpaid Leave Mins / ({$workHours} x 60))",
                'formula' => "{$halfMonthDays} - (Uncovered absence and unpaid-leave minutes / ({$workHours} x 60)). Saturday absence contributes 180 minutes, not one full day.",
            ],
            [
                'name' => 'Daily employee days worked',
                'category' => 'Attendance & Hours',
                'badge_color' => 'indigo',
                'expression' => "Count(Payable D.T.R entries) + Paid Leave Days - Approved Half-Days",
                'formula' => 'Count payable D.T.R entries, excluding absences and overtime-only rows, then deduct approved half-days.',
            ],
            [
                'name' => 'Saturday (read-only)',
                'category' => 'Attendance & Hours',
                'badge_color' => 'indigo',
                'expression' => "Worked 3-Hour Saturday = 0.50 Day | Absence = 180 Mins",
                'formula' => 'Worked three-hour Saturday counts as 0.50 attendance day; Saturday absence deducts only three hours for a monthly employee. Overtime-only rows add no workday.',
            ],
            [
                'name' => 'Base pay',
                'category' => 'Earnings & Premiums',
                'badge_color' => 'emerald',
                'expression' => "Monthly: Salary x {$halfMonthDays} / {$daysPerMonth} | Daily: Daily Rate x Days Worked",
                'formula' => "Monthly salary x {$halfMonthDays} / {$daysPerMonth}; daily employees: Daily Rate x Days Worked. Round completed monetary lines only.",
            ],
            [
                'name' => 'Overtime amount',
                'category' => 'Earnings & Premiums',
                'badge_color' => 'emerald',
                'expression' => "(Credited OT Minutes / 60) x Rate Per Hour x {$overtimeMultiplier}",
                'formula' => "Credited Overtime Minutes / 60 x Rate Per Hour x {$overtimeMultiplier}.",
            ],
            [
                'name' => 'Shift 3 (read-only)',
                'category' => 'Earnings & Premiums',
                'badge_color' => 'emerald',
                'expression' => "10% x Eligible Shift 3 Regular Worked Pay",
                'formula' => '10% of eligible imported Shift 3 regular worked pay, excluding overtime.',
            ],
            [
                'name' => 'Holiday premium',
                'category' => 'Earnings & Premiums',
                'badge_color' => 'emerald',
                'expression' => "Credited Holiday Hours x Premium | Holiday OT = {$holidayOvertimePremium}%",
                'formula' => "Regular credited holiday hours use the holiday premium multiplier. Credited overtime minutes on a holiday always use {$holidayOvertimePremium}%.",
            ],
            [
                'name' => 'Unworked regular holiday pay',
                'category' => 'Earnings & Premiums',
                'badge_color' => 'emerald',
                'expression' => "Daily Employees (No Punch) = 1 Daily Rate ({$unworkedHolidayPolicy})",
                'formula' => "Daily employees with no D.T.R row on a regular holiday receive one daily rate when this setting is {$unworkedHolidayPolicy}. Monthly employees are already covered by base pay.",
            ],
            [
                'name' => 'Gross pay',
                'category' => 'Totals & Summaries',
                'badge_color' => 'amber',
                'expression' => "Base Pay + Salary Adj + Allowance + OT + Shift 3 + Reg Holiday + Spec Holiday",
                'formula' => 'Base Pay + Salary Adjustment + Allowance + Overtime Amount + Shift 3 Premium + Regular Holiday + Special Holiday.',
            ],
            [
                'name' => 'Deductions',
                'category' => 'Deductions & Statutory',
                'badge_color' => 'rose',
                'expression' => "Undertime + Half-Day + Absent + Late + Shortages + Uniform + Other + Statutory",
                'formula' => 'Undertime Amount + Half-Day Amount + Absent Amount + Late Amount + Company Deductions + Remittances + Other Deductions.',
            ],
            [
                'name' => 'Net pay',
                'category' => 'Totals & Summaries',
                'badge_color' => 'amber',
                'expression' => "Gross Pay - Total Deductions",
                'formula' => 'Gross Pay - Total Deductions.',
            ],
            [
                'name' => 'Payroll summary',
                'category' => 'Totals & Summaries',
                'badge_color' => 'amber',
                'expression' => "Sum(Gross Pay), Sum(Total Deductions), Sum(Net Pay) grouped by Branch/Dept",
                'formula' => 'Grouped by branch: sum Gross Pay, Total Deductions, and Net Pay from the same period employee payroll rows.',
            ],
        ];
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function saveSettingsAction(): Action
    {
        return Action::make('saveSettings')
            ->label('Save Changes')
            ->icon(Heroicon::Check)
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Save Period Calculation Rules?')
            ->modalDescription('Apply these rules to the selected open period and recalculate its attendance and DTR totals. Locked periods and other periods remain unchanged.')
            ->visible(fn (): bool => static::canAccess())
            ->disabled(fn (): bool => ! $this->period || $this->period->is_locked)
            ->action(fn () => $this->save());
    }

    public function resetToDefaultsAction(): Action
    {
        return Action::make('resetToDefaults')
            ->label('Reset to Standard Baseline')
            ->icon(Heroicon::ArrowPath)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Reset Period Rules to Standard Baseline?')
            ->modalDescription('This will populate standard baseline parameters (26 workdays, 13 cutoff days, 8 hours, 2 min grace, 1.00 OT multiplier, 200% reg holiday). You still need to click Save Changes to apply them.')
            ->visible(fn (): bool => static::canAccess() && (bool) $this->period && ! $this->period->is_locked)
            ->action(fn () => $this->resetToDefaults());
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
