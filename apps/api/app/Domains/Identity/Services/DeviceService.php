<?php

namespace App\Domains\Identity\Services;

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Models\UserDevice;
use App\Shared\Services\BaseService;
use Laravel\Sanctum\NewAccessToken;

/**
 * Records a device per issued access token and manages revocation. Revoking a device deletes
 * its linked Sanctum token so the session is invalidated.
 */
class DeviceService extends BaseService
{
    public function register(User $user, NewAccessToken $token, ?string $name, ?string $ip, ?string $userAgent): UserDevice
    {
        return UserDevice::create([
            'user_id' => $user->id,
            'token_id' => $token->accessToken->getKey(),
            'name' => $name,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'last_used_at' => now(),
        ]);
    }

    public function revoke(UserDevice $device): void
    {
        $this->transaction(function () use ($device): void {
            if ($device->token_id !== null) {
                $device->user->tokens()->whereKey($device->token_id)->delete();
            }

            $device->delete();
        });
    }
}
