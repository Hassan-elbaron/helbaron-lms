<?php

use App\Contexts\Commerce\Models\PaymentWebhookEvent;
use Illuminate\Support\Facades\Schedule;

// Horizon queue metrics snapshots (required for the Horizon dashboard graphs).
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// Prune expired Sanctum tokens, old failed jobs, and stale password-reset tokens.
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('auth:clear-resets')->daily();

// Processed payment webhook events are kept 30 days for reconciliation, then pruned.
Schedule::call(function (): void {
    PaymentWebhookEvent::query()
        ->whereNotNull('processed_at')
        ->where('processed_at', '<', now()->subDays(30))
        ->delete();
})->daily()->name('commerce-prune-processed-webhook-events')->onOneServer()->withoutOverlapping();
