<?php

namespace App\Domains\Certification\Filament\Resources\BadgeResource\Pages;

use App\Domains\Certification\Filament\Resources\BadgeResource;
use Filament\Resources\Pages\ListRecords;

class ListBadge extends ListRecords
{
    protected static string $resource = BadgeResource::class;
}
