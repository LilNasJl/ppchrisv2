<?php

namespace App\Filament\Employee\Pages\Station;

use App\Models\PayrollPeriod;
use App\Services\StationManagementAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class StationDtrImport extends Page
{
    protected string $view = 'filament.employee.pages.station.station-dtr-import';

    protected static ?string $slug = 'station/dtr-import';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'DTR Importer';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowUpTray;

    public static function canAccess(): bool
    {
        return StationManagementAccess::canAccessStationManagement(auth()->user());
    }

    public function mount(): void
    {
        abort_unless(StationManagementAccess::canAccessStationManagement(auth()->user()), 403);
    }

    public function getIframeUrl(): string
    {
        return asset('page/hr_atttendance_viewer.html').'?'.http_build_query([
            'endpoint' => route('sicrc_tools.import.dtr_preview'),
            'dtr_only' => 1,
        ]);
    }

    /**
     * @return array<int, array{value:string, label:string}>
     */
    public function getPayrollPeriodOptions(): array
    {
        return PayrollPeriod::query()
            ->where('is_locked', false)
            ->newestFirst()
            ->get(['id', 'title'])
            ->map(fn (PayrollPeriod $period): array => [
                'value' => (string) $period->getKey(),
                'label' => (string) $period->title,
            ])
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return to Station D.T.R')
                ->icon(Heroicon::ArrowLeft)
                ->url(ManageStationDtr::getUrl()),
        ];
    }
}
