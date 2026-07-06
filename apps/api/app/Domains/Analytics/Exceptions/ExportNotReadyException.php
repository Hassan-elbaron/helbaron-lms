<?php

namespace App\Domains\Analytics\Exceptions;

class ExportNotReadyException extends AnalyticsException
{
    protected string $errorCode = 'ANALYTICS_EXPORT_NOT_READY';

    protected int $status = 409;

    public function __construct(string $message = 'The export is not ready yet.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
