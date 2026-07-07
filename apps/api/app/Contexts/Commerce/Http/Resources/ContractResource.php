<?php

namespace App\Contexts\Commerce\Http\Resources;

use App\Contexts\Commerce\Models\Contract;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property Contract $resource
 */
class ContractResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'status' => $this->resource->status->value,
            'accepted_at' => $this->resource->accepted_at?->toIso8601String(),
            'template' => $this->whenLoaded('template', fn () => [
                'key' => $this->resource->template->key,
                'version' => $this->resource->template->version,
                'title' => $this->resource->template->title,
                'body' => $this->resource->template->body,
            ]),
            'order_id' => $this->whenLoaded('order', fn () => $this->resource->order?->public_id),
        ];
    }
}
