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
use App\Models\PayrollSignatory;
use App\Models\SystemAccount;
use App\Models\User;
use App\Observers\HrActionNotificationObserver;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        foreach ($this->hrNotifiableModels() as $model) {
            $model::observe(HrActionNotificationObserver::class);
        }
    }

    /**
     * @return array<class-string<\Illuminate\Database\Eloquent\Model>>
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
            PayrollSignatory::class,
            Role::class,
            SystemAccount::class,
            User::class,
        ];
    }
}
