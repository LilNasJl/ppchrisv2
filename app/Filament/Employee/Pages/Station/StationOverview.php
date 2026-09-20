<?php

namespace App\Filament\Employee\Pages\Station;

use App\Filament\Employee\Widgets\StationBranchAttendanceChart;
use App\Models\Employee;
use App\Services\StationManagementAccess;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Override;
use UnitEnum;

class StationOverview extends Page
{
    protected string $view = 'filament.employee.pages.station.station-overview';

    protected static ?string $slug = 'station/overview';

    protected static ?string $title = 'Station Overview';

    protected static ?string $navigationLabel = 'Overview';

    protected static string|UnitEnum|null $navigationGroup = 'Station Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return StationManagementAccess::canAccessStationManagement(auth()->user());
    }

    public function mount(): void
    {
        if (! StationManagementAccess::canAccessStationManagement(auth()->user())) {
            abort(403, 'Unauthorized access to Station Management.');
        }
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function branchCount(): int
    {
        return count($this->assignedBranchIds());
    }

    public function employeeCount(): int
    {
        $branchIds = $this->assignedBranchIds();

        if ($branchIds === []) {
            return 0;
        }

        return Employee::query()
            ->whereIn('branch_id', $branchIds)
            ->activeEmployment()
            ->count();
    }

    #[Override]
    protected function getFooterWidgets(): array
    {
        return [
            StationBranchAttendanceChart::class,
        ];
    }

    protected function assignedBranchIds(): array
    {
        return StationManagementAccess::getManagedBranchIds(auth()->user());
    }
}
