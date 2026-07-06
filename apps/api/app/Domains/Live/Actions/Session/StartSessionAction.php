<?php

namespace App\Domains\Live\Actions\Session;

use App\Domains\Live\Enums\LiveSessionStatus;
use App\Domains\Live\Events\SessionStarted;
use App\Domains\Live\Exceptions\InvalidSessionStateException;
use App\Domains\Live\Models\LiveSession;
use App\Shared\Actions\BaseAction;

class StartSessionAction extends BaseAction
{
    public function execute(LiveSession $session): LiveSession
    {
        if ($session->status !== LiveSessionStatus::Scheduled) {
            throw new InvalidSessionStateException;
        }

        $session = $this->transaction(function () use ($session): LiveSession {
            $session->forceFill(['status' => LiveSessionStatus::Live->value])->save();

            return $session;
        });

        SessionStarted::dispatch($session);

        return $session;
    }
}
