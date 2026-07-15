<?php

namespace App\Platform\Pages\Filament\Resources\StaticPageResource\Pages;

use App\Platform\Pages\Filament\Resources\StaticPageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateStaticPage extends CreateRecord
{
    protected static string $resource = StaticPageResource::class;

    /** Stamp the authoring admin on create. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] ??= Auth::id();

        return $data;
    }
}
