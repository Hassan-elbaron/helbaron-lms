<?php

namespace App\Domains\Authoring\Actions\Lesson;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\LessonMedia;
use App\Platform\Shared\Actions\BaseAction;

class UpsertLessonMediaAction extends BaseAction
{
    /**
     * Store/replace media METADATA for a lesson (Mux/S3 identifiers + descriptive fields).
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Lesson $lesson, array $data): LessonMedia
    {
        return $this->transaction(function () use ($lesson, $data): LessonMedia {
            return LessonMedia::updateOrCreate(
                ['lesson_id' => $lesson->id],
                [
                    'mux_asset_id' => $data['mux_asset_id'] ?? null,
                    'mux_playback_id' => $data['mux_playback_id'] ?? null,
                    's3_key' => $data['s3_key'] ?? null,
                    'mime_type' => $data['mime_type'] ?? null,
                    'duration' => $data['duration'] ?? null,
                    'filesize' => $data['filesize'] ?? null,
                ],
            );
        });
    }
}
