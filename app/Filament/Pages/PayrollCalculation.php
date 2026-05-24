<?php

namespace App\Filament\Pages;

use App\Models\PayrollCalculationSetting;
use App\Models\PayrollPeriod;
use App\Services\PayrollCalculator;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Payroll Management';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Payroll Calculation';

    public ?int $periodId = null;

    public ?PayrollPeriod $period = null;

    public ?string $period_display = null;

    public ?string $regular_work_days_per_month = null;

    public ?string $regular_half_month_days = null;

    public ?string $work_hours_per_day = null;

    public ?string $half_day_work_day_value = null;

    public ?string $overtime_rate_multiplier = null;

    public ?string $regular_holiday_rate = null;

    public ?string $special_holiday_rate = null;

    public function mount(): void
    {
        $this->periodId = (int) (request()->query('periodId') ?: app(PayrollCalculator::class)->defaultPeriod()?->id);
        $this->period = PayrollPeriod::query()->find($this->periodId);
        $this->period_display = $this->period?->title ?? 'No payroll period selected';

        $setting = $this->period
            ? PayrollCalculationSetting::forPeriod($this->period)
            : new PayrollCalculationSetting(PayrollCalculationSetting::DEFAULTS);

        $this->form->fill([
            'period_display' => $this->period_display,
            'regular_work_days_per_month' => $setting->regular_work_days_per_month,
            'regular_half_month_days' => $setting->regular_half_month_days,
            'work_hours_per_day' => $setting->work_hours_per_day,
            'half_day_work_day_value' => $setting->half_day_work_day_value,
            'overtime_rate_multiplier' => $setting->overtime_rate_multiplier,
            'regular_holiday_rate' => $setting->regular_holiday_rate,
            'special_holiday_rate' => $setting->special_holiday_rate,
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
                    ->helperText('Used for monthly employee daily-rate conversion.'),

                TextInput::make('regular_half_month_days')
                    ->label('Regular Half-Month Days')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->helperText('Base payable days for regular monthly employees.'),

                TextInput::make('work_hours_per_day')
                    ->label('Work Hours / Day')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->helperText('Used to compute rate per hour.'),

                TextInput::make('half_day_work_day_value')
                    ->label('Half-Day Value')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->helperText('Example: 0.50 means half day deducts half of one day.'),

                TextInput::make('overtime_rate_multiplier')
                    ->label('Overtime Multiplier')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->helperText('Example: 1.00 means credited overtime is paid at normal hourly rate.'),

                TextInput::make('regular_holiday_rate')
                    ->label('Default Regular Holiday Rate (%)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->helperText('Used only when the D.T.R row has no stored holiday rate.'),

                TextInput::make('special_holiday_rate')
                    ->label('Default Special Holiday Rate (%)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->helperText('Used only when the D.T.R row has no stored holiday rate.'),
            ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    public function save(): void
    {
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

        $data = collect($this->form->getState())
            ->except('period_display')
            ->map(fn ($value): float => round((float) $value, 2))
            ->all();

        PayrollCalculationSetting::query()->updateOrCreate(
            ['payroll_period_id' => $this->period->id],
            $data
        );

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
        $halfDay = $settings->value('half_day_work_day_value');
        $overtimeMultiplier = $settings->value('overtime_rate_multiplier');

        return [
            ['name' => 'Rate per day', 'formula' => "Monthly employee: Monthly Rate / {$daysPerMonth}. Daily employee: Employee Daily Rate or D.T.R daily-rate snapshot."],
            ['name' => 'Rate per hour', 'formula' => "Rate Per Day / {$workHours}."],
            ['name' => 'Monthly employee days worked', 'formula' => "{$halfMonthDays} - Absence Days - (Approved Half-Day Count x {$halfDay})."],
            ['name' => 'Daily employee days worked', 'formula' => 'Count payable D.T.R entries, excluding absences and overtime-only rows, then deduct approved half-days.'],
            ['name' => 'Base pay', 'formula' => "Monthly employee: Rate Per Day x {$halfMonthDays}. Daily employee: Rate Per Day x Days Worked."],
            ['name' => 'Overtime amount', 'formula' => "Credited Overtime Minutes / 60 x Rate Per Hour x {$overtimeMultiplier}."],
            ['name' => 'Holiday premium', 'formula' => 'Credited work hours x rate per hour x holiday premium multiplier. Regular 200% adds the extra 100%; Special 30% adds 30%.'],
            ['name' => 'Gross pay', 'formula' => 'Base Pay + Salary Adjustment + Allowance + Overtime Amount + Regular Holiday + Special Holiday.'],
            ['name' => 'Deductions', 'formula' => 'Undertime Amount + Half-Day Amount + Absent Amount + Late Amount + Company Deductions + Remittances + Other Deductions.'],
            ['name' => 'Net pay', 'formula' => 'Gross Pay - Total Deductions.'],
            ['name' => 'Payroll summary', 'formula' => 'Grouped by branch: sum Gross Pay, Total Deductions, and Net Pay from the same period employee payroll rows.'],
        ];
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => filled($this->periodId)
                    ? PayrollPeriodBranches::getUrl(['periodId' => $this->periodId])
                    : Payroll::getUrl()),
        ];
    }
}
