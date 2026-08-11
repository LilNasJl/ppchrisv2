<?php

use App\Models\Employee;
use App\Services\DtrDailyAggregationService;
use App\Services\PayrollPeriodGenerator;
use App\Services\PayrollPeriodLockService;
use App\Services\ProbationaryEmploymentPromotionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('leave:reset-yearly', function (): void {
    Employee::query()
        ->with('designation')
        ->each(function (Employee $employee): void {
            $employee->forceFill([
                'leave_credits' => $employee->annualLeaveCredits(),
                'birthday_leave_credits' => 1,
                'leave_credits_year' => now()->year,
            ])->saveQuietly();
        });

    $this->info('Employee leave credits have been reset for '.now()->year.'.');
})->purpose('Reset employee leave and birthday leave credits for the current year');

Artisan::command('payroll-period:ensure-current', function (PayrollPeriodGenerator $generator): void {
    $result = $generator->ensureCurrentPeriod();

    $message = $result['created']
        ? 'Payroll period created: '
        : 'Payroll period already exists: ';

    $this->info($message.$result['period']->title);
})->purpose('Create the current payroll period when it does not exist');

Artisan::command('payroll-period:auto-lock-due', function (PayrollPeriodLockService $lockService): void {
    $locked = $lockService->lockPastPayoutPeriods();

    $this->info("Locked {$locked} payroll period(s) after payout.");
})->purpose('Lock payroll periods after their payout date and process deduction terms');

Artisan::command('employment:promote-eligible', function (ProbationaryEmploymentPromotionService $service): void {
    $promoted = $service->promoteEligible();

    $this->info("Promoted {$promoted} eligible probationary employee(s) to Permanent.");
})->purpose('Promote probationary employees after completing six calendar months of service');

Artisan::command('dtr:recalculate-daily {--period=}', function (DtrDailyAggregationService $service): void {
    $periodId = filled($this->option('period')) ? (int) $this->option('period') : null;
    $groups = $service->recalculatePeriod($periodId);

    $scope = $periodId ? " for payroll period {$periodId}" : '';
    $this->info("Recalculated {$groups} employee-day D.T.R group(s){$scope}.");
})->purpose('Recalculate combined daily Regular attendance metrics');

Schedule::command('payroll-period:ensure-current')->hourly();
Schedule::command('payroll-period:auto-lock-due')->hourly();
Schedule::command('employment:promote-eligible')
    ->hourly()
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();
