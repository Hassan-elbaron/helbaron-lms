<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Contracts;

use App\Platform\Shared\Tenancy\Events\TenantEvent;

/**
 * Port: publishes tenant lifecycle events (DTOs). Implemented later by Administration/Integration
 * (e.g. via the framework event bus and/or the outbox). No implementation in this story.
 */
interface TenantEventPublisher
{
    public function publish(TenantEvent $event): void;
}
