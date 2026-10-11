<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected string $view = 'filament.resources.announcements.pages.announcement-form';

    public function getSubheading(): string
    {
        return 'Update memorandum details and publication status.';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['is_published'] ?? false) && blank($this->record->published_at)) {
            $data['published_at'] = now();
        }

        if (! ($data['is_published'] ?? false)) {
            $data['published_at'] = null;
        }

        return $data;
    }
}
