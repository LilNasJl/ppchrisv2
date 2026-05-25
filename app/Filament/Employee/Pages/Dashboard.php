<?php

namespace App\Filament\Employee\Pages;

use App\Models\Activity;
use App\Models\Announcement;
use App\Services\HolidayResolver;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class Dashboard extends Page
{
    protected string $view = 'filament.employee.pages.dashboard';

    protected static ?string $slug = 'dashboard';

    protected static ?string $title = 'Dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static ?int $navigationSort = 1;

    public function getAnnouncementsProperty(): Collection
    {
        return Announcement::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->limit(3)
            ->get();
    }

    public function getUpcomingHolidaysProperty(): Collection
    {
        return app(HolidayResolver::class)->upcomingNationalHolidays(5);
    }

    public function getUpcomingActivitiesProperty(): Collection
    {
        return Activity::query()
            ->whereDate('date_to', '>=', now()->toDateString())
            ->orderBy('date_from')
            ->limit(5)
            ->get();
    }
}
