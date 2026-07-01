<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class BirthdayCalendar extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.birthday-calendar';

    protected static ?string $title = 'Birthday Calendar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cake;

    protected static string|UnitEnum|null $navigationGroup = 'Updates and Activities';

    protected static ?int $navigationSort = 5;

    public string $calendarMonth;

    public function mount(): void
    {
        $this->calendarMonth = now('Asia/Manila')->startOfMonth()->format('Y-m-d');
    }

    public function previousMonth(): void
    {
        $this->calendarMonth = Carbon::parse($this->calendarMonth)
            ->subMonth()
            ->startOfMonth()
            ->format('Y-m-d');
    }

    public function nextMonth(): void
    {
        $this->calendarMonth = Carbon::parse($this->calendarMonth)
            ->addMonth()
            ->startOfMonth()
            ->format('Y-m-d');
    }

    public function getMonthLabelProperty(): string
    {
        return Carbon::parse($this->calendarMonth)->format('F Y');
    }

    public function getCalendarDaysProperty(): array
    {
        $month = Carbon::parse($this->calendarMonth, 'Asia/Manila')->startOfMonth();
        $start = $month->copy()->startOfWeek(Carbon::SUNDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
        $today = now('Asia/Manila')->toDateString();
        $birthdays = $this->birthdaysForMonth($month);

        $days = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $monthDay = $date->format('m-d');

            $days[] = [
                'date' => $date->toDateString(),
                'day' => $date->day,
                'isCurrentMonth' => $date->isSameMonth($month),
                'isToday' => $date->toDateString() === $today,
                'birthdays' => $birthdays->get($monthDay, collect())->values(),
            ];
        }

        return $days;
    }

    public function getMonthBirthdaysProperty(): Collection
    {
        return $this->birthdaysForMonth(Carbon::parse($this->calendarMonth)->startOfMonth())
            ->flatten(1)
            ->sortBy([
                ['birthday_sort', 'asc'],
                ['lastname', 'asc'],
                ['firstname', 'asc'],
            ])
            ->values();
    }

    protected function birthdaysForMonth(Carbon $month): Collection
    {
        return Employee::query()
            ->with(['user', 'branch'])
            ->whereNotNull('birthdate')
            ->activeEmployment()
            ->whereHas('user', fn (Builder $query) => $query->where('role', 'employee'))
            ->whereMonth('birthdate', $month->month)
            ->orderByRaw('DAY(birthdate)')
            ->orderBy('lastname')
            ->get()
            ->map(function (Employee $employee) use ($month): Employee {
                $birthday = Carbon::parse($employee->birthdate)->year($month->year);

                return $employee->forceFill([
                    'birthday_display' => $birthday->format('M d, Y'),
                    'birthday_sort' => $birthday->format('m-d'),
                ]);
            })
            ->groupBy(fn (Employee $employee): string => Carbon::parse($employee->birthdate)->format('m-d'));
    }
}
