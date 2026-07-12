<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->previousUrl = $this->normalizeReturnUrl(request()->query('returnUrl')) ?: $this->previousUrl;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['name'] = $data['username'] ?? 'Employee Account';

        return $data;
    }

    protected function afterCreate(): void
    {
        $employee = $this->record->employee;
        $username = User::companyUsernameFromUid($employee?->uid);

        if (filled($username)) {
            $this->record->forceFill([
                'username' => $username,
                'name' => $username,
            ])->saveQuietly();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->normalizeReturnUrl(request()->query('returnUrl')) ?: parent::getRedirectUrl();
    }

    protected function normalizeReturnUrl(mixed $url): ?string
    {
        if (! is_string($url) || blank($url)) {
            return null;
        }

        $appUrl = url('/');

        return str_starts_with($url, $appUrl) || str_starts_with($url, '/')
            ? $url
            : null;
    }
}
