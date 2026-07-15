<?php

namespace App\Platform\Navigation\Filament\Resources\NavItemResource\Pages;

use App\Platform\Navigation\Filament\Resources\NavItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNavItem extends EditRecord
{
    protected static string $resource = NavItemResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
