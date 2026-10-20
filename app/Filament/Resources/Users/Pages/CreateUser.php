<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Pages\EmployeeDetails;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    protected string $view = 'filament.resources.users.pages.create-user';

    public ?string $returnUrl = null;

    public function mount(): void
    {
        parent::mount();

        $this->returnUrl = $this->normalizeReturnUrl(request()->query('returnUrl'));
    }

    public function getTitle(): string
    {
        return 'New Employee Account';
    }

    public function getSubheading(): string
    {
        return 'Register personal profile, work assignment, payroll setup, and system login credentials.';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->color('gray')
                ->url(fn (): string => $this->getResolvedReturnUrl()),
        ];
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

    public function getResolvedReturnUrl(): string
    {
        return $this->returnUrl ?: EmployeeDetails::getUrl();
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

    protected function getRedirectUrl(): string
    {
        return $this->getResolvedReturnUrl();
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Create Employee Account')
            ->icon(Heroicon::Check);
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancel')
            ->color('gray')
            ->url(fn (): string => $this->getResolvedReturnUrl());
    }
}
