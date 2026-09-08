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
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmployeeLogin extends BaseLogin
{
    protected string $view = 'filament.auth.login';

    protected static string $layout = 'filament.auth.layout';

    public function mount(): void
    {
        $user = Filament::auth()->user();

        if ($user && $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            redirect()->intended(Filament::getUrl());

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
                // Database-session cleanup below still keeps only the current employee session active.
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

    protected function getIdentifierLabel(): string
    {
        return 'Company ID';
    }

    protected function getIdentifierPlaceholder(): string
    {
        return '';
    }

    protected function normalizeIdentifier(?string $identifier): ?string
    {
        return User::companyUsernameFromUid($identifier);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Company ID')
            ->placeholder('PF0001')
            ->prefixIcon('heroicon-m-identification')
            ->prefixIconColor('primary')
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->rule('regex:/^(?:PF-?)?\d{1,4}$/i')
            ->validationMessages([
                'regex' => 'Enter a valid company ID, such as PF-0001.',
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/login.form.password.label'))
            ->placeholder('Enter your password')
            ->prefixIcon(Heroicon::LockClosed)
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
            'username' => User::companyUsernameFromUid($data['username'] ?? null),
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => 'The provided Self-Service credentials are incorrect or the employee account is disabled.',
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Employee Login';
    }

    public function getHeading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getHeading();
        }

        return 'HRIS: SELF SERVICE';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getSubheading();
        }

        return 'Use your company ID without the dash.';
    }
}
