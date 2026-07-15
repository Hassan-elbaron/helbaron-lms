<?php

namespace App\Platform\Notifications\Filament\Resources\NotificationTemplateResource\Pages;

use App\Platform\Notifications\Filament\Resources\NotificationTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTemplate extends ListRecords
{
    protected static string $resource = NotificationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
