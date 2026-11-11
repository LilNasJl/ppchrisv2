<?php

namespace App\Filament\Resources\SystemUpdates\Pages;

use App\Filament\Resources\SystemUpdates\SystemUpdateResource;
use Filament\Resources\Pages\EditRecord;

class EditSystemUpdate extends EditRecord
{
    protected static string $resource = SystemUpdateResource::class;

    protected string $view = 'filament.resources.system-updates.pages.system-update-form';

    public function getSubheading(): string
    {
        return 'Update notice content, category, or target audience.';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['is_published'] = true;
        $data['published_at'] = $this->record->published_at ?? now();
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
