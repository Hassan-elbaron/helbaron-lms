<?php

namespace App\Platform\Navigation\Filament\Resources\NavItemResource\Pages;

use App\Platform\Navigation\Filament\Resources\NavItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNavItems extends ListRecords
{
    protected static string $resource = NavItemResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
