<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected string $view = 'filament.resources.roles.pages.list-roles';

    protected function getActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Role')
                ->icon('heroicon-o-plus'),
        ];
    }
}
