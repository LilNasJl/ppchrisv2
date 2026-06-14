<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\Announcement;
use App\Models\Branch;
use App\Models\Deduction;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Models\Holiday;
use App\Models\HolidayType;
use App\Models\Leave;
use App\Models\Memo;
use App\Models\MemoType;
use App\Models\PayrollPeriod;
use App\Models\PayrollPeriodBranchExclusion;
use App\Models\PayrollPeriodEmployeeExclusion;
use App\Models\PayrollSignatory;
use App\Models\SystemAccount;
use App\Models\Ticket;
use App\Models\User;
use App\Observers\HrActionNotificationObserver;
use App\Services\AccountLogService;
use App\Services\PayrollPeriodGenerator;
use App\Services\PayrollPeriodLockService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        date_default_timezone_set(config('app.timezone', 'Asia/Manila'));

        foreach ($this->hrNotifiableModels() as $model) {
            $model::observe(HrActionNotificationObserver::class);
        }

        $this->registerAccountLogEvents();
        $this->ensureCurrentPayrollPeriodForWebRequests();
    }

    protected function registerAccountLogEvents(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            app(AccountLogService::class)->record('login', $event->user);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            app(AccountLogService::class)->record('logout', $event->user);
        });
    }

    protected function ensureCurrentPayrollPeriodForWebRequests(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            if (! Schema::hasTable('payroll_periods')) {
                return;
            }

            $now = Carbon::now('Asia/Manila');
            $checkKey = 'payroll-period:auto-check:'.$now->format('Y-m-d-H');

            if (Cache::has($checkKey)) {
                return;
            }

            Cache::lock('payroll-period:auto-check-lock', 10)->block(1, function () use ($checkKey): void {
                if (Cache::has($checkKey)) {
                    return;
                }

                app(PayrollPeriodGenerator::class)->ensureCurrentPeriod();
                app(PayrollPeriodLockService::class)->lockPastPayoutPeriods();

                Cache::put($checkKey, true, now()->addHour());
            });
        } catch (Throwable $exception) {
            Log::warning('Automatic payroll period check failed.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array<class-string<Model>>
     */
    protected function hrNotifiableModels(): array
    {
        return [
            Activity::class,
            Announcement::class,
            Branch::class,
            Deduction::class,
            Department::class,
            Designation::class,
            Dtr::class,
            Employee::class,
            EmployeeDeduction::class,
            Holiday::class,
            HolidayType::class,
            Leave::class,
            Memo::class,
            MemoType::class,
            PayrollPeriod::class,
            PayrollPeriodBranchExclusion::class,
            PayrollPeriodEmployeeExclusion::class,
            PayrollSignatory::class,
            Role::class,
            SystemAccount::class,
            Ticket::class,
            User::class,
        ];
    }
}
