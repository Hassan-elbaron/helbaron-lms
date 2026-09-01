<?php

namespace App\Platform\Notifications\Services;

use App\Platform\Notifications\Enums\Channel;
use App\Platform\Notifications\Enums\NotificationCategory;
use App\Platform\Notifications\Models\AutomationRule;
use App\Platform\Shared\Services\BaseService;

/**
 * Evaluates active event-triggered automation rules for a given trigger key and executes their
 * send-notification actions (conditions matched against the payload).
 *
 * NOT WIRED, and the distinction matters. `handleEventForUserId()` has zero callers. Automation
 * itself is NOT dead — `Crm\...\AutomationRunner` is subscribed and does run rules for its two lead
 * events — so "all automation is inert" (as the plan document has it) overstates the problem. What
 * is dead is this per-user entry point and the SCHEDULED trigger type: `AutomationTriggerType`
 * declares Scheduled, the `scheduled_automations` table exists, `ScheduledAutomation` is referenced
 * by nothing, and AutomationRunner hard-filters `trigger_type = 'event'`.
 *
 * Left in place rather than deleted: the evaluation logic is correct and is the reusable half. But
 * nothing should read the presence of this class as evidence that scheduled automations run.
 */
class WorkflowEngine extends BaseService
{
    public function __construct(private readonly NotificationDispatcher $dispatcher) {}

    /** @param array<string, mixed> $payload */
    public function handleEventForUserId(string $triggerKey, int $userId, array $payload = []): void
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
                $this->dispatcher->dispatchToUserId(
                    $userId,
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
