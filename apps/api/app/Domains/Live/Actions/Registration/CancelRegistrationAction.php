<?php

namespace App\Domains\Live\Actions\Registration;

use App\Domains\Live\Enums\RegistrationStatus;
use App\Domains\Live\Models\LiveSession;
use App\Platform\Shared\Actions\BaseAction;

class CancelRegistrationAction extends BaseAction
{
    public function executeByUserId(LiveSession $session, int $userId): void
    {
        $this->transaction(function () use ($session, $userId): void {
            $session->registrations()->where('user_id', $userId)
                ->update(['status' => RegistrationStatus::Cancelled->value]);
        });
    }
}
