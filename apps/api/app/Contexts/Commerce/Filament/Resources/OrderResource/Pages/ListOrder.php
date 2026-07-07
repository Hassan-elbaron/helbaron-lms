<?php

namespace App\Contexts\Commerce\Filament\Resources\OrderResource\Pages;

use App\Contexts\Commerce\Filament\Resources\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ListOrder extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
