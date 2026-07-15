<?php

namespace App\Domains\Catalog\Filament\Resources\CourseAnnouncementResource\Pages;

use App\Domains\Catalog\Filament\Resources\CourseAnnouncementResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = CourseAnnouncementResource::class;

    /** Stamp the acting admin as the author. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] ??= Filament::auth()->id();

        return $data;
    }
}
