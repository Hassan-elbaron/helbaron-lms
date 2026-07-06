<?php

namespace App\Domains\Commerce\Filament\Resources\ProductResource\Pages;

use App\Domains\Commerce\Filament\Resources\ProductResource;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;
}
