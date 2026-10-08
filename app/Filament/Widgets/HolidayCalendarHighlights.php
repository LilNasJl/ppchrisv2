<?php

namespace App\Filament\Widgets;

use App\Models\Holiday;
use Filament\Widgets\Widget;

class HolidayCalendarHighlights extends Widget
{
    protected string $view = 'filament.widgets.holiday-calendar-highlights';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $today = now()->startOfDay();

        return [
            'today' => $today,
            'holidays' => Holiday::query()
                ->with(['branch', 'type'])
                ->where(function ($query) use ($today): void {
                    $query
                        ->whereDate('date', $today->toDateString())
                        ->orWhere(function ($query) use ($today): void {
                            $query
                                ->where('is_recurring', true)
                                ->where('month_day', $today->format('m-d'));
                        });
                })
                ->orderBy('title')
                ->get(),
        ];
    }
}
