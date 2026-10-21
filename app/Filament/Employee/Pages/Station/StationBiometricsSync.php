<?php

namespace App\Filament\Employee\Pages\Station;

use App\Services\StationManagementAccess;

class StationBiometricsSync extends \App\Filament\Pages\BiometricsSync
{
    protected static ?string $slug = 'station/biometrics-sync';

    protected static ?string $title = 'Sync Devices';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->role === 'employee'
            && StationManagementAccess::canAccessStationManagement($user);
    }

    protected function permittedDeviceIds(): ?array
    {
        return StationManagementAccess::getManagedBiometricsDeviceIds(auth()->user());
    }

    public function canSyncEmployees(): bool
    {
        return false;
    }

    public function returnUrl(): string
    {
        return ManageStationDtr::getUrl();
    }

    public function detailsUrl(string $syncId): string
    {
        return StationBiometricsSyncDetails::getUrl(['device' => $this->device, 'syncId' => $syncId]);
    }
}
