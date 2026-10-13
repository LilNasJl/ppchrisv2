<?php

namespace App\Filament\Pages;

use App\Models\PayrollCalculationSetting;
use App\Models\PayrollPeriod;
use App\Services\DtrDailyAggregationService;
use App\Services\PayrollCalculator;
use App\Services\AccountLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Group;
use Filament\Support\Icons\Heroicon;
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

    protected static ?string $title = 'Payroll Settings';

    public static function canAccess(): bool
    {
        return false;
    }

    #[Locked]
    public ?int $periodId = null;

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
        $this->periodId = PayrollPeriod::resolvePublicId(request()->query('periodId')) ?: app(PayrollCalculator::class)->defaultPeriod()?->id;
        $this->period = filled($this->periodId) ? PayrollPeriod::query()->find($this->periodId) : null;
        $this->period_display = $this->period?->title ?? 'No payroll period selected';

        $setting = $this->period
            ? PayrollCalculationSetting::forPeriod($this->period)
            : new PayrollCalculationSetting(PayrollCalculationSetting::DEFAULTS);

        $this->form->fill([
            'period_display' => $this->period_display,
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

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('period_display')
                ->label('Payroll Period')
                ->disabled()
                ->dehydrated(false)
                ->columnSpanFull(),

            Group::make([
                TextInput::make('regular_work_days_per_month')
                    ->label('Regular Work Days / Month')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(31)
                    ->helperText('Used for monthly employee daily-rate conversion.'),

                TextInput::make('regular_half_month_days')
                    ->label('Regular Half-Month Days')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(31)
                    ->helperText('Base payable days for regular monthly employees.'),

                TextInput::make('work_hours_per_day')
                    ->label('Work Hours / Day')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(24)
                    ->helperText('Used to compute rate per hour.'),

                TextInput::make('late_grace_minutes')
                    ->label('Late Grace (Minutes)')
                    ->numeric()
                    ->integer()
                    ->required()
                    ->minValue(0)
                    ->maxValue(60)
                    ->helperText('A punch inside this grace period is not counted as late.'),

                TextInput::make('half_day_work_day_value')
                    ->label('Half-Day Value')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(1)
                    ->helperText('Example: 0.50 means half day deducts half of one day.'),

                TextInput::make('overtime_rate_multiplier')
                    ->label('Overtime Multiplier')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(5)
                    ->helperText('Example: 1.00 means credited overtime is paid at normal hourly rate.'),

                TextInput::make('regular_holiday_rate')
                    ->label('Default Regular Holiday Rate (%)')
                    ->disabled()
                    ->dehydrated(false)
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->helperText('Used only when the D.T.R row has no stored holiday rate.'),

                TextInput::make('special_holiday_rate')
                    ->label('Default Special Holiday Rate (%)')
                    ->disabled()
                    ->dehydrated(false)
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->helperText('Used only when the D.T.R row has no stored holiday rate.'),

                TextInput::make('holiday_overtime_premium_rate')
                    ->label('Holiday Overtime Premium (%)')
                    ->disabled()
                    ->dehydrated(false)
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->helperText('Applied only to credited overtime minutes on holidays.'),

                Toggle::make('unworked_regular_holiday_pay_enabled')
                    ->label('Pay Unworked Regular Holidays for Daily Employees')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Monthly employees are already covered by fixed half-month base pay.')
                    ->columnSpanFull(),
            ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->period = PayrollPeriod::query()->find($this->periodId);
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
        ]);
        $data = $state
            ->map(fn ($value): float => round((float) $value, 2))
            ->all();
        if ($data['regular_half_month_days'] > $data['regular_work_days_per_month']) {
            throw ValidationException::withMessages(['regular_half_month_days' => 'Cutoff days cannot exceed monthly work days.']);
        }
        DB::transaction(function () use ($data): void {
            $period = PayrollPeriod::query()->whereKey($this->periodId)->lockForUpdate()->firstOrFail();
            if ($period->is_locked) {
                throw ValidationException::withMessages(['period_display' => 'This payroll period has been locked.']);
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
            ->title('Payroll calculation updated')
            ->body('The formula settings now apply only to this payroll period.')
            ->success()
            ->send();
    }

    public function getFormulaRowsProperty(): array
    {
        $settings = $this->period
            ? PayrollCalculationSetting::forPeriod($this->period)
            : new PayrollCalculationSetting(PayrollCalculationSetting::DEFAULTS);

        $daysPerMonth = $settings->divisor('regular_work_days_per_month');
        $halfMonthDays = $settings->value('regular_half_month_days');
        $workHours = $settings->divisor('work_hours_per_day');
        $lateGrace = (int) round($settings->value('late_grace_minutes'));
        $halfDay = $settings->value('half_day_work_day_value');
        $overtimeMultiplier = $settings->value('overtime_rate_multiplier');
        $holidayOvertimePremium = $settings->value('holiday_overtime_premium_rate');
        $unworkedHolidayPolicy = $settings->enabled('unworked_regular_holiday_pay_enabled') ? 'enabled' : 'disabled';

        return [
            ['name' => 'Rate per day', 'formula' => "Monthly employee: Monthly Rate / {$daysPerMonth}. Daily employee: Employee Daily Rate or D.T.R daily-rate snapshot."],
            ['name' => 'Rate per hour', 'formula' => "Rate Per Day / {$workHours}."],
            ['name' => 'Late grace', 'formula' => "Daily Regular attendance arriving within {$lateGrace} minute(s) of schedule start has no late deduction."],
            ['name' => 'Monthly employee days worked', 'formula' => "{$halfMonthDays} - Absence Days - (Approved Half-Day Count x {$halfDay})."],
            ['name' => 'Daily employee days worked', 'formula' => 'Count payable D.T.R entries, excluding absences and overtime-only rows, then deduct approved half-days.'],
            ['name' => 'Base pay', 'formula' => "Monthly salary x {$halfMonthDays} / {$daysPerMonth}; daily employees: Daily Rate x Days Worked. Round completed monetary lines only."],
            ['name' => 'Saturday (read-only)', 'formula' => 'Existing three-hour Saturday schedule counts as 0.50 attendance day.'],
            ['name' => 'Shift 3 (read-only)', 'formula' => '10% of eligible imported Shift 3 regular worked pay, excluding overtime.'],
            ['name' => 'Overtime amount', 'formula' => "Credited Overtime Minutes / 60 x Rate Per Hour x {$overtimeMultiplier}."],
            ['name' => 'Holiday premium', 'formula' => "Regular credited holiday hours use the holiday premium multiplier. Credited overtime minutes on a holiday always use {$holidayOvertimePremium}%."],
            ['name' => 'Unworked regular holiday pay', 'formula' => "Daily employees with no D.T.R row on a regular holiday receive one daily rate when this setting is {$unworkedHolidayPolicy}. Monthly employees are already covered by base pay."],
            ['name' => 'Gross pay', 'formula' => 'Base Pay + Salary Adjustment + Allowance + Overtime Amount + Shift 3 Premium + Regular Holiday + Special Holiday.'],
            ['name' => 'Deductions', 'formula' => 'Undertime Amount + Half-Day Amount + Absent Amount + Late Amount + Company Deductions + Remittances + Other Deductions.'],
            ['name' => 'Net pay', 'formula' => 'Gross Pay - Total Deductions.'],
            ['name' => 'Payroll summary', 'formula' => 'Grouped by branch: sum Gross Pay, Total Deductions, and Net Pay from the same period employee payroll rows.'],
        ];
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function saveSettingsAction(): Action
    {
        return Action::make('saveSettings')->label('Save Changes')->icon(Heroicon::Check)
            ->requiresConfirmation()
            ->modalDescription('Apply these rules to the selected open period and recalculate its DTR totals. Locked periods and other periods remain unchanged.')
            ->visible(fn (): bool => static::canAccess())
            ->disabled(fn (): bool => ! $this->period || $this->period->is_locked)
            ->action(fn () => $this->save());
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
