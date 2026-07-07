<?php

namespace App\Contexts\Commerce\Filament\Resources\ProductResource\Pages;

use App\Contexts\Commerce\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
