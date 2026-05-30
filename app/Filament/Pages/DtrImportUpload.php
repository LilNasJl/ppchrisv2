<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class DtrImportUpload extends Page
{
    protected string $view = 'filament.pages.dtr-import-upload';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Import D.T.R Entries';

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(DtrImport::getUrl()),
        ];
    }
}
