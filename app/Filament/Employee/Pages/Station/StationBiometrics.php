<?php

namespace App\Filament\Employee\Pages\Station;

use App\Services\StationManagementAccess;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpKernel\Exception\HttpException;
use UnitEnum;

class StationBiometrics extends Page
{
    protected string $view = 'filament.employee.pages.station.station-biometrics';

    protected static ?string $slug = 'station/biometrics';

    protected static ?string $title = 'Biometrics';

    protected static ?string $navigationLabel = 'Biometrics';

    protected static string|UnitEnum|null $navigationGroup = 'Station Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::FingerPrint;

    protected static ?int $navigationSort = 4;

    public string $biometricsUrl = 'https://www.biometrics.philfumes.net';

    public static function canAccess(): bool
    {
        return StationManagementAccess::canAccessStationManagement(auth()->user());
    }

    public function mount(): void
    {
        if (! StationManagementAccess::canAccessStationManagement(auth()->user())) {
            throw new HttpException(403, 'Unauthorized access to Station Management.');
        }
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
