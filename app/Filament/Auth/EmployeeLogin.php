<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class EmployeeLogin extends Login
{
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

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => User::companyUsernameFromUid($data['username'] ?? null),
            'password' => $data['password'],
        ];
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
