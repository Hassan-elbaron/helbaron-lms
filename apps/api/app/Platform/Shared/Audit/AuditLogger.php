<?php

namespace App\Platform\Shared\Audit;

use Illuminate\Database\Eloquent\Model;

/**
 * Writes append-only audit-trail entries for privileged actions. Deliberately minimal: one
 * synchronous insert per call, no update path. The actor defaults to the authenticated user
 * (null => 'system', e.g. scheduled jobs / webhooks).
 */
class AuditLogger
{
    /** @param array<string, mixed> $context */
    public function log(string $action, ?Model $subject = null, array $context = [], ?int $actorId = null): AuditLog
    {
        if ($actorId === null) {
            $authId = auth()->id();
            $actorId = $authId !== null ? (int) $authId : null;
        }

        return AuditLog::create([
            'actor_id' => $actorId,
            'actor_type' => $actorId !== null ? 'user' : 'system',
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'context' => $context !== [] ? $context : null,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }
}
