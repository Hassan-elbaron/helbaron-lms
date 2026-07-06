<?php

namespace App\Domains\Learning\Filament\Resources\EnrollmentResource\Pages;

use App\Domains\Learning\Filament\Resources\EnrollmentResource;
use Filament\Resources\Pages\ListRecords;

class ListEnrollments extends ListRecords
{
    protected static string $resource = EnrollmentResource::class;
}
