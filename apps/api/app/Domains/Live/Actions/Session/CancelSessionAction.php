<?php

namespace App\Domains\Live\Actions\Session;

use App\Domains\Live\Enums\LiveSessionStatus;
use App\Domains\Live\Events\SessionCancelled;
use App\Domains\Live\Models\LiveSession;
use App\Shared\Actions\BaseAction;

class CancelSessionAction extends BaseAction
{
    public function execute(LiveSession $session): LiveSession
    {
        $session = $this->transaction(function () use ($session): LiveSession {
            $session->forceFill(['status' => LiveSessionStatus::Cancelled->value])->save();

            return $session;
        });

        SessionCancelled::dispatch($session);

        return $session;
    }
}
