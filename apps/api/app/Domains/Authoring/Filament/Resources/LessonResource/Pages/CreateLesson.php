<?php

namespace App\Domains\Authoring\Filament\Resources\LessonResource\Pages;

use App\Domains\Authoring\Filament\Resources\LessonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLesson extends CreateRecord
{
    protected static string $resource = LessonResource::class;
}
