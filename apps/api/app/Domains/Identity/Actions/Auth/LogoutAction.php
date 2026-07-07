<?php

namespace App\Domains\Identity\Actions\Auth;

use App\Domains\Identity\Events\UserLoggedOut;
use App\Domains\Identity\Models\User;
use App\Platform\Shared\Actions\BaseAction;

class LogoutAction extends BaseAction
{
    /** Revoke the currently-used access token and its device row. */
    public function execute(User $user, ?int $currentTokenId): void
    {
        $this->transaction(function () use ($user, $currentTokenId): void {
            if ($currentTokenId !== null) {
                $user->devices()->where('token_id', $currentTokenId)->delete();
                $user->tokens()->whereKey($currentTokenId)->delete();
            }
        });

        UserLoggedOut::dispatch($user);
    }
}
