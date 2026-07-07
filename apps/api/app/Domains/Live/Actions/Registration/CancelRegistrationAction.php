<?php

namespace App\Domains\Live\Actions\Registration;

use App\Platform\Identity\Models\User;
use App\Domains\Live\Enums\RegistrationStatus;
use App\Domains\Live\Models\LiveSession;
use App\Platform\Shared\Actions\BaseAction;

class CancelRegistrationAction extends BaseAction
{
    public function execute(LiveSession $session, User $user): void
    {
        $this->transaction(function () use ($session, $user): void {
            $session->registrations()->where('user_id', $user->id)
                ->update(['status' => RegistrationStatus::Cancelled->value]);
        });
    }
}
