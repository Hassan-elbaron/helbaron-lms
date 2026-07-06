<?php

namespace App\Domains\Catalog\Filament\Resources\CourseResource\Pages;

use App\Domains\Catalog\Filament\Resources\CourseResource;
use Filament\Resources\Pages\ListRecords;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;
}
