<?php

namespace App\Filament\Pages;

class BiometricsSyncDetails extends BiometricsSync
{
    protected static ?string $slug = 'biometrics-sync/details';

    protected static ?string $title = 'Synchronization Details';

    public function mount(): void
    {
        parent::mount();

        $syncId = request()->query('syncId');
        $this->detailSyncId = is_string($syncId) ? $syncId : null;
        abort_unless($this->detailSync(), 404);
    }

    public function detailsReturnUrl(): string
    {
        return BiometricsSync::getUrl(['device' => $this->device]);
    }
}
