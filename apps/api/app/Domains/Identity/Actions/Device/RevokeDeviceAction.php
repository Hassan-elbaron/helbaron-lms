<?php

namespace App\Domains\Identity\Actions\Device;

use App\Domains\Identity\Events\DeviceRevoked;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Models\UserDevice;
use App\Domains\Identity\Services\DeviceService;
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
