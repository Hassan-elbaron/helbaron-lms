<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Injects the current correlation id + service metadata into every log record so structured
 * (JSON) logs are traceable across the request lifecycle and the queue.
 */
class CorrelationProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['service'] = (string) config('app.name', 'helbaron');
        $record->extra['env'] = (string) config('app.env', 'production');

        $correlationId = request()?->headers?->get('X-Correlation-ID');
        if (is_string($correlationId) && $correlationId !== '') {
            $record->extra['correlation_id'] = $correlationId;
        }

        return $record;
    }
}
