<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Notifications\RecordUpdatedNotification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $user->notify(new RecordUpdatedNotification(
                'User created',
                'A new user record has been created.'
            ));
        }
    }
}
