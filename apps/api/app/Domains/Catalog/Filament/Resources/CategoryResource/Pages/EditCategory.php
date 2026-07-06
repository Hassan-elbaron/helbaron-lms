<?php

namespace App\Domains\Catalog\Filament\Resources\CategoryResource\Pages;

use App\Domains\Catalog\Filament\Resources\CategoryResource;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;
}
