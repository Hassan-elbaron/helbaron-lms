<?php

namespace App\Platform\Shared\Media\Exceptions;

use App\Platform\Shared\Exceptions\BaseDomainException;

/**
 * No signable media is available for a lesson (missing media row, or missing playback id / storage
 * key for the configured provider). Relocated from Learning to the Shared Media namespace so the
 * Media platform may throw it without a Media -> Learning dependency. The machine-readable error
 * code and HTTP status are preserved verbatim (stable API contract): LEARNING_MEDIA_UNAVAILABLE / 404.
 */
class MediaUnavailableException extends BaseDomainException
{
    protected string $errorCode = 'LEARNING_MEDIA_UNAVAILABLE';

    protected int $status = 404;

    /** @param array<string, mixed> $details */
    public function __construct(string $message = 'No media is available for this lesson.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
