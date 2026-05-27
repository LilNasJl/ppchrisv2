<?php

namespace App\Filament\Pages;

use App\Filament\Resources\HolidayTypes\HolidayTypeResource;
use App\Models\Holiday;
use App\Models\HolidayType;
use App\Services\HolidayResolver;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use UnitEnum;

class HolidayCalendar extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.holiday-calendar';

    protected static ?string $title = 'Holiday Calendar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Updates and Activities';

    protected static ?int $navigationSort = 3;

    public string $calendarMonth;

    public ?string $selectedDate = null;

    public int|string|null $holidayTypeId = null;

    public ?string $holidayTitle = null;

    public ?string $newHolidayType = null;

    public ?string $newHolidayRate = null;

    public ?string $newHolidayDescription = null;

    public array $holidayTypeEdits = [];

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('fetchPhilippineHolidays')
                    ->label('Fetch PH Holidays')
                    ->icon('heroicon-m-cloud-arrow-down')
                    ->visible(fn (): bool => ! $this->isBranchHolidayCalendar())
                    ->action(fn (): mixed => $this->fetchPhilippineHolidays()),

                Action::make('manageHolidayTypes')
                    ->label('Manage Holiday Types')
                    ->icon('heroicon-m-pencil-square')
                    ->url(HolidayTypeResource::getUrl()),

                Action::make('viewHolidays')
                    ->label('View Holidays')
                    ->icon('heroicon-m-list-bullet')
                    ->modalHeading(fn (): string => $this->calendarScopeLabel.' Holidays')
                    ->modalSubmitAction(false)
                    ->modalContent(fn () => view('filament.pages.partials.holiday-list', [
                        'holidays' => $this->allHolidays,
                    ])),

                Action::make('holidayExclusions')
                    ->label('Holiday Exclusions')
                    ->icon('heroicon-m-user-minus')
                    ->url(HolidayExclusions::getUrl()),

                Action::make('branchHolidays')
                    ->label('Branch Holiday')
                    ->icon('heroicon-m-building-storefront')
                    ->visible(fn (): bool => ! $this->isBranchHolidayCalendar())
                    ->url(BranchHolidayBranches::getUrl()),
            ])
                ->label('Manage')
                ->icon(Heroicon::ChevronDown)
                ->button(),
        ];
    }

    public function mount(): void
    {
        $this->ensureDefaultHolidayTypes();
        $this->ensureDefaultPhilippineFixedHolidays();
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

        $holiday = app(HolidayResolver::class)
            ->resolveForDate($this->selectedDate, $this->getHolidayBranchId(), includeNational: false);

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

        $date = Carbon::parse($data['selectedDate']);

        Holiday::query()->updateOrCreate(
            [
                'branch_id' => $this->getHolidayBranchId(),
                'is_recurring' => true,
                'month_day' => $date->format('m-d'),
            ],
            [
                'date' => $date->toDateString(),
                'holiday_type_id' => $data['holidayTypeId'],
                'title' => $data['holidayTitle'],
                'source' => $this->isBranchHolidayCalendar() ? 'branch' : 'manual',
            ],
        );

        Notification::make()
            ->title('Recurring holiday saved')
            ->body('This holiday will be applied automatically every year.')
            ->success()
            ->send();
    }

    public function fetchPhilippineHolidays(): void
    {
        $year = Carbon::parse($this->calendarMonth)->year;

        try {
            $response = Http::timeout(20)
                ->get("https://date.nager.at/api/v3/PublicHolidays/{$year}/PH");
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Unable to fetch Philippine holidays')
                ->body('Please check internet connection and try again.')
                ->danger()
                ->send();

            return;
        }

        if (! $response->successful()) {
            Notification::make()
                ->title('Unable to fetch Philippine holidays')
                ->body("The holiday service returned HTTP {$response->status()}.")
                ->danger()
                ->send();

            return;
        }

        $createdOrUpdated = 0;

        foreach ($response->json() as $holiday) {
            $date = Carbon::parse($holiday['date']);
            $title = $holiday['localName'] ?? $holiday['name'] ?? 'Philippine Holiday';
            $isRecurring = (bool) ($holiday['fixed'] ?? false);
            $attributes = [
                'branch_id' => null,
                'is_recurring' => $isRecurring,
            ];

            if ($isRecurring) {
                $attributes['month_day'] = $date->format('m-d');
            } else {
                $attributes['date'] = $date->toDateString();
            }

            Holiday::query()->updateOrCreate($attributes, [
                'date' => $date->toDateString(),
                'month_day' => $date->format('m-d'),
                'holiday_type_id' => $this->getHolidayTypeIdForPhilippineHoliday($title),
                'title' => $title,
                'source' => 'philippines-api',
            ]);

            $createdOrUpdated++;
        }

        Notification::make()
            ->title('Philippine holidays fetched')
            ->body("Saved {$createdOrUpdated} holidays for {$year}.")
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

        HolidayType::query()->find($holidayTypeId)?->update($data);

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
        $this->holidayScopedQuery()
            ->whereKey($holidayId)
            ->first()
            ?->delete();

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

        $holidays = app(HolidayResolver::class)
            ->holidaysForRange($start, $end, $this->getHolidayBranchId(), includeNational: false);

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
            ->whereNull('branch_id')
            ->orderByDesc('date')
            ->limit(20)
            ->get();
    }

    public function getAllHolidaysProperty()
    {
        return Holiday::query()
            ->with(['type', 'branch'])
            ->when(
                $this->getHolidayBranchId(),
                fn (Builder $query, int $branchId): Builder => $query->where('branch_id', $branchId),
                fn (Builder $query): Builder => $query->whereNull('branch_id'),
            )
            ->orderByDesc('date')
            ->get();
    }

    public function getCalendarScopeLabelProperty(): string
    {
        return 'National';
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

    protected function ensureDefaultPhilippineFixedHolidays(): void
    {
        if ($this->isBranchHolidayCalendar()) {
            return;
        }

        $regularHolidayTypeId = HolidayType::query()
            ->where('type', 'Regular Holiday')
            ->value('id');
        $specialHolidayTypeId = HolidayType::query()
            ->where('type', 'Special Holiday')
            ->value('id');

        foreach ([
            ['month_day' => '01-01', 'title' => "New Year's Day", 'type_id' => $regularHolidayTypeId],
            ['month_day' => '04-09', 'title' => 'Araw ng Kagitingan', 'type_id' => $regularHolidayTypeId],
            ['month_day' => '05-01', 'title' => 'Labor Day', 'type_id' => $regularHolidayTypeId],
            ['month_day' => '06-12', 'title' => 'Independence Day', 'type_id' => $regularHolidayTypeId],
            ['month_day' => '11-30', 'title' => 'Bonifacio Day', 'type_id' => $regularHolidayTypeId],
            ['month_day' => '12-25', 'title' => 'Christmas Day', 'type_id' => $regularHolidayTypeId],
            ['month_day' => '12-30', 'title' => 'Rizal Day', 'type_id' => $regularHolidayTypeId],
            ['month_day' => '08-21', 'title' => 'Ninoy Aquino Day', 'type_id' => $specialHolidayTypeId],
            ['month_day' => '11-01', 'title' => "All Saints' Day", 'type_id' => $specialHolidayTypeId],
            ['month_day' => '12-08', 'title' => 'Feast of the Immaculate Conception', 'type_id' => $specialHolidayTypeId],
            ['month_day' => '12-31', 'title' => 'Last Day of the Year', 'type_id' => $specialHolidayTypeId],
        ] as $holiday) {
            if (blank($holiday['type_id'])) {
                continue;
            }

            Holiday::query()->updateOrCreate([
                'branch_id' => null,
                'is_recurring' => true,
                'month_day' => $holiday['month_day'],
            ], [
                'date' => now()->year.'-'.$holiday['month_day'],
                'holiday_type_id' => $holiday['type_id'],
                'title' => $holiday['title'],
                'source' => 'philippines-fixed-default',
            ]);
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

    protected function holidayScopedQuery(): Builder
    {
        return Holiday::query()
            ->when(
                $this->getHolidayBranchId(),
                fn (Builder $query, int $branchId): Builder => $query->where('branch_id', $branchId),
                fn (Builder $query): Builder => $query->whereNull('branch_id'),
            );
    }

    protected function getHolidayBranchId(): ?int
    {
        return null;
    }

    protected function isBranchHolidayCalendar(): bool
    {
        return false;
    }

    protected function getHolidayTypeIdForPhilippineHoliday(string $title): ?int
    {
        $normalizedTitle = str($title)->lower()->toString();
        $regularKeywords = [
            'new year',
            'maundy',
            'good friday',
            'valor',
            'kagitingan',
            'labor',
            'independence',
            'national heroes',
            'bonifacio',
            'christmas',
            'rizal',
            'eid',
        ];

        $typeName = collect($regularKeywords)
            ->contains(fn (string $keyword): bool => str_contains($normalizedTitle, $keyword))
                ? 'Regular Holiday'
                : 'Special Holiday';

        return HolidayType::query()
            ->where('type', $typeName)
            ->value('id')
            ?: $this->getDefaultHolidayTypeId();
    }
}
