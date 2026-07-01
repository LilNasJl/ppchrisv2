<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Notifications\RecordUpdatedNotification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

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

        $users = User::all();

        foreach ($users as $user) {
            $user->notify(new RecordUpdatedNotification(
                'User created',
                'A new user record has been created.'
            ));
        }
    }
}
