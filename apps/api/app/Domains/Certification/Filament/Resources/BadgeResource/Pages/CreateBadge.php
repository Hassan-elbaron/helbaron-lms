<?php

namespace App\Domains\Certification\Filament\Resources\BadgeResource\Pages;

use App\Domains\Certification\Filament\Resources\BadgeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBadge extends CreateRecord
{
    protected static string $resource = BadgeResource::class;
}
