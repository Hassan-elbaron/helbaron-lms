<?php

namespace App\Platform\Identity\Actions\Device;

use App\Platform\Identity\Events\DeviceRevoked;
use App\Platform\Identity\Models\User;
use App\Platform\Identity\Models\UserDevice;
use App\Platform\Identity\Services\DeviceService;
use App\Platform\Shared\Actions\BaseAction;

class RevokeDeviceAction extends BaseAction
{
    public function __construct(private readonly DeviceService $devices) {}

    public function execute(User $user, UserDevice $device): void
    {
        $publicId = $device->public_id;
        $this->devices->revoke($device);

        DeviceRevoked::dispatch($user, $publicId);
    }
}
