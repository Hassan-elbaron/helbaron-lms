<?php

namespace App\Platform\Notifications\Http\Resources;

use App\Platform\Notifications\Models\Notification;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property Notification $resource
 */
class NotificationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'category' => $this->resource->category->value,
            'type' => $this->resource->type,
            'title' => $this->resource->title,
            'body' => $this->resource->body,
            'data' => $this->resource->data,
            'locale' => $this->resource->locale,
            'read' => $this->resource->read_at !== null,
            'archived' => $this->resource->archived_at !== null,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'deliveries' => NotificationDeliveryResource::collection($this->whenLoaded('deliveries')),
        ];
    }
}
