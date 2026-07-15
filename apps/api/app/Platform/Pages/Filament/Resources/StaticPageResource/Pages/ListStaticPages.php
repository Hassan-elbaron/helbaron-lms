<?php

namespace App\Platform\Pages\Filament\Resources\StaticPageResource\Pages;

use App\Platform\Pages\Filament\Resources\StaticPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaticPages extends ListRecords
{
    protected static string $resource = StaticPageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
