<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PayrollRosterTable;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class PayrollRoster extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.payroll-roster';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Payroll Roster';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentList;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(Payroll::getUrl()),
        ];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            PayrollRosterTable::class,
        ];
    }
}
