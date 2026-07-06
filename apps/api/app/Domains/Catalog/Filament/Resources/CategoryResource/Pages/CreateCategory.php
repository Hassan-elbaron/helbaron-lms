<?php

namespace App\Domains\Catalog\Filament\Resources\CategoryResource\Pages;

use App\Domains\Catalog\Filament\Resources\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
}
