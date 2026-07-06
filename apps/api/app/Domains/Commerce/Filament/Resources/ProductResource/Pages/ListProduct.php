<?php

namespace App\Domains\Commerce\Filament\Resources\ProductResource\Pages;

use App\Domains\Commerce\Filament\Resources\ProductResource;
use Filament\Resources\Pages\ListRecords;

class ListProduct extends ListRecords
{
    protected static string $resource = ProductResource::class;
}
