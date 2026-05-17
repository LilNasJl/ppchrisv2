<?php

namespace App\Filament\Pages;

use App\Filament\Resources\HolidayTypes\HolidayTypeResource;
use App\Models\Holiday;
use App\Models\HolidayType;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Validator;
use UnitEnum;

class HolidayCalendar extends Page
{
    protected string $view = 'filament.pages.holiday-calendar';

    protected static ?string $title = 'Holiday Calendar';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::CalendarDays;

    protected static string | UnitEnum | null $navigationGroup = 'Updates and Activities';

    protected static ?int $navigationSort = 3;

    public string $calendarMonth;

    public ?string $selectedDate = null;

    public int | string | null $holidayTypeId = null;

    public ?string $holidayTitle = null;

    public ?string $newHolidayType = null;

    public ?string $newHolidayRate = null;

    public ?string $newHolidayDescription = null;

    public array $holidayTypeEdits = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageHolidayTypes')
                ->label('Manage Holiday Types')
                ->icon('heroicon-m-pencil-square')
                ->url(HolidayTypeResource::getUrl()),

            Action::make('viewHolidays')
                ->label('View Holidays')
                ->icon('heroicon-m-list-bullet')
                ->modalHeading('Recent Holidays')
                ->modalSubmitAction(false)
                ->modalContent(fn () => view('filament.pages.partials.holiday-list', [
                    'holidays' => $this->allHolidays,
                ])),
        ];
    }

    public function mount(): void
    {
        $this->ensureDefaultHolidayTypes();
        $this->loadHolidayTypeEdits();

        $this->calendarMonth = now()->startOfMonth()->format('Y-m-d');
        $this->selectedDate = now()->toDateString();
        $this->holidayTypeId = $this->getDefaultHolidayTypeId();
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
        $this->selectedDate = Carbon::parse($date)->toDateString();

        $holiday = Holiday::query()
            ->whereDate('date', $this->selectedDate)
            ->first();

        $this->holidayTypeId = $holiday?->holiday_type_id ?: $this->getDefaultHolidayTypeId();
        $this->holidayTitle = $holiday?->title;
    }

    public function saveHoliday(): void
    {
        $data = Validator::make([
            'selectedDate' => $this->selectedDate,
            'holidayTypeId' => $this->holidayTypeId,
            'holidayTitle' => $this->holidayTitle,
        ], [
            'selectedDate' => ['required', 'date'],
            'holidayTypeId' => ['required', 'exists:holiday_types,id'],
            'holidayTitle' => ['required', 'string', 'max:255'],
        ])->validate();

        Holiday::updateOrCreate(
            ['date' => Carbon::parse($data['selectedDate'])->toDateString()],
            [
                'holiday_type_id' => $data['holidayTypeId'],
                'title' => $data['holidayTitle'],
            ],
        );

        Notification::make()
            ->title('Holiday saved')
            ->success()
            ->send();
    }

    public function createHolidayType(): void
    {
        $data = Validator::make([
            'type' => $this->newHolidayType,
            'rate' => $this->newHolidayRate,
            'description' => $this->newHolidayDescription,
        ], [
            'type' => ['required', 'string', 'max:255', 'unique:holiday_types,type'],
            'rate' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ])->validate();

        $holidayType = HolidayType::create($data);

        $this->holidayTypeId = $holidayType->id;
        $this->newHolidayType = null;
        $this->newHolidayRate = null;
        $this->newHolidayDescription = null;

        Notification::make()
            ->title('Holiday type created')
            ->success()
            ->send();
    }

    public function updateHolidayType(int $holidayTypeId): void
    {
        $data = Validator::make($this->holidayTypeEdits[$holidayTypeId] ?? [], [
            'type' => ['required', 'string', 'max:255', "unique:holiday_types,type,{$holidayTypeId}"],
            'rate' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ])->validate();

        HolidayType::query()
            ->whereKey($holidayTypeId)
            ->update($data);

        $this->loadHolidayTypeEdits();

        Notification::make()
            ->title('Holiday type updated')
            ->success()
            ->send();
    }

    public function deleteHolidayType(int $holidayTypeId): void
    {
        $holidayType = HolidayType::query()->find($holidayTypeId);

        if (! $holidayType) {
            return;
        }

        if (in_array($holidayType->type, $this->getDefaultHolidayTypeNames(), true)) {
            Notification::make()
                ->title('Default holiday type cannot be deleted')
                ->body('Regular Holiday and Special Holiday are required defaults.')
                ->danger()
                ->send();

            return;
        }

        $holidayType->delete();
        $this->loadHolidayTypeEdits();

        Notification::make()
            ->title('Holiday type deleted')
            ->success()
            ->send();
    }

    public function deleteHoliday(int $holidayId): void
    {
        Holiday::query()->whereKey($holidayId)->delete();

        Notification::make()
            ->title('Holiday removed')
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

        $holidays = Holiday::query()
            ->with('type')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn (Holiday $holiday): string => $holiday->date->toDateString());

        $days = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $holiday = $holidays->get($date->toDateString());

            $days[] = [
                'date' => $date->toDateString(),
                'day' => $date->day,
                'isCurrentMonth' => $date->isSameMonth($month),
                'isToday' => $date->isToday(),
                'isSelected' => $this->selectedDate === $date->toDateString(),
                'holidayTitle' => $holiday?->title,
                'holidayType' => $holiday?->type?->type,
            ];
        }

        return $days;
    }

    public function getHolidayTypesProperty()
    {
        return HolidayType::query()
            ->orderBy('type')
            ->get();
    }

    public function getHolidaysProperty()
    {
        return Holiday::query()
            ->with('type')
            ->orderByDesc('date')
            ->limit(20)
            ->get();
    }

    public function getAllHolidaysProperty()
    {
        return Holiday::query()
            ->with('type')
            ->orderByDesc('date')
            ->get();
    }

    protected function ensureDefaultHolidayTypes(): void
    {
        foreach ([
            ['type' => 'Regular Holiday', 'rate' => 200, 'description' => 'Default regular holiday rate.'],
            ['type' => 'Special Holiday', 'rate' => 30, 'description' => 'Default special holiday premium rate.'],
        ] as $holidayType) {
            HolidayType::query()->firstOrCreate(
                ['type' => $holidayType['type']],
                [
                    'rate' => $holidayType['rate'],
                    'description' => $holidayType['description'],
                ],
            );
        }
    }

    protected function loadHolidayTypeEdits(): void
    {
        $this->holidayTypeEdits = HolidayType::query()
            ->orderBy('type')
            ->get()
            ->mapWithKeys(fn (HolidayType $holidayType): array => [
                $holidayType->id => [
                    'type' => $holidayType->type,
                    'rate' => $holidayType->rate,
                    'description' => $holidayType->description,
                ],
            ])
            ->all();
    }

    protected function getDefaultHolidayTypeId(): ?int
    {
        return HolidayType::query()
            ->where('type', 'Regular Holiday')
            ->value('id');
    }

    protected function getDefaultHolidayTypeNames(): array
    {
        return ['Regular Holiday', 'Special Holiday'];
    }
}
