<?php

namespace App\Domains\Crm\Filament\Resources\LeadResource\Pages;

use App\Domains\Crm\Filament\Resources\LeadResource;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;
}
