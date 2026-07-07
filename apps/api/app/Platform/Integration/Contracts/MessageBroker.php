<?php

declare(strict_types=1);

namespace App\Platform\Integration\Contracts;

/**
 * Port: FUTURE external message broker (Kafka/SQS/RabbitMQ) for cross-service messaging when/if a
 * context is extracted to its own service. Contract only — reserved; no implementation and no
 * messaging in this story.
 */
interface MessageBroker
{
    /** @param array<string, mixed> $message */
    public function publish(string $topic, array $message): void;
}
