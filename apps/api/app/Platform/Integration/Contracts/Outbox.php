<?php

declare(strict_types=1);

namespace App\Platform\Integration\Contracts;

/**
 * Port: transactional outbox. Store an event alongside the domain write (same transaction); a
 * relay publishes pending entries and marks them dispatched, giving guaranteed delivery.
 * Contract only — no implementation in this story.
 */
interface Outbox
{
    public function store(object $event): void;

    /** @return iterable<object> pending, not-yet-published events (bounded by $limit). */
    public function pending(int $limit = 100): iterable;

    public function markPublished(string $id): void;
}
