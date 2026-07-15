<?php

namespace App\Platform\Notifications\Filament\Resources\NotificationTemplateResource\Pages;

use App\Platform\Notifications\Filament\Resources\NotificationTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTemplate extends EditRecord
{
    protected static string $resource = NotificationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
