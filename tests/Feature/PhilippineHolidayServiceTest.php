<?php

namespace Tests\Feature;

use App\Models\Dtr;
use App\Models\Holiday;
use App\Models\HolidayType;
use App\Services\PhilippineHolidayService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhilippineHolidayServiceTest extends TestCase
{
    protected PhilippineHolidayService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestTables();
        $this->service = app(PhilippineHolidayService::class);
    }

    protected function createTestTables(): void
    {
        Schema::dropAllTables();

        Schema::create('holiday_types', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->decimal('rate', 8, 2)->default(100);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('holidays', function (Blueprint $table): void {
            $table->id();
            $table->date('date')->nullable();
            $table->unsignedBigInteger('holiday_type_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('title')->default('Holiday');
            $table->boolean('is_recurring')->default(false);
            $table->string('month_day', 10)->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
        });

        Schema::create('dtrs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_id')->default(1);
            $table->date('date_in')->nullable();
            $table->unsignedBigInteger('holiday_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function test_easter_sunday_and_holy_week_calculation(): void
    {
        // 2025 Easter is April 20
        $this->assertSame('2025-04-20', $this->service->calculateEasterSunday(2025));

        // 2026 Easter is April 5
        $this->assertSame('2026-04-05', $this->service->calculateEasterSunday(2026));

        // 2027 Easter is March 28
        $this->assertSame('2027-03-28', $this->service->calculateEasterSunday(2027));

        $holidays2026 = $this->service->getOfficialHolidays(2026);

        // Maundy Thursday: 3 days before Easter -> 2026-04-02
        $this->assertArrayHasKey('2026-04-02', $holidays2026);
        $this->assertSame('Maundy Thursday', $holidays2026['2026-04-02']['title']);
        $this->assertSame('Regular Holiday', $holidays2026['2026-04-02']['type']);

        // Good Friday: 2 days before Easter -> 2026-04-03
        $this->assertArrayHasKey('2026-04-03', $holidays2026);
        $this->assertSame('Good Friday', $holidays2026['2026-04-03']['title']);
        $this->assertSame('Regular Holiday', $holidays2026['2026-04-03']['type']);

        // Black Saturday: 1 day before Easter -> 2026-04-04
        $this->assertArrayHasKey('2026-04-04', $holidays2026);
        $this->assertSame('Black Saturday', $holidays2026['2026-04-04']['title']);
        $this->assertSame('Special Holiday', $holidays2026['2026-04-04']['type']);
    }

    public function test_national_heroes_day_calculation(): void
    {
        // Last Monday of August
        $this->assertSame('2024-08-26', $this->service->calculateNationalHeroesDay(2024));
        $this->assertSame('2025-08-25', $this->service->calculateNationalHeroesDay(2025));
        $this->assertSame('2026-08-31', $this->service->calculateNationalHeroesDay(2026));
        $this->assertSame('2027-08-30', $this->service->calculateNationalHeroesDay(2027));
    }

    public function test_statutory_dole_classification(): void
    {
        // Regular Holidays (200% pay)
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday("New Year's Day")['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday('Bagong Taon')['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday('Maundy Thursday')['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday('Huwebes Santo')['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday('Good Friday')['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday('Biyernes Santo')['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday('Labor Day')['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday('Araw ng Paggawa')['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday('Independence Day')['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday('Araw ng Kalayaan')['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday('National Heroes Day')['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday('Araw ng mga Bayani')['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday('Bonifacio Day')['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday('Christmas Day')['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday('Araw ng Pasko')['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday('Rizal Day')['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday("Eid'l Fitr")['type']);
        $this->assertSame('Regular Holiday', $this->service->classifyHoliday("Eid'l Adha")['type']);

        // Special (Non-Working) Days (130% pay)
        $this->assertSame('Special Holiday', $this->service->classifyHoliday('Chinese New Year')['type']);
        $this->assertSame('Special Holiday', $this->service->classifyHoliday('Black Saturday')['type']);
        $this->assertSame('Special Holiday', $this->service->classifyHoliday('Sabado de Gloria')['type']);
        $this->assertSame('Special Holiday', $this->service->classifyHoliday('Ninoy Aquino Day')['type']);
        $this->assertSame('Special Holiday', $this->service->classifyHoliday("All Saints' Day")['type']);
        $this->assertSame('Special Holiday', $this->service->classifyHoliday("All Souls' Day")['type']);
        $this->assertSame('Special Holiday', $this->service->classifyHoliday("All Saints' Day Eve", '2026-10-31')['type']);
        $this->assertSame('Special Holiday', $this->service->classifyHoliday('Feast of the Immaculate Conception')['type']);
        $this->assertSame('Special Holiday', $this->service->classifyHoliday('Christmas Eve')['type']);
        $this->assertSame('Special Holiday', $this->service->classifyHoliday('Last Day of the Year')['type']);
        $this->assertSame('Special Holiday', $this->service->classifyHoliday('Huling Araw ng Taon')['type']);
    }

    public function test_tagalog_names_map_to_canonical_english_titles(): void
    {
        $this->assertSame("New Year's Day", $this->service->classifyHoliday('Bagong Taon')['title']);
        $this->assertSame('Maundy Thursday', $this->service->classifyHoliday('Huwebes Santo')['title']);
        $this->assertSame('Good Friday', $this->service->classifyHoliday('Biyernes Santo')['title']);
        $this->assertSame('Black Saturday', $this->service->classifyHoliday('Sabado de Gloria')['title']);
        $this->assertSame('Labor Day', $this->service->classifyHoliday('Araw ng Paggawa')['title']);
        $this->assertSame('Independence Day', $this->service->classifyHoliday('Araw ng Kalayaan')['title']);
        $this->assertSame('National Heroes Day', $this->service->classifyHoliday('Araw ng mga Bayani')['title']);
        $this->assertSame('Christmas Day', $this->service->classifyHoliday('Araw ng Pasko')['title']);
        $this->assertSame('Christmas Eve', $this->service->classifyHoliday('Bisperas ng Pasko')['title']);
        $this->assertSame('Last Day of the Year', $this->service->classifyHoliday('Huling Araw ng Taon')['title']);
        $this->assertSame("All Souls' Day", $this->service->classifyHoliday('Araw ng mga Patay')['title']);
    }

    public function test_sync_holidays_contains_proclamation_movable_days(): void
    {
        $holidays2026 = $this->service->getOfficialHolidays(2026);

        // Chinese New Year: 2026-02-17
        $this->assertArrayHasKey('2026-02-17', $holidays2026);
        $this->assertSame('Chinese New Year', $holidays2026['2026-02-17']['title']);
        $this->assertSame('Special Holiday', $holidays2026['2026-02-17']['type']);

        // Eid'l Fitr: 2026-03-20
        $this->assertArrayHasKey('2026-03-20', $holidays2026);
        $this->assertSame("Eid'l Fitr", $holidays2026['2026-03-20']['title']);
        $this->assertSame('Regular Holiday', $holidays2026['2026-03-20']['type']);

        // Eid'l Adha: 2026-05-27
        $this->assertArrayHasKey('2026-05-27', $holidays2026);
        $this->assertSame("Eid'l Adha", $holidays2026['2026-05-27']['title']);
        $this->assertSame('Regular Holiday', $holidays2026['2026-05-27']['type']);

        // All Souls' Day: 2026-11-02
        $this->assertArrayHasKey('2026-11-02', $holidays2026);
        $this->assertSame("All Souls' Day", $holidays2026['2026-11-02']['title']);
        $this->assertSame('Special Holiday', $holidays2026['2026-11-02']['type']);
    }

    public function test_ensure_default_fixed_holidays_creates_statutory_fixed_days(): void
    {
        $this->service->ensureDefaultFixedHolidays();

        $fixedHolidays = [
            '01-01' => "New Year's Day",
            '04-09' => 'Araw ng Kagitingan',
            '05-01' => 'Labor Day',
            '06-12' => 'Independence Day',
            '08-21' => 'Ninoy Aquino Day',
            '11-01' => "All Saints' Day",
            '11-30' => 'Bonifacio Day',
            '12-08' => 'Feast of the Immaculate Conception',
            '12-24' => 'Christmas Eve',
            '12-25' => 'Christmas Day',
            '12-30' => 'Rizal Day',
            '12-31' => 'Last Day of the Year',
        ];

        foreach ($fixedHolidays as $monthDay => $title) {
            $this->assertDatabaseHas('holidays', [
                'branch_id' => null,
                'month_day' => $monthDay,
                'title' => $title,
                'is_recurring' => true,
            ]);
        }
    }

    public function test_cleanup_duplicate_holidays_merges_and_repoints_dtrs(): void
    {
        $regType = HolidayType::create(['type' => 'Regular Holiday', 'rate' => 200]);
        $specType = HolidayType::create(['type' => 'Special Holiday', 'rate' => 30]);

        // Create duplicate holidays on 2026-08-31
        $canonical = Holiday::create([
            'title' => 'National Heroes Day',
            'date' => '2026-08-31',
            'month_day' => '08-31',
            'holiday_type_id' => $regType->id,
            'is_recurring' => false,
            'source' => 'manual',
        ]);

        $duplicate = Holiday::create([
            'title' => 'Araw ng mga Bayani',
            'date' => '2026-08-31',
            'month_day' => '08-31',
            'holiday_type_id' => $specType->id, // misclassified
            'is_recurring' => false,
            'source' => 'philippines-api',
        ]);

        // Attach DTRs to the duplicate holiday
        $dtr1 = Dtr::create(['employee_id' => 10, 'date_in' => '2026-08-31', 'holiday_id' => $duplicate->id]);
        $dtr2 = Dtr::create(['employee_id' => 11, 'date_in' => '2026-08-31', 'holiday_id' => $duplicate->id]);

        $merged = $this->service->cleanupDuplicateHolidays(2026);

        $this->assertSame(1, $merged);

        // Verify duplicate row was deleted
        $this->assertDatabaseMissing('holidays', ['id' => $duplicate->id]);

        // Verify canonical row survived with correct type
        $this->assertDatabaseHas('holidays', [
            'id' => $canonical->id,
            'title' => 'National Heroes Day',
            'holiday_type_id' => $regType->id,
        ]);

        // Verify DTRs were re-pointed to canonical ID
        $this->assertSame($canonical->id, $dtr1->fresh()->holiday_id);
        $this->assertSame($canonical->id, $dtr2->fresh()->holiday_id);
    }

    public function test_sync_holidays_for_year_produces_twenty_official_holidays(): void
    {
        $result = $this->service->syncHolidaysForYear(2026);

        $this->assertGreaterThanOrEqual(20, $result['saved']);

        $holidays = Holiday::whereNull('branch_id')->with('type')->get();

        // Ensure no date has more than 1 national holiday
        $grouped = $holidays->groupBy(fn ($h) => Carbon::parse($h->date)->toDateString());
        foreach ($grouped as $date => $records) {
            $this->assertCount(1, $records, "Duplicate holiday found on date {$date}");
        }

        // Check statutory classifications
        $christmasEve = $holidays->first(fn ($h) => Carbon::parse($h->date)->toDateString() === '2026-12-24');
        $this->assertNotNull($christmasEve);
        $this->assertSame('Special Holiday', $christmasEve->type->type);

        $chineseNewYear = $holidays->first(fn ($h) => Carbon::parse($h->date)->toDateString() === '2026-02-17');
        $this->assertNotNull($chineseNewYear);
        $this->assertSame('Special Holiday', $chineseNewYear->type->type);

        $maundyThursday = $holidays->first(fn ($h) => Carbon::parse($h->date)->toDateString() === '2026-04-02');
        $this->assertNotNull($maundyThursday);
        $this->assertSame('Regular Holiday', $maundyThursday->type->type);

        $goodFriday = $holidays->first(fn ($h) => Carbon::parse($h->date)->toDateString() === '2026-04-03');
        $this->assertNotNull($goodFriday);
        $this->assertSame('Regular Holiday', $goodFriday->type->type);

        $eidFitr = $holidays->first(fn ($h) => Carbon::parse($h->date)->toDateString() === '2026-03-20');
        $this->assertNotNull($eidFitr);
        $this->assertSame('Regular Holiday', $eidFitr->type->type);

        $eidAdha = $holidays->first(fn ($h) => Carbon::parse($h->date)->toDateString() === '2026-05-27');
        $this->assertNotNull($eidAdha);
        $this->assertSame('Regular Holiday', $eidAdha->type->type);
    }
}
