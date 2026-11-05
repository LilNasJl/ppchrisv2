<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\BirthdayCalendar;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BirthdayGreetingCard extends Widget
{
    protected string $view = 'filament.widgets.birthday-greeting-card';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return true;
    }

    protected function getViewData(): array
    {
        $today = now('Asia/Manila');

        $birthdayEmployees = static::birthdayEmployees()
            ->get()
            ->map(function (Employee $employee) use ($today): array {
                $birthdate = $employee->birthdate ? Carbon::parse($employee->birthdate) : null;
                $age = $birthdate ? (int) floor($birthdate->diffInYears($today)) : null;

                $first = trim((string) ($employee->first_name ?: $employee->firstname));
                $last = trim((string) ($employee->last_name ?: $employee->lastname));
                $initials = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));

                return [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'initials' => $initials ?: 'EMP',
                    'designation' => $employee->designation?->title ?: 'No designation',
                    'branch' => $employee->branch?->branch_name ?: 'No branch',
                    'department' => $employee->department?->name,
                    'birthdate_display' => $birthdate?->format('F d, Y'),
                    'birthday_display' => $birthdate?->format('F d'),
                    'age' => $age,
                    'avatar' => $employee->user?->profile_photo_url,
                ];
            });

        $upcomingBirthdays = static::upcomingBirthdays($today);

        return [
            'today' => $today,
            'birthdayEmployees' => $birthdayEmployees,
            'upcomingBirthdays' => $upcomingBirthdays,
            'calendarUrl' => class_exists(BirthdayCalendar::class) ? BirthdayCalendar::getUrl() : null,
        ];
    }

    public static function birthdayEmployees(): Builder
    {
        $today = now('Asia/Manila');
        $monthDay = $today->format('m-d');
        $driver = DB::getDriverName();

        $query = Employee::query()
            ->with(['user', 'branch', 'designation', 'department'])
            ->activeEmployment()
            ->whereHas('user', fn (Builder $q) => $q->where('role', 'employee'));

        if ($driver === 'sqlite') {
            $query->whereRaw("strftime('%m-%d', birthdate) = ?", [$monthDay]);
        } else {
            $query->whereRaw("DATE_FORMAT(birthdate, '%m-%d') = ?", [$monthDay]);
        }

        return $query
            ->orderBy('lastname')
            ->orderBy('firstname');
    }

    public static function upcomingBirthdays(Carbon $today): Collection
    {
        return Employee::query()
            ->with(['branch', 'designation'])
            ->whereNotNull('birthdate')
            ->activeEmployment()
            ->whereHas('user', fn (Builder $query) => $query->where('role', 'employee'))
            ->get()
            ->filter(function (Employee $employee) use ($today): bool {
                $bdayDate = Carbon::parse($employee->birthdate)->setYear($today->year)->startOfDay();
                if ($bdayDate->isBefore($today->copy()->startOfDay())) {
                    $bdayDate->addYear();
                }

                $diff = (int) round($today->copy()->startOfDay()->diffInDays($bdayDate, false));

                return $diff > 0 && $diff <= 14;
            })
            ->sortBy(function (Employee $employee) use ($today): int {
                $bdayDate = Carbon::parse($employee->birthdate)->setYear($today->year)->startOfDay();
                if ($bdayDate->isBefore($today->copy()->startOfDay())) {
                    $bdayDate->addYear();
                }

                return $bdayDate->timestamp;
            })
            ->take(4)
            ->map(function (Employee $employee) use ($today): array {
                $bdayDate = Carbon::parse($employee->birthdate)->setYear($today->year)->startOfDay();
                if ($bdayDate->isBefore($today->copy()->startOfDay())) {
                    $bdayDate->addYear();
                }
                $diff = (int) round($today->copy()->startOfDay()->diffInDays($bdayDate, false));

                return [
                    'name' => $employee->full_name,
                    'date' => $bdayDate->format('M d'),
                    'relative' => $diff === 1 ? 'Tomorrow' : "In {$diff} days",
                    'designation' => $employee->designation?->title,
                    'branch' => $employee->branch?->branch_name,
                ];
            })
            ->values();
    }
}
