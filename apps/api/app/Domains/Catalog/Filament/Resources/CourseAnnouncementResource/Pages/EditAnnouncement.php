<?php

namespace App\Domains\Catalog\Filament\Resources\CourseAnnouncementResource\Pages;

use App\Domains\Catalog\Filament\Resources\CourseAnnouncementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = CourseAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
