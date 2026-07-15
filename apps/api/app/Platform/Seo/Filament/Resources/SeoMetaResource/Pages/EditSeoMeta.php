<?php

namespace App\Platform\Seo\Filament\Resources\SeoMetaResource\Pages;

use App\Platform\Seo\Filament\Resources\SeoMetaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSeoMeta extends EditRecord
{
    protected static string $resource = SeoMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
