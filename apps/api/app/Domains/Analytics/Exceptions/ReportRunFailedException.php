<?php

namespace App\Domains\Analytics\Exceptions;

class ReportRunFailedException extends AnalyticsException
{
    protected string $errorCode = 'ANALYTICS_REPORT_FAILED';

    protected int $status = 500;

    public function __construct(string $message = 'The report could not be generated.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
