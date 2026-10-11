<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected string $view = 'filament.resources.announcements.pages.announcement-form';

    public function getSubheading(): string
    {
        return 'Draft or publish a company memorandum for employees.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['is_published'] ?? false) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
