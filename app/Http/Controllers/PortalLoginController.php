<?php

namespace App\Http\Controllers;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PortalLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100', 'regex:/^\S+$/'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $identifier = User::normalizeUsername($data['username']);
        $username = preg_match('/^(?:PF-?)?\d{1,4}$/i', $identifier)
            ? User::companyUsernameFromUid(strtoupper($identifier))
            : $identifier;
        if (strcasecmp($username, (string) config('auth.master_admin.username', 'masteradmin')) === 0) {
            $username = (string) config('auth.master_admin.username', 'masteradmin');
        }
        $key = 'portal-login:'.hash('sha256', Str::lower($username).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'login' => 'Too many sign-in attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        $this->syncConfiguredMasterAdmin($username, $data['password']);

        /** @var SessionGuard $guard */
        $guard = Auth::guard('web');
        $credentials = ['username' => $username, 'password' => $data['password']];
        $provider = $guard->getProvider();
        $user = $provider->retrieveByCredentials($credentials);
        $panel = $user instanceof User ? match ($user->role) {
            'admin', 'hr' => Filament::getPanel('hr'),
            'employee' => Filament::getPanel('employee'),
            default => null,
        } : null;

        if (! $user || ! $provider->validateCredentials($user, $credentials) || ! $panel || ! $user->canAccessPanel($panel)) {
            event(new Failed('web', $user, $credentials));
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'login' => 'The provided credentials are incorrect or the account is unavailable.',
            ]);
        }

        RateLimiter::clear($key);
        $guard->login($user, (bool) ($data['remember'] ?? false));
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        if (method_exists($guard, 'logoutOtherDevices')) {
            try {
                $guard->logoutOtherDevices($data['password']);
            } catch (Throwable) {
                // The database-session cleanup below still keeps the current session active.
            }
        }

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getAuthIdentifier())
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        return redirect()->to($panel->getUrl());
    }

    private function syncConfiguredMasterAdmin(string $username, string $password): void
    {
        $configuredPassword = config('auth.master_admin.password');

        if (
            filled($configuredPassword)
            && strcasecmp($username, (string) config('auth.master_admin.username', 'masteradmin')) === 0
            && hash_equals((string) $configuredPassword, $password)
        ) {
            User::syncMasterAdminFromConfig();
        }
    }
}
