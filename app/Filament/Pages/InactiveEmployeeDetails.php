<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Override;

class InactiveEmployeeDetails extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.inactive-employee-details';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'inactive-employees';

    protected static ?string $title = 'Inactive Employees';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserMinus;

    public function getTitle(): string
    {
        return 'Inactive Employees';
    }

    public function getHeader(): ?View
    {
        return view('filament.pages.partials.inactive-employee-details-header', [
            'headerActions' => $this->getCachedHeaderActions(),
        ]);
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('returnToEmployeeDetails')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(EmployeeDetails::getUrl()),
        ];
    }
}
