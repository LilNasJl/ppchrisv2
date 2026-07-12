<?php

namespace App\Filament\Pages;

use App\Filament\Pages\EmployeeImport;
use App\Filament\Resources\Users\UserResource;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;
use UnitEnum;

class EmployeeDetails extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.employee-details';

    protected static ?string $title = 'Accounts & Records';

    protected static ?string $navigationLabel = 'Accounts & Records';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Identification;

    protected static string|UnitEnum|null $navigationGroup = 'Employee Management';

    protected static ?int $navigationSort = 1;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('importEmployee')
                    ->label('Import Employee')
                    ->icon(Heroicon::ArrowDownTray)
                    ->url(EmployeeImport::getUrl([
                        'returnUrl' => EmployeeDetails::getUrl(),
                    ])),

                Action::make('importHistory')
                    ->label('Import History')
                    ->icon(Heroicon::QueueList)
                    ->url(UserResource::getUrl('import-history', [
                        'returnUrl' => EmployeeDetails::getUrl(),
                    ])),

                Action::make('newEmployeeAccount')
                    ->label('New Employee Account')
                    ->icon(Heroicon::Plus)
                    ->url(UserResource::getUrl('create', [
                        'returnUrl' => EmployeeDetails::getUrl(),
                    ])),
            ])
                ->label('Manage Records')
                ->icon(Heroicon::Cog6Tooth)
                ->button()
                ->tooltip('Manage Records'),
        ];
    }

    // protected ?string $subheading = 'Manage and view employee profiles and employement data';

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Action::make('add')
    //             ->label('Add')
    //     ];
    // }

}
