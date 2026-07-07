<?php

namespace App\Platform\Notifications\Http\Resources;

use App\Platform\Notifications\Models\NotificationDelivery;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property NotificationDelivery $resource
 */
class NotificationDeliveryResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'channel' => $this->resource->channel->value,
            'status' => $this->resource->status->value,
            'attempts' => $this->resource->attempts,
            'sent_at' => $this->resource->sent_at?->toIso8601String(),
            'dead_at' => $this->resource->dead_at?->toIso8601String(),
        ];
    }
}
