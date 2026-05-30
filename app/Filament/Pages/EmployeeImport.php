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

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(UserResource::getUrl('index')),
        ];
    }
}
