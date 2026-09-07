<?php

namespace App\Filament\Auth;

use App\Http\Responses\PortalLoginResponse;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Models\Contracts\FilamentUser;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Throwable;

class Login extends BaseLogin
{
    protected string $view = 'filament.auth.login';

    protected static string $layout = 'filament.auth.layout';

    public function mount(): void
    {
        /** @var SessionGuard $hrGuard */
        $hrGuard = Auth::guard('web');
        /** @var SessionGuard $sicRcGuard */
        $sicRcGuard = Auth::guard('sicrc');
        $currentPanelId = Filament::getCurrentOrDefaultPanel()->getId();

        if (($currentPanelId === 'hr') && $hrGuard->check()) {
            redirect()->to(Filament::getPanel('hr')->getUrl());

            return;
        }

        if (($currentPanelId === 'sicrc') && $sicRcGuard->check()) {
            redirect()->to(Filament::getPanel('sicrc')->getUrl());

            return;
        }

        if ($hrGuard->check()) {
            redirect()->to(Filament::getPanel('hr')->getUrl());

            return;
        }

        if ($sicRcGuard->check()) {
            redirect()->to(Filament::getPanel('sicrc')->getUrl());

            return;
        }

        if ($currentPanelId === 'sicrc') {
            redirect()->route('filament.hr.auth.login');

            return;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
        ]);
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->icon(Heroicon::ArrowRightOnRectangle);
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $username = User::normalizeUsername($data['username'] ?? null);
        $password = (string) ($data['password'] ?? '');
        $remember = (bool) ($data['remember'] ?? false);

        /** @var SessionGuard $hrGuard */
        $hrGuard = Auth::guard('web');
        /** @var SessionGuard $sicRcGuard */
        $sicRcGuard = Auth::guard('sicrc');

        $hrCredentials = [
            'username' => $username,
            'password' => $password,
        ];
        $sicRcCredentials = [
            'username' => $username,
            'password' => $password,
            'is_active' => true,
        ];

        $hrUser = $this->retrieveAuthorizedAccount($hrGuard, $hrCredentials, 'hr');
        $sicRcUser = $this->retrieveAuthorizedAccount($sicRcGuard, $sicRcCredentials, 'sicrc');

        if ($hrUser && $sicRcUser) {
            throw ValidationException::withMessages([
                'data.username' => 'This username is assigned to both HR and SIC / RC accounts. Contact an administrator to resolve the duplicate username.',
            ]);
        }

        if (! $hrUser && ! $sicRcUser) {
            $this->fireFailedEvent($hrGuard, null, $hrCredentials);
            $this->throwFailureValidationException();
        }

        if ($hrUser) {
            $sicRcGuard->logout();
            $hrGuard->login($hrUser, $remember);
            session()->regenerate();
            $this->closeOtherHrSessions($hrGuard, $hrUser, $password);

            return new PortalLoginResponse(Filament::getPanel('hr')->getUrl());
        }

        $hrGuard->logout();
        $sicRcGuard->login($sicRcUser, $remember);
        session()->regenerate();

        return new PortalLoginResponse(Filament::getPanel('sicrc')->getUrl());
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function retrieveAuthorizedAccount(SessionGuard $guard, array $credentials, string $panelId): ?Authenticatable
    {
        $provider = $guard->getProvider();
        $user = $provider->retrieveByCredentials($credentials);

        if (! $user || ! $provider->validateCredentials($user, $credentials)) {
            return null;
        }

        if ($user instanceof FilamentUser && ! $user->canAccessPanel(Filament::getPanel($panelId))) {
            return null;
        }

        return $user;
    }

    private function closeOtherHrSessions(SessionGuard $guard, Authenticatable $user, string $password): void
    {
        if (filled($password) && method_exists($guard, 'logoutOtherDevices')) {
            try {
                $guard->logoutOtherDevices($password);
            } catch (Throwable) {
                // Database-session cleanup below still keeps only the current HR session active.
            }
        }

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getAuthIdentifier())
                ->where('id', '!=', session()->getId())
                ->delete();
        }
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->placeholder('Enter your username')
            ->prefixIcon('heroicon-m-user-circle')
            ->prefixIconColor('primary')
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->rule('regex:/^\S+$/')
            ->validationMessages([
                'regex' => 'The username must not contain spaces.',
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/login.form.password.label'))
            ->placeholder('Enter your password')
            ->prefixIcon('heroicon-m-lock-closed')
            ->prefixIconColor('primary')
            ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="-1"> {{ __(\'filament-panels::auth/pages/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => User::normalizeUsername($data['username'] ?? null),
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => 'The provided HRIS Portal credentials are incorrect or the account is disabled.',
        ]);
    }
}
