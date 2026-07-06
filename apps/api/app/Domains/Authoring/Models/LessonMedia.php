<?php

namespace App\Domains\Authoring\Models;

use App\Domains\Authoring\Database\Factories\LessonMediaFactory;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Media METADATA for a lesson (Mux/S3 identifiers + descriptive fields). Contains no signing
 * or playback logic — Learning issues signed URLs from these references later.
 */
class LessonMedia extends Model
{
    /** @use HasFactory<LessonMediaFactory> */
    use HasFactory;

    use HasPublicId;

    protected $table = 'lesson_media';

    protected $fillable = [
        'lesson_id', 'mux_asset_id', 'mux_playback_id', 's3_key', 'mime_type', 'duration', 'filesize',
    ];

    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'filesize' => 'integer',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    protected static function newFactory(): LessonMediaFactory
    {
        return LessonMediaFactory::new();
    }
}
