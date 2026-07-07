<?php

namespace App\Domains\Notifications\Services;

use App\Platform\Identity\Models\User;
use App\Domains\Notifications\Enums\Channel;
use App\Domains\Notifications\Enums\NotificationCategory;
use App\Domains\Notifications\Models\AutomationRule;
use App\Platform\Shared\Services\BaseService;

/**
 * Evaluates active event-triggered automation rules for a given trigger key and executes their
 * send-notification actions (conditions matched against the payload).
 */
class WorkflowEngine extends BaseService
{
    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    /** @param array<string, mixed> $payload */
    public function handleEvent(string $triggerKey, User $user, array $payload = []): void
    {
        $rules = AutomationRule::query()
            ->where('is_active', true)
            ->where('trigger_type', 'event')
            ->where('trigger_key', $triggerKey)
            ->with('actions')
            ->get();

        foreach ($rules as $rule) {
            if (! $this->conditionsMet($rule, $payload)) {
                continue;
            }

            foreach ($rule->actions as $action) {
                $channels = array_map(fn (string $c) => Channel::from($c), (array) ($action->channels ?? ['in_app']));
                $this->dispatcher->dispatch(
                    $user,
                    NotificationCategory::from($action->category),
                    $action->template_key,
                    $payload,
                    $channels,
                );
            }
        }
    }

    /** @param array<string, mixed> $payload */
    private function conditionsMet(AutomationRule $rule, array $payload): bool
    {
        foreach ((array) ($rule->conditions ?? []) as $key => $expected) {
            if (($payload[$key] ?? null) != $expected) {
                return false;
            }
        }

        return true;
    }
}
