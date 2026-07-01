<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Notifications\RecordUpdatedNotification;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->previousUrl = $this->normalizeReturnUrl(request()->query('returnUrl')) ?: $this->previousUrl;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $username = User::companyUsernameFromUid($this->record->employee?->uid);

        if (filled($username)) {
            $this->record->forceFill([
                'username' => $username,
                'name' => $username,
            ])->saveQuietly();
        }

        $users = User::all();

        foreach ($users as $user) {
            $user->notify(new RecordUpdatedNotification(
                'User updated',
                'A user record has been updated.'
            ));
        }
    }

    protected function normalizeReturnUrl(mixed $url): ?string
    {
        if (! is_string($url) || blank($url)) {
            return null;
        }

        $appUrl = url('/');

        if (str_starts_with($url, $appUrl) || str_starts_with($url, '/')) {
            return $url;
        }

        return null;
    }
}
