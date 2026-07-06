<?php

namespace App\Domains\Certification\Filament\Resources\BadgeResource\Pages;

use App\Domains\Certification\Filament\Resources\BadgeResource;
use Filament\Resources\Pages\EditRecord;

class EditBadge extends EditRecord
{
    protected static string $resource = BadgeResource::class;
}
