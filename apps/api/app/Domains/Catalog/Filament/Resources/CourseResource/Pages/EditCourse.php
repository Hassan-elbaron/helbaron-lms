<?php

namespace App\Domains\Catalog\Filament\Resources\CourseResource\Pages;

use App\Domains\Catalog\Filament\Resources\CourseResource;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;
}
