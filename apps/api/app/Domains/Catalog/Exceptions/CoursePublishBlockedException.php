<?php

namespace App\Domains\Catalog\Exceptions;

class CoursePublishBlockedException extends CatalogException
{
    protected string $errorCode = 'CATALOG_COURSE_PUBLISH_BLOCKED';

    protected int $status = 422;

    public function __construct(?string $reason = null)
    {
        parent::__construct($reason ?? 'This course cannot be published.', $reason ? ['reason' => $reason] : []);
    }
}
