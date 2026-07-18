<?php

namespace App\Domains\Authoring\Enums;

/**
 * Supported lesson content types. Playback/rendering is NOT handled here — Authoring only
 * stores the type and (for media types) the media metadata.
 */
enum LessonType: string
{
    case Video = 'video';
    case Audio = 'audio';
    case Article = 'article';
    case Pdf = 'pdf';
    case Download = 'download';
    case ExternalLink = 'external_link';
    case QuizPlaceholder = 'quiz_placeholder';
    /**
     * A lesson backed by a real Assessment record, referenced via `lessons.assessment_id`.
     * Distinct from QuizPlaceholder, which is inert authored text with no engine behind it and is
     * retained for existing content.
     */
    case Quiz = 'quiz';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Video',
            self::Audio => 'Audio',
            self::Article => 'Article',
            self::Pdf => 'PDF',
            self::Download => 'Download',
            self::ExternalLink => 'External Link',
            self::QuizPlaceholder => 'Quiz (placeholder)',
            self::Quiz => 'Quiz',
        };
    }

    /** Types whose payload is an attached Assessment rather than `content` or media. */
    public function usesAssessment(): bool
    {
        return $this === self::Quiz;
    }

    /** Types that carry media metadata (Mux/S3). */
    public function usesMedia(): bool
    {
        return in_array($this, [self::Video, self::Audio, self::Pdf, self::Download], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
