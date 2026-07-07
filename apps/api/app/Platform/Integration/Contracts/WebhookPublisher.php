<?php

declare(strict_types=1);

namespace App\Platform\Integration\Contracts;

/**
 * Port: deliver an outbound webhook to an external endpoint (signed, retried, audited by the
 * implementation). Contract only — no implementation in this story.
 */
interface WebhookPublisher
{
    /** @param array<string, mixed> $payload */
    public function publish(string $endpoint, string $event, array $payload): void;
}
