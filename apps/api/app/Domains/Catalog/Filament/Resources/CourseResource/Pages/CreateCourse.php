<?php

namespace App\Domains\Catalog\Filament\Resources\CourseResource\Pages;

use App\Domains\Catalog\Filament\Resources\CourseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;
}
