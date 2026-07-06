<?php

namespace App\Domains\Authoring\Filament\Resources\LessonResource\Pages;

use App\Domains\Authoring\Filament\Resources\LessonResource;
use Filament\Resources\Pages\EditRecord;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;
}
