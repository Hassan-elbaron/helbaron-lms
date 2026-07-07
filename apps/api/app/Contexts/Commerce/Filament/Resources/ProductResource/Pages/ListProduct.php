<?php

namespace App\Contexts\Commerce\Filament\Resources\ProductResource\Pages;

use App\Contexts\Commerce\Filament\Resources\ProductResource;
use Filament\Resources\Pages\ListRecords;

class ListProduct extends ListRecords
{
    protected static string $resource = ProductResource::class;
}
