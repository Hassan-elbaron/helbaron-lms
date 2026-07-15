<?php

namespace App\Platform\Seo\Filament\Resources\SeoMetaResource\Pages;

use App\Platform\Seo\Filament\Resources\SeoMetaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSeoMetas extends ListRecords
{
    protected static string $resource = SeoMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
