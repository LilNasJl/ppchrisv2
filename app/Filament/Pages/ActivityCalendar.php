<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Activities\ActivityResource;
use App\Models\Activity;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Validator;
use UnitEnum;

class ActivityCalendar extends Page
{
    protected string $view = 'filament.pages.activity-calendar';

    protected static ?string $title = 'Activity Calendar';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::CalendarDateRange;

    protected static string | UnitEnum | null $navigationGroup = 'Updates and Activities';

    protected static ?int $navigationSort = 4;

    public string $calendarMonth;

    public ?int $editingActivityId = null;

    public ?string $activityTitle = null;

    public ?string $activityDescription = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewActivities')
                ->label('View Activities')
                ->icon(Heroicon::ClipboardDocumentList)
                ->url(ActivityResource::getUrl()),
        ];
    }

    public function mount(): void
    {
        $this->calendarMonth = now()->startOfMonth()->format('Y-m-d');
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();
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

    public function selectDate(string $date): void
    {
        $this->dateFrom = Carbon::parse($date)->toDateString();
        $this->dateTo = Carbon::parse($date)->toDateString();
        $this->editingActivityId = null;
    }

    public function editActivity(int $activityId): void
    {
        $activity = Activity::query()->find($activityId);

        if (! $activity) {
            return;
        }

        $this->editingActivityId = $activity->id;
        $this->activityTitle = $activity->title;
        $this->activityDescription = $activity->description;
        $this->dateFrom = $activity->date_from?->toDateString();
        $this->dateTo = $activity->date_to?->toDateString();
    }

    public function resetActivityForm(): void
    {
        $this->editingActivityId = null;
        $this->activityTitle = null;
        $this->activityDescription = null;
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function saveActivity(): void
    {
        $data = Validator::make([
            'title' => $this->activityTitle,
            'description' => $this->activityDescription,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ])->validate();

        if ($this->editingActivityId) {
            Activity::query()
                ->whereKey($this->editingActivityId)
                ->update($data);
        } else {
            Activity::create($data);
        }

        $this->resetActivityForm();

        Notification::make()
            ->title('Activity saved')
            ->success()
            ->send();
    }

    public function deleteActivity(int $activityId): void
    {
        Activity::query()->whereKey($activityId)->delete();

        if ($this->editingActivityId === $activityId) {
            $this->resetActivityForm();
        }

        Notification::make()
            ->title('Activity deleted')
            ->success()
            ->send();
    }

    public function getMonthLabelProperty(): string
    {
        return Carbon::parse($this->calendarMonth)->format('F Y');
    }

    public function getCalendarDaysProperty(): array
    {
        $month = Carbon::parse($this->calendarMonth)->startOfMonth();
        $start = $month->copy()->startOfWeek();
        $end = $month->copy()->endOfMonth()->endOfWeek();

        $activities = Activity::query()
            ->whereDate('date_from', '<=', $end->toDateString())
            ->whereDate('date_to', '>=', $start->toDateString())
            ->orderBy('date_from')
            ->get();

        $days = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateString = $date->toDateString();
            $dayActivities = $activities
                ->filter(fn (Activity $activity): bool =>
                    $activity->date_from->toDateString() <= $dateString &&
                    $activity->date_to->toDateString() >= $dateString
                )
                ->values();

            $days[] = [
                'date' => $dateString,
                'day' => $date->day,
                'isCurrentMonth' => $date->isSameMonth($month),
                'isToday' => $date->isToday(),
                'activities' => $dayActivities,
            ];
        }

        return $days;
    }

    public function getUpcomingActivitiesProperty()
    {
        return Activity::query()
            ->whereDate('date_to', '>=', now()->toDateString())
            ->orderBy('date_from')
            ->limit(20)
            ->get();
    }
}
