<?php

namespace App\Platform\Identity\Filament\Resources\UserResource\Pages;

use App\Platform\Identity\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
