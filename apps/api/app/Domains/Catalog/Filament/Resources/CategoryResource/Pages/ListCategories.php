<?php

namespace App\Domains\Catalog\Filament\Resources\CategoryResource\Pages;

use App\Domains\Catalog\Filament\Resources\CategoryResource;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;
}
