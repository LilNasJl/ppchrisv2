<?php

namespace App\Filament\Pages;

use App\Services\Biometrics\BiometricsSyncDtrExport;
use Filament\Notifications\Notification;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function exportDtrExcel(): ?BinaryFileResponse
    {
        abort_unless(static::canAccess(), 403);
        $sync = $this->detailSync();
        abort_unless($sync && $sync->sync_type === 'payroll', 404);

        try {
            $path = app(BiometricsSyncDtrExport::class)->create($sync);
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title('DTR export unavailable')->body('The original device export could not be downloaded or verified.')->danger()->send();

            return null;
        }

        return response()->download($path, 'dtr-sync-'.$sync->id.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
