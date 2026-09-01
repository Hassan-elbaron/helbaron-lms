<?php

namespace App\Domains\Catalog\Exceptions;

class CoursePublishBlockedException extends CatalogException
{
    protected string $errorCode = 'CATALOG_COURSE_PUBLISH_BLOCKED';

    protected int $status = 422;

    /**
     * @param  list<string>  $blockerCodes  stable codes of every blocker, for logs and clients
     */
    public function __construct(?string $reason = null, public readonly array $blockerCodes = [])
    {
        parent::__construct(
            $reason ?? 'This course cannot be published.',
            array_filter([
                'reason' => $reason,
                // Exposed alongside the prose so an operator (and the scheduled-publish command) can
                // see WHICH rules refused, not just the first one's wording.
                'blockers' => $blockerCodes !== [] ? $blockerCodes : null,
            ], fn ($v) => $v !== null),
        );
    }
}
