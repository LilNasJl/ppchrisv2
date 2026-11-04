<?php

namespace App\Services;

use App\Models\Dtr;
use App\Models\Holiday;
use App\Models\HolidayType;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhilippineHolidayService
{
    /**
     * Google Calendar official public iCal feed for Philippine Holidays.
     */
    public const GOOGLE_CALENDAR_PH_ICAL = 'https://calendar.google.com/calendar/ical/en.philippines%23holiday%40group.v.calendar.google.com/public/basic.ics';

    /**
     * Fallback international holiday REST API endpoint.
     */
    public const NAGER_DATE_PH_URL = 'https://date.nager.at/api/v3/PublicHolidays/%d/PH';

    /**
     * Synchronize Philippine holidays for a specific year.
     * Combines official Presidential Proclamation data with online feeds,
     * corrects statutory DOLE pay types, and ensures zero duplicates.
     *
     * @return array{saved: int, merged_duplicates: int, online_synced: bool}
     */
    public function syncHolidaysForYear(int $year): array
    {
        return DB::transaction(function () use ($year): array {
            $regularTypeId = $this->getHolidayTypeId('Regular Holiday');
            $specialTypeId = $this->getHolidayTypeId('Special Holiday');

            // 1. First, safely clean up any existing duplicate rows for this year
            $mergedDuplicates = $this->cleanupDuplicateHolidays($year);

            // 2. Load official baseline holidays from statutory law & presidential proclamations
            $officialHolidays = $this->getOfficialHolidays($year);

            // 3. Attempt online sync from high-availability Google Calendar feed for any newly proclaimed days
            $onlineHolidays = $this->fetchOnlineGoogleHolidays($year);
            $onlineSynced = count($onlineHolidays) > 0;

            // Collect existing normalized titles in official holidays to avoid movable title duplicates
            $existingNormalizedTitles = collect($officialHolidays)
                ->map(fn (array $h): string => strtolower($h['title']))
                ->values()
                ->all();

            // Merge online holidays (if any new ad-hoc proclamation exists that isn't in baseline)
            foreach ($onlineHolidays as $dateStr => $onlineItem) {
                $classification = $this->classifyHoliday($onlineItem['title'], $dateStr);
                $normalizedClassifiedTitle = strtolower($classification['title']);

                if (isset($officialHolidays[$dateStr]) || in_array($normalizedClassifiedTitle, $existingNormalizedTitles, true)) {
                    continue;
                }

                $officialHolidays[$dateStr] = [
                    'title' => $classification['title'],
                    'type' => $classification['type'],
                    'is_recurring' => false,
                    'source' => 'google-calendar-ph',
                ];
                $existingNormalizedTitles[] = $normalizedClassifiedTitle;
            }

            $savedCount = 0;

            // 4. Upsert each holiday uniquely by calendar date
            foreach ($officialHolidays as $dateStr => $item) {
                $date = Carbon::parse($dateStr);
                $monthDay = $date->format('m-d');
                $typeId = ($item['type'] === 'Regular Holiday') ? $regularTypeId : $specialTypeId;
                $isRecurring = (bool) ($item['is_recurring'] ?? false);

                // Find existing national holiday matching this exact date OR recurring month_day
                $existing = Holiday::query()
                    ->whereNull('branch_id')
                    ->where(function ($q) use ($dateStr, $monthDay) {
                        $q->whereDate('date', $dateStr)
                            ->orWhere(function ($sub) use ($monthDay) {
                                $sub->where('is_recurring', true)
                                    ->where('month_day', $monthDay);
                            });
                    })
                    ->orderByRaw('CASE WHEN DATE(`date`) = ? THEN 1 ELSE 0 END DESC', [$dateStr])
                    ->first();

                if ($existing) {
                    $existing->update([
                        'date' => $date->toDateString(),
                        'month_day' => $monthDay,
                        'holiday_type_id' => $typeId,
                        'title' => $item['title'],
                        'is_recurring' => $isRecurring,
                        'source' => $item['source'] ?? 'philippines-proclamation',
                    ]);
                } else {
                    Holiday::create([
                        'branch_id' => null,
                        'date' => $date->toDateString(),
                        'month_day' => $monthDay,
                        'holiday_type_id' => $typeId,
                        'title' => $item['title'],
                        'is_recurring' => $isRecurring,
                        'source' => $item['source'] ?? 'philippines-proclamation',
                    ]);
                }

                $savedCount++;
            }

            return [
                'saved' => $savedCount,
                'merged_duplicates' => $mergedDuplicates,
                'online_synced' => $onlineSynced,
            ];
        });
    }

    /**
     * Ensure the default statutory fixed national holidays exist as recurring holidays.
     * Prevents duplication if matching dates or month_day already exist.
     */
    public function ensureDefaultFixedHolidays(): void
    {
        $regularTypeId = $this->getHolidayTypeId('Regular Holiday');
        $specialTypeId = $this->getHolidayTypeId('Special Holiday');

        $fixedHolidays = [
            ['month_day' => '01-01', 'title' => "New Year's Day", 'type_id' => $regularTypeId],
            ['month_day' => '04-09', 'title' => 'Araw ng Kagitingan', 'type_id' => $regularTypeId],
            ['month_day' => '05-01', 'title' => 'Labor Day', 'type_id' => $regularTypeId],
            ['month_day' => '06-12', 'title' => 'Independence Day', 'type_id' => $regularTypeId],
            ['month_day' => '11-30', 'title' => 'Bonifacio Day', 'type_id' => $regularTypeId],
            ['month_day' => '12-25', 'title' => 'Christmas Day', 'type_id' => $regularTypeId],
            ['month_day' => '12-30', 'title' => 'Rizal Day', 'type_id' => $regularTypeId],
            ['month_day' => '08-21', 'title' => 'Ninoy Aquino Day', 'type_id' => $specialTypeId],
            ['month_day' => '11-01', 'title' => "All Saints' Day", 'type_id' => $specialTypeId],
            ['month_day' => '12-08', 'title' => 'Feast of the Immaculate Conception', 'type_id' => $specialTypeId],
            ['month_day' => '12-24', 'title' => 'Christmas Eve', 'type_id' => $specialTypeId],
            ['month_day' => '12-31', 'title' => 'Last Day of the Year', 'type_id' => $specialTypeId],
        ];

        foreach ($fixedHolidays as $holiday) {
            $existing = Holiday::query()
                ->whereNull('branch_id')
                ->where(function ($q) use ($holiday) {
                    $q->where('month_day', $holiday['month_day'])
                        ->orWhere(function ($sub) use ($holiday) {
                            $sub->where('is_recurring', true)
                                ->where('month_day', $holiday['month_day']);
                        });
                })
                ->orderByRaw('CASE WHEN is_recurring = 1 THEN 1 ELSE 0 END DESC')
                ->first();

            if ($existing) {
                $existing->update([
                    'month_day' => $holiday['month_day'],
                    'holiday_type_id' => $holiday['type_id'],
                    'title' => $holiday['title'],
                    'is_recurring' => true,
                    'source' => $existing->source ?: 'philippines-fixed-default',
                ]);
            } else {
                Holiday::create([
                    'branch_id' => null,
                    'is_recurring' => true,
                    'month_day' => $holiday['month_day'],
                    'date' => now()->year.'-'.$holiday['month_day'],
                    'holiday_type_id' => $holiday['type_id'],
                    'title' => $holiday['title'],
                    'source' => 'philippines-fixed-default',
                ]);
            }
        }
    }

    /**
     * Get the official Philippine holidays according to Republic Acts (RA 9849, RA 10966)
     * and Presidential Proclamations (Proc. 727 for 2025, Proc. 1006 for 2026, etc.).
     */
    public function getOfficialHolidays(int $year): array
    {
        $easterSunday = $this->calculateEasterSunday($year);
        $easterTs = strtotime($easterSunday);

        $maundyThursday = date('Y-m-d', strtotime('-3 days', $easterTs));
        $goodFriday = date('Y-m-d', strtotime('-2 days', $easterTs));
        $blackSaturday = date('Y-m-d', strtotime('-1 day', $easterTs));
        $nationalHeroesDay = $this->calculateNationalHeroesDay($year);

        $holidays = [
            // 1. Statutory Regular Holidays (200% pay / 100% unworked wage)
            "$year-01-01" => ['title' => "New Year's Day", 'type' => 'Regular Holiday', 'is_recurring' => true],
            $maundyThursday => ['title' => 'Maundy Thursday', 'type' => 'Regular Holiday', 'is_recurring' => false],
            $goodFriday => ['title' => 'Good Friday', 'type' => 'Regular Holiday', 'is_recurring' => false],
            "$year-04-09" => ['title' => 'Araw ng Kagitingan', 'type' => 'Regular Holiday', 'is_recurring' => true],
            "$year-05-01" => ['title' => 'Labor Day', 'type' => 'Regular Holiday', 'is_recurring' => true],
            "$year-06-12" => ['title' => 'Independence Day', 'type' => 'Regular Holiday', 'is_recurring' => true],
            $nationalHeroesDay => ['title' => 'National Heroes Day', 'type' => 'Regular Holiday', 'is_recurring' => false],
            "$year-11-30" => ['title' => 'Bonifacio Day', 'type' => 'Regular Holiday', 'is_recurring' => true],
            "$year-12-25" => ['title' => 'Christmas Day', 'type' => 'Regular Holiday', 'is_recurring' => true],
            "$year-12-30" => ['title' => 'Rizal Day', 'type' => 'Regular Holiday', 'is_recurring' => true],

            // 2. Statutory Nationwide Special (Non-Working) Days (130% pay / 0% unworked)
            $blackSaturday => ['title' => 'Black Saturday', 'type' => 'Special Holiday', 'is_recurring' => false],
            "$year-08-21" => ['title' => 'Ninoy Aquino Day', 'type' => 'Special Holiday', 'is_recurring' => true],
            "$year-11-01" => ['title' => "All Saints' Day", 'type' => 'Special Holiday', 'is_recurring' => true],
            "$year-12-08" => ['title' => 'Feast of the Immaculate Conception', 'type' => 'Special Holiday', 'is_recurring' => true],
            "$year-12-24" => ['title' => 'Christmas Eve', 'type' => 'Special Holiday', 'is_recurring' => true],
            "$year-12-31" => ['title' => 'Last Day of the Year', 'type' => 'Special Holiday', 'is_recurring' => true],
        ];

        // Specific Presidential Proclamations for Movable Islamic and Special Days
        $proclamationMovable = [
            2024 => [
                '2024-02-10' => ['title' => 'Chinese New Year', 'type' => 'Special Holiday'],
                '2024-04-10' => ['title' => "Eid'l Fitr", 'type' => 'Regular Holiday'],
                '2024-06-17' => ['title' => "Eid'l Adha", 'type' => 'Regular Holiday'],
                '2024-08-23' => ['title' => 'Ninoy Aquino Day', 'type' => 'Special Holiday', 'replaces' => '2024-08-21'],
                '2024-10-31' => ['title' => "All Saints' Day Eve", 'type' => 'Special Holiday'],
                '2024-11-02' => ['title' => "All Souls' Day", 'type' => 'Special Holiday'],
            ],
            2025 => [
                '2025-01-29' => ['title' => 'Chinese New Year', 'type' => 'Special Holiday'],
                '2025-03-31' => ['title' => "Eid'l Fitr", 'type' => 'Regular Holiday'],
                '2025-05-12' => ['title' => 'National and Local Elections', 'type' => 'Special Holiday'],
                '2025-06-06' => ['title' => "Eid'l Adha", 'type' => 'Regular Holiday'],
                '2025-10-31' => ['title' => "All Saints' Day Eve", 'type' => 'Special Holiday'],
            ],
            2026 => [
                '2026-02-17' => ['title' => 'Chinese New Year', 'type' => 'Special Holiday'],
                '2026-03-20' => ['title' => "Eid'l Fitr", 'type' => 'Regular Holiday'],
                '2026-05-27' => ['title' => "Eid'l Adha", 'type' => 'Regular Holiday'],
                '2026-11-02' => ['title' => "All Souls' Day", 'type' => 'Special Holiday'],
            ],
            2027 => [
                '2027-02-06' => ['title' => 'Chinese New Year', 'type' => 'Special Holiday'],
                '2027-03-10' => ['title' => "Eid'l Fitr", 'type' => 'Regular Holiday'],
                '2027-05-16' => ['title' => "Eid'l Adha", 'type' => 'Regular Holiday'],
                '2027-11-02' => ['title' => "All Souls' Day", 'type' => 'Special Holiday'],
            ],
        ];

        if (isset($proclamationMovable[$year])) {
            foreach ($proclamationMovable[$year] as $date => $item) {
                if (! empty($item['replaces'])) {
                    unset($holidays[$item['replaces']]);
                }
                $holidays[$date] = [
                    'title' => $item['title'],
                    'type' => $item['type'],
                    'is_recurring' => false,
                ];
            }
        }

        ksort($holidays);

        return $holidays;
    }

    /**
     * Classify any holiday string (English or Tagalog) into its canonical English title
     * and statutory DOLE classification ('Regular Holiday' or 'Special Holiday').
     */
    public function classifyHoliday(string $rawTitle, ?string $date = null): array
    {
        $normalized = strtolower(trim($rawTitle));

        // Canonical mapping dictionary for common aliases, Tagalog names, and API titles
        $canonicalMap = [
            'bagong taon' => ["New Year's Day", 'Regular Holiday'],
            "new year's day" => ["New Year's Day", 'Regular Holiday'],
            'new year' => ["New Year's Day", 'Regular Holiday'],

            'chinese' => ['Chinese New Year', 'Special Holiday'],
            'lunar new year' => ['Chinese New Year', 'Special Holiday'],

            'huwebes santo' => ['Maundy Thursday', 'Regular Holiday'],
            'maundy' => ['Maundy Thursday', 'Regular Holiday'],

            'biyernes santo' => ['Good Friday', 'Regular Holiday'],
            'good friday' => ['Good Friday', 'Regular Holiday'],

            'sabado de gloria' => ['Black Saturday', 'Special Holiday'],
            'black saturday' => ['Black Saturday', 'Special Holiday'],

            'araw ng kagitingan' => ['Araw ng Kagitingan', 'Regular Holiday'],
            'day of valor' => ['Araw ng Kagitingan', 'Regular Holiday'],
            'kagitingan' => ['Araw ng Kagitingan', 'Regular Holiday'],

            'paggawa' => ['Labor Day', 'Regular Holiday'],
            'labor day' => ['Labor Day', 'Regular Holiday'],

            'kalayaan' => ['Independence Day', 'Regular Holiday'],
            'independence day' => ['Independence Day', 'Regular Holiday'],

            'ninoy' => ['Ninoy Aquino Day', 'Special Holiday'],

            'bayani' => ['National Heroes Day', 'Regular Holiday'],
            'national heroes' => ['National Heroes Day', 'Regular Holiday'],
            'national heroes day' => ['National Heroes Day', 'Regular Holiday'],

            'all saints' => ["All Saints' Day", 'Special Holiday'],
            'santo' => ["All Saints' Day", 'Special Holiday'],

            'all souls' => ["All Souls' Day", 'Special Holiday'],
            'araw ng mga patay' => ["All Souls' Day", 'Special Holiday'],

            'bonifacio' => ['Bonifacio Day', 'Regular Holiday'],

            'immaculada' => ['Feast of the Immaculate Conception', 'Special Holiday'],
            'immaculate' => ['Feast of the Immaculate Conception', 'Special Holiday'],

            'christmas eve' => ['Christmas Eve', 'Special Holiday'],
            'bisperas ng pasko' => ['Christmas Eve', 'Special Holiday'],

            'pasko' => ['Christmas Day', 'Regular Holiday'],
            'christmas day' => ['Christmas Day', 'Regular Holiday'],
            'christmas' => ['Christmas Day', 'Regular Holiday'],

            'rizal' => ['Rizal Day', 'Regular Holiday'],

            'huling araw' => ['Last Day of the Year', 'Special Holiday'],
            'last day of the year' => ['Last Day of the Year', 'Special Holiday'],
            "new year's eve" => ['Last Day of the Year', 'Special Holiday'],

            "eid'l fitr" => ["Eid'l Fitr", 'Regular Holiday'],
            'eid al-fitr' => ["Eid'l Fitr", 'Regular Holiday'],
            'eid-ul-fitr' => ["Eid'l Fitr", 'Regular Holiday'],
            'feast of ramadhan' => ["Eid'l Fitr", 'Regular Holiday'],

            "eid'l adha" => ["Eid'l Adha", 'Regular Holiday'],
            'eid al-adha' => ["Eid'l Adha", 'Regular Holiday'],
            'eid-ul-adha' => ["Eid'l Adha", 'Regular Holiday'],
            'feast of sacrifice' => ["Eid'l Adha", 'Regular Holiday'],
        ];

        // Specific overrides: Christmas Eve must never be classified as Christmas Day
        if (str_contains($normalized, 'christmas eve') || str_contains($normalized, 'bisperas ng pasko')) {
            return ['title' => 'Christmas Eve', 'type' => 'Special Holiday'];
        }

        // All Saints' Day Eve override
        if ((str_contains($normalized, 'eve') && (str_contains($normalized, 'saint') || str_contains($normalized, 'santo')))
            || ($date && str_ends_with($date, '-10-31'))) {
            return ['title' => "All Saints' Day Eve", 'type' => 'Special Holiday'];
        }

        // Chinese New Year must never be classified as New Year's Day
        if (str_contains($normalized, 'chinese') || str_contains($normalized, 'lunar')) {
            return ['title' => 'Chinese New Year', 'type' => 'Special Holiday'];
        }

        foreach ($canonicalMap as $needle => [$canonicalTitle, $holidayType]) {
            if (str_contains($normalized, $needle)) {
                return [
                    'title' => $canonicalTitle,
                    'type' => $holidayType,
                ];
            }
        }

        // Regular holiday keyword check
        $regularKeywords = [
            'maundy', 'good friday', 'kagitingan', 'valor', 'labor', 'paggawa',
            'independence', 'kalayaan', 'national heroes', 'bayani', 'bonifacio',
            'christmas', 'pasko', 'rizal', 'eid',
        ];

        foreach ($regularKeywords as $kw) {
            if (str_contains($normalized, $kw)) {
                return ['title' => $rawTitle, 'type' => 'Regular Holiday'];
            }
        }

        return ['title' => $rawTitle, 'type' => 'Special Holiday'];
    }

    /**
     * Fetch public holidays from Google Calendar's Philippine Holidays iCal feed.
     */
    public function fetchOnlineGoogleHolidays(int $year): array
    {
        try {
            $response = Http::timeout(10)->get(self::GOOGLE_CALENDAR_PH_ICAL);
            if (! $response->successful()) {
                return [];
            }
            $ics = $response->body();
        } catch (\Throwable $e) {
            Log::info("Google Calendar PH iCal fetch skipped: {$e->getMessage()}");

            return [];
        }

        preg_match_all('/BEGIN:VEVENT.*?END:VEVENT/s', $ics, $matches);
        $holidays = [];

        foreach ($matches[0] as $eventStr) {
            preg_match('/DTSTART;VALUE=DATE:(\d{8})/', $eventStr, $mDate);
            preg_match('/SUMMARY:(.*?)\r?\n/', $eventStr, $mSum);
            preg_match('/DESCRIPTION:(.*?)\r?\n/', $eventStr, $mDesc);

            if (! empty($mDate[1]) && ! empty($mSum[1])) {
                $eventYear = (int) substr($mDate[1], 0, 4);
                if ($eventYear !== $year) {
                    continue;
                }

                $summary = trim($mSum[1]);
                $desc = trim($mDesc[1] ?? '');
                $date = substr($mDate[1], 0, 4).'-'.substr($mDate[1], 4, 2).'-'.substr($mDate[1], 6, 2);

                // Skip non-holiday observances unless recognized as a Philippine special day
                if (str_contains(strtolower($desc), 'observance') && ! str_contains(strtolower($summary), 'all souls')) {
                    continue;
                }

                // Skip regional Muslim observances (PD 1083) that are not nationwide holidays
                $lowerTitle = strtolower($summary);
                if (str_contains($lowerTitle, 'lailatul')
                    || str_contains($lowerTitle, 'amun jadid')
                    || str_contains($lowerTitle, 'maulid')
                    || str_contains($lowerTitle, 'ramadan')) {
                    continue;
                }

                $holidays[$date] = [
                    'title' => $summary,
                    'raw_desc' => $desc,
                ];
            }
        }

        return $holidays;
    }

    /**
     * Clean up duplicate holidays for a given year or all years.
     * Re-links any existing DTR records to the canonical record before deleting duplicates.
     */
    public function cleanupDuplicateHolidays(?int $year = null): int
    {
        $regularTypeId = $this->getHolidayTypeId('Regular Holiday');
        $specialTypeId = $this->getHolidayTypeId('Special Holiday');

        $query = Holiday::query()
            ->whereNull('branch_id')
            ->when($year, function ($q) use ($year) {
                $q->where(function ($sub) use ($year) {
                    $sub->whereYear('date', $year)
                        ->orWhere('is_recurring', true);
                });
            });

        $allHolidays = $query->get();

        // Group holidays by calendar date (or recurring month_day)
        $grouped = [];
        foreach ($allHolidays as $h) {
            $dateStr = $h->date instanceof CarbonInterface
                ? $h->date->toDateString()
                : ($h->date ? Carbon::parse($h->date)->toDateString() : (($year ?? now()->year).'-'.$h->month_day));
            $grouped[$dateStr][] = $h;
        }

        $mergedCount = 0;

        foreach ($grouped as $dateStr => $records) {
            if (count($records) <= 1) {
                // Ensure single record has the right type and title
                $single = $records[0];
                $classification = $this->classifyHoliday($single->title, $dateStr);
                $correctTypeId = ($classification['type'] === 'Regular Holiday') ? $regularTypeId : $specialTypeId;

                $isMovable = in_array($classification['title'], [
                    'Maundy Thursday',
                    'Good Friday',
                    'Black Saturday',
                    'National Heroes Day',
                    "Eid'l Fitr",
                    "Eid'l Adha",
                    'Chinese New Year',
                    "All Souls' Day",
                    "All Saints' Day Eve",
                ], true);

                $single->update([
                    'title' => $classification['title'],
                    'holiday_type_id' => $correctTypeId,
                    'is_recurring' => ! $isMovable && (bool) $single->is_recurring,
                    'source' => $single->source ?: 'philippines-proclamation',
                ]);

                continue;
            }

            // Multiple records found for the same date: choose canonical
            $canonical = null;
            $duplicates = [];

            // Prefer recurring / English statutory title
            foreach ($records as $rec) {
                if ($canonical === null) {
                    $canonical = $rec;

                    continue;
                }

                // If rec is recurring and canonical is not, prefer rec
                if ($rec->is_recurring && ! $canonical->is_recurring) {
                    $duplicates[] = $canonical;
                    $canonical = $rec;
                } elseif (! $rec->is_recurring && $canonical->is_recurring) {
                    $duplicates[] = $rec;
                } elseif ($this->isEnglishTitle($rec->title) && ! $this->isEnglishTitle($canonical->title)) {
                    $duplicates[] = $canonical;
                    $canonical = $rec;
                } else {
                    $duplicates[] = $rec;
                }
            }

            // Ensure canonical has proper classification
            $classification = $this->classifyHoliday($canonical->title, $dateStr);
            $correctTypeId = ($classification['type'] === 'Regular Holiday') ? $regularTypeId : $specialTypeId;

            $isMovable = in_array($classification['title'], [
                'Maundy Thursday',
                'Good Friday',
                'Black Saturday',
                'National Heroes Day',
                "Eid'l Fitr",
                "Eid'l Adha",
                'Chinese New Year',
                "All Souls' Day",
                "All Saints' Day Eve",
            ], true);

            $canonical->update([
                'title' => $classification['title'],
                'holiday_type_id' => $correctTypeId,
                'is_recurring' => ! $isMovable && (bool) $canonical->is_recurring,
                'source' => $canonical->source ?: 'philippines-proclamation',
            ]);

            // Re-point DTRs and delete duplicates
            foreach ($duplicates as $dup) {
                Dtr::where('holiday_id', $dup->id)->update(['holiday_id' => $canonical->id]);
                $dup->delete();
                $mergedCount++;
            }
        }

        return $mergedCount;
    }

    /**
     * Compute Easter Sunday using the Gregorian Meeus/Jones/Butcher algorithm.
     */
    public function calculateEasterSunday(int $year): string
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * National Heroes Day is legally observed on the last Monday of August (RA 9492).
     */
    public function calculateNationalHeroesDay(int $year): string
    {
        return date('Y-m-d', strtotime("last monday of august $year"));
    }

    protected function getHolidayTypeId(string $typeName): int
    {
        return (int) HolidayType::firstOrCreate(
            ['type' => $typeName],
            [
                'rate' => ($typeName === 'Regular Holiday') ? 200 : 30,
                'description' => "Default $typeName rate.",
            ]
        )->id;
    }

    protected function isEnglishTitle(string $title): bool
    {
        $tagalogWords = ['araw', 'pasko', 'paggawa', 'kalayaan', 'bayani', 'santo', 'patay', 'huling', 'taon', 'huwebes', 'biyernes', 'sabado'];
        $lower = strtolower($title);
        foreach ($tagalogWords as $w) {
            if (str_contains($lower, $w)) {
                return false;
            }
        }

        return true;
    }
}
