<?php

namespace App\Services\Biometrics;

use Illuminate\Http\Request;

class SyncSignature
{
    public static function headers(string $method, string $path, string $body = ''): array
    {
        $time = (string) time();

        return ['X-Sync-Time' => $time, 'X-Sync-Signature' => self::sign($method, $path, $body, $time)];
    }

    private static function sign(string $method, string $path, string $body, string $time): string
    {
        return hash_hmac('sha256', strtoupper($method)."\n".$path."\n".$time."\n".hash('sha256', $body), (string) config('biometrics.secret'));
    }

    public static function authorize(Request $request): void
    {
        abort_if(strlen((string) config('biometrics.secret')) < 32, 503, 'Biometrics integration is not configured.');
        $time = (string) $request->header('X-Sync-Time', '');
        abort_unless(ctype_digit($time) && abs(time() - (int) $time) <= 300, 401, 'Invalid integration timestamp.');
        abort_unless(hash_equals(self::sign($request->method(), $request->getRequestUri(), $request->getContent(), $time), (string) $request->header('X-Sync-Signature', '')), 401, 'Invalid integration signature.');
    }
}
