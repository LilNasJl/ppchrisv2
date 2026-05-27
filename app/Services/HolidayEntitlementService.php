<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\HolidayEmployeeExclusion;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class HolidayEntitlementService
{
    public function dtrHolidayData(?Employee $employee, CarbonInterface|string $date, ?int $branchId = null): array
    {
        $resolved = $this->resolveForEmployeeDate($employee, $date, $branchId);
        $holiday = $resolved['holiday'];

        if (! $holiday) {
            return $this->emptyDtrHolidayData();
        }

        $excluded = (bool) $resolved['excluded'];

        return [
            'is_holiday' => 1,
            'holiday_id' => $holiday->id,
            'holiday_type' => $holiday->type?->type,
            'holiday_rate' => $excluded ? 0 : $holiday->type?->rate,
            'holiday_excluded' => $excluded,
        ];
    }

    public function resolveForEmployeeDate(?Employee $employee, CarbonInterface|string $date, ?int $branchId = null, bool $includeNational = true): array
    {
        $holiday = app(HolidayResolver::class)->resolveForDate($date, $branchId, $includeNational);

        return [
            'holiday' => $holiday,
            'excluded' => $holiday && $employee
                ? $this->isExcluded($holiday, $employee, $date)
                : false,
        ];
    }

    public function regularHolidaysForEmployeeRange(Employee $employee, CarbonInterface|string $start, CarbonInterface|string $end, ?int $branchId = null): Collection
    {
        return app(HolidayResolver::class)
            ->holidaysForRange($start, $end, $branchId)
            ->filter(fn (Holiday $holiday, string $date): bool => $this->isRegularHoliday($holiday)
                && ! $this->isExcluded($holiday, $employee, $date))
            ->map(function (Holiday $holiday, string $date): Holiday {
                $holiday->setAttribute('occurrence_date', Carbon::parse($date)->toDateString());

                return $holiday;
            })
            ->values();
    }

    public function isExcluded(Holiday $holiday, Employee|int|null $employee, CarbonInterface|string $date): bool
    {
        $employeeId = $employee instanceof Employee ? $employee->id : $employee;

        if (blank($employeeId)) {
            return false;
        }

        $date = Carbon::parse($date)->startOfDay();

        return HolidayEmployeeExclusion::query()
            ->where('holiday_id', $holiday->id)
            ->where('employee_id', $employeeId)
            ->where(function ($query) use ($date): void {
                $query
                    ->whereDate('occurrence_date', $date->toDateString())
                    ->orWhere(function ($query) use ($date): void {
                        $query
                            ->where('applies_every_year', true)
                            ->whereMonth('occurrence_date', $date->month)
                            ->whereDay('occurrence_date', $date->day);
                    });
            })
            ->exists();
    }

    public function isRegularHoliday(Holiday $holiday): bool
    {
        $holidayType = Str::lower((string) $holiday->type?->type);
        $holidayRate = (float) ($holiday->type?->rate ?? 0);

        return Str::contains($holidayType, 'regular') || $holidayRate >= 100;
    }

    public function emptyDtrHolidayData(): array
    {
        return [
            'is_holiday' => 0,
            'holiday_id' => null,
            'holiday_type' => null,
            'holiday_rate' => null,
            'holiday_excluded' => false,
        ];
    }
}
