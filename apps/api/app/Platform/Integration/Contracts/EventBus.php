<?php

declare(strict_types=1);

namespace App\Platform\Integration\Contracts;

/**
 * Port: in-process event bus for dispatching domain/platform event DTOs to subscribers.
 * Contract only — no implementation in this story (Integration Platform is future work).
 */
interface EventBus
{
    public function dispatch(object $event): void;
}
