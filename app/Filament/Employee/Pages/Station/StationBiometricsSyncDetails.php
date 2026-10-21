<?php

namespace App\Filament\Employee\Pages\Station;

use App\Services\StationManagementAccess;

class StationBiometricsSyncDetails extends \App\Filament\Pages\BiometricsSyncDetails
{
    protected static ?string $slug = 'station/biometrics-sync/details';

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

    public function detailsReturnUrl(): string
    {
        return StationBiometricsSync::getUrl(['device' => $this->device]);
    }
}
