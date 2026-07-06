<?php

namespace App\Domains\Crm\Filament\Resources\LeadResource\Pages;

use App\Domains\Crm\Filament\Resources\LeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;
}
