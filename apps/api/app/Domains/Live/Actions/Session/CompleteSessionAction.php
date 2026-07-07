<?php

namespace App\Domains\Live\Actions\Session;

use App\Domains\Live\Contracts\MeetingProvider;
use App\Domains\Live\Enums\LiveSessionStatus;
use App\Domains\Live\Events\SessionCompleted;
use App\Domains\Live\Models\LiveSession;
use App\Domains\Live\Models\SessionRecording;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Completes a session and stores any recording METADATA the provider exposes (no media
 * processing). The Fake provider returns none.
 */
class CompleteSessionAction extends BaseAction
{
    public function __construct(private readonly MeetingProvider $meeting) {}

    public function execute(LiveSession $session): LiveSession
    {
        $session = $this->transaction(function () use ($session): LiveSession {
            $session->forceFill(['status' => LiveSessionStatus::Completed->value])->save();

            if ($session->meeting_external_id !== null) {
                $meta = $this->meeting->recording($session->meeting_external_id);
                if ($meta !== null) {
                    SessionRecording::updateOrCreate(
                        ['session_id' => $session->id, 'external_id' => $meta->externalId],
                        [
                            'provider' => $meta->provider,
                            'url' => $meta->url,
                            'duration_seconds' => $meta->durationSeconds,
                            'status' => $meta->status,
                            'recorded_at' => $meta->recordedAt,
                        ],
                    );
                }
            }

            return $session;
        });

        SessionCompleted::dispatch($session);

        return $session;
    }
}
