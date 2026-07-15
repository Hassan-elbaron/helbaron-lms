<?php

namespace App\Domains\Catalog\Filament\Resources\CourseAnnouncementResource\Pages;

use App\Domains\Catalog\Filament\Resources\CourseAnnouncementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAnnouncements extends ListRecords
{
    protected static string $resource = CourseAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
