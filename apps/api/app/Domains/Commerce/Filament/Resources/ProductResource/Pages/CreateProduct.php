<?php

namespace App\Domains\Commerce\Filament\Resources\ProductResource\Pages;

use App\Domains\Commerce\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
