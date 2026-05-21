<?php

namespace App\Filament\Auth;

use App\Models\User;
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
