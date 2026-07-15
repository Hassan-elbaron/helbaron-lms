<?php

namespace App\Platform\Shared\Filament\Resources\AuditLogResource\Pages;

use App\Platform\Shared\Filament\Resources\AuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditLog extends ListRecords
{
    protected static string $resource = AuditLogResource::class;
}
