<?php

namespace App\Filament\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class KpiLogin extends BaseLogin
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

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->placeholder('Enter your KPI username')
            ->prefixIcon(Heroicon::UserCircle)
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
            ->label('Password')
            ->placeholder('Enter your password')
            ->prefixIcon(Heroicon::LockClosed)
            ->prefixIconColor('primary')
            ->password()
            ->revealable()
            ->autocomplete('current-password')
            ->required();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => trim((string) ($data['username'] ?? '')),
            'password' => $data['password'],
            'is_active' => true,
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => 'The provided KPI credentials are incorrect or the account is disabled.',
        ]);
    }
}
