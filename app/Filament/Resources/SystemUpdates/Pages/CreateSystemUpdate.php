<?php

namespace App\Filament\Resources\SystemUpdates\Pages;

use App\Filament\Resources\SystemUpdates\SystemUpdateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSystemUpdate extends CreateRecord
{
    protected static string $resource = SystemUpdateResource::class;

    protected string $view = 'filament.resources.system-updates.pages.system-update-form';

    public function getSubheading(): string
    {
        return 'Create a new update entry, changelog batch, or operational reminder for HR and Employees.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $userId = auth()->id();

        $data['is_published'] = true;
        $data['published_at'] = now();
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;

        return $data;
    }
}
