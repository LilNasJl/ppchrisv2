<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DtrImportTable;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class DtrImport extends Page
{
    protected string $view = 'filament.pages.dtr-import';
    protected static bool $shouldRegisterNavigation = false;    
    protected static ?string $title = "D.T.R Importer";

    
    public function getHeaderActions(): array
    {
        return [
             Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(Dtr::getUrl())  
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            DtrImportTable::class
        ];
    }

}
