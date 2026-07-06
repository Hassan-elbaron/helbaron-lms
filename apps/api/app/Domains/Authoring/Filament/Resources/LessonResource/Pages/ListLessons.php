<?php

namespace App\Domains\Authoring\Filament\Resources\LessonResource\Pages;

use App\Domains\Authoring\Filament\Resources\LessonResource;
use Filament\Resources\Pages\ListRecords;

class ListLessons extends ListRecords
{
    protected static string $resource = LessonResource::class;
}
