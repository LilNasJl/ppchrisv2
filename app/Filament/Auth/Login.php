<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Throwable;

class Login extends BaseLogin
{
    protected string $view = 'filament.auth.login';

    protected static string $layout = 'filament.auth.layout';

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

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
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
            'data.username' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }
}
