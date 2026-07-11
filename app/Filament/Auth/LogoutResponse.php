<?php

namespace App\Filament\Auth;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;

class LogoutResponse implements LogoutResponseContract
{
    private const REDIRECT_URL = 'https://hris.philfumes.net';

    public function toResponse($request): RedirectResponse
    {
        return redirect()->away(self::REDIRECT_URL);
    }
}
