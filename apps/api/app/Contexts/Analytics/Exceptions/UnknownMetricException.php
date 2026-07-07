<?php

namespace App\Contexts\Analytics\Exceptions;

class UnknownMetricException extends AnalyticsException
{
    protected string $errorCode = 'ANALYTICS_UNKNOWN_METRIC';

    protected int $status = 422;

    public function __construct(string $message = 'Unknown metric.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
