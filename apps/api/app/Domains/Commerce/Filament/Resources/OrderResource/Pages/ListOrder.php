<?php

namespace App\Domains\Commerce\Filament\Resources\OrderResource\Pages;

use App\Domains\Commerce\Filament\Resources\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ListOrder extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
