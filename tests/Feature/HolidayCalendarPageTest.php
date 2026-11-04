<?php

namespace Tests\Feature;

use App\Filament\Pages\HolidayCalendar;
use App\Models\Holiday;
use App\Models\HolidayType;
use App\Models\User;
use App\Services\PhilippineHolidayService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class HolidayCalendarPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Gate::before(fn () => true);

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Test Admin');
            $table->string('email')->default('admin@example.com');
            $table->string('password')->default('secret');
            $table->string('role')->default('admin');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('branch_name')->default('Main Office');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->unsignedInteger('uid')->nullable();
            $table->string('firstname')->default('John');
            $table->string('lastname')->default('Doe');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('action_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_role')->nullable();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('model_label')->nullable();
            $table->string('record_label')->nullable();
            $table->string('summary')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->json('changed_data')->nullable();
            $table->timestamps();
        });

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

    public function test_holiday_calendar_page_mount_seeds_fixed_holidays(): void
    {
        $user = User::create(['role' => 'admin']);
        $this->actingAs($user);

        Livewire::test(HolidayCalendar::class)
            ->assertSuccessful();

        $this->assertDatabaseHas('holidays', [
            'month_day' => '01-01',
            'title' => "New Year's Day",
            'is_recurring' => true,
        ]);

        $this->assertDatabaseHas('holidays', [
            'month_day' => '12-25',
            'title' => 'Christmas Day',
            'is_recurring' => true,
        ]);
    }

    public function test_fetch_philippine_holidays_action(): void
    {
        $user = User::create(['role' => 'admin']);
        $this->actingAs($user);

        $year = now('Asia/Manila')->year;
        Livewire::test(HolidayCalendar::class)
            ->set('calendarMonth', "{$year}-01-01")
            ->call('fetchPhilippineHolidays')
            ->assertNotified('Philippine holidays updated');

        // Check that Christmas Eve was synced as Special Holiday
        $christmasEve = Holiday::whereNull('branch_id')
            ->whereDate('date', "{$year}-12-24")
            ->with('type')
            ->first();

        $this->assertNotNull($christmasEve);
        $this->assertSame('Special Holiday', $christmasEve->type->type);
    }
}
