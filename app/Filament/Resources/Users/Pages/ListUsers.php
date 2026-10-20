<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Pages\EmployeeDetails;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
    public function mount(): void
    {
        parent::mount();

        $this->redirect(EmployeeDetails::getUrl());
    }
}
