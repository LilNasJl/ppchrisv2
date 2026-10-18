<?php

namespace App\Http\Middleware;

use App\Services\Biometrics\SyncSignature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateBiometricsSync
{
    public function handle(Request $request, Closure $next): Response
    {
        SyncSignature::authorize($request);

        return $next($request);
    }
}
