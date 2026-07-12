<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class EmployeeImport extends Page
{
    protected string $view = 'filament.pages.employee-import';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Import Employees';

    public ?string $returnUrl = null;

    public function mount(): void
    {
        $this->returnUrl = $this->normalizeReturnUrl(request()->query('returnUrl'));
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => $this->getReturnUrl()),

            Action::make('importHistory')
                ->label('Import History')
                ->icon(Heroicon::QueueList)
                ->url(fn (): string => UserResource::getUrl('import-history', [
                    'returnUrl' => $this->getReturnUrl(),
                ])),
        ];
    }

    protected function getReturnUrl(): string
    {
        return $this->returnUrl ?: EmployeeDetails::getUrl();
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
