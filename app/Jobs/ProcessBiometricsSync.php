<?php

namespace App\Jobs;

use App\Services\Biometrics\BiometricsSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessBiometricsSync implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 240;

    public function __construct(public string $requestId)
    {
        $this->onConnection('biometrics')->onQueue('biometrics');
    }

    public function backoff(): array
    {
        return [15, 60, 180];
    }

    public function handle(BiometricsSyncService $service): void
    {
        $service->advance($this->requestId);
    }
}
