<?php

namespace App\Services\Biometrics;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BiometricsClient
{
    public function request(string $method, string $path, array $data = []): Response
    {
        $base = (string) config('biometrics.url');
        if ($base === '' || strlen((string) config('biometrics.secret')) < 32) {
            throw new \RuntimeException('Configure the BiometricsWeb URL and shared sync secret first.');
        }
        if (parse_url($base, PHP_URL_SCHEME) !== 'https' && ! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('BiometricsWeb requires HTTPS.');
        }
        $body = $method === 'GET' ? '' : json_encode($data, JSON_THROW_ON_ERROR);
        $fullPath = (parse_url($base, PHP_URL_PATH) ?: '').$path;
        $request = Http::connectTimeout(5)->timeout(str_ends_with($path, '/file') ? 60 : 20)->withoutRedirecting()->acceptJson()->withHeaders(SyncSignature::headers($method, $fullPath, $body));
        if ($method !== 'GET') {
            $request = $request->withBody($body, 'application/json');
        }

        return $request->send($method, $base.$path)->throw();
    }

    public function devices(): array
    {
        $devices = $this->request('GET', '/api/hris/devices')->json('data');
        if (! is_array($devices)) {
            throw new \RuntimeException('BiometricsWeb returned an invalid device list. Check the configured URL.');
        }

        return $devices;
    }

    public function cachedDevices(): array
    {
        return Cache::remember(
            'biometrics-device-list:'.hash('sha256', (string) config('biometrics.url')),
            15,
            fn (): array => $this->devices(),
        );
    }
}
