<?php

namespace App\Filament\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\DB;
use Throwable;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $password = $this->data['password'] ?? null;

        $response = parent::authenticate();

        if (! $response) {
            return null;
        }

        /** @var SessionGuard $guard */
        $guard = Filament::auth();
        $user = $guard->user();

        if (! $user) {
            return $response;
        }

        if (filled($password) && method_exists($guard, 'logoutOtherDevices')) {
            try {
                $guard->logoutOtherDevices($password);
            } catch (Throwable) {
                // Database-session cleanup below still keeps only the current session active.
            }
        }

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getAuthIdentifier())
                ->where('id', '!=', session()->getId())
                ->delete();
        }

        return $response;
    }
}
