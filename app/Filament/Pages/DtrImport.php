<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DtrImportTable;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class DtrImport extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.dtr-import';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'D.T.R Import History';

    public function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(DtrViewer::getUrl()),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            DtrImportTable::class,
        ];
    }
}
