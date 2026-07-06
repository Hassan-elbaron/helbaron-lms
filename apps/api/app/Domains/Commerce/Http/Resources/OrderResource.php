<?php

namespace App\Domains\Commerce\Http\Resources;

use App\Domains\Commerce\Models\Order;
use App\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @property Order $resource
 */
class OrderResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'status' => $this->resource->status->value,
            'currency' => $this->resource->currency,
            'subtotal_minor' => $this->resource->subtotal_minor,
            'discount_minor' => $this->resource->discount_minor,
            'total_minor' => $this->resource->total_minor,
            'placed_at' => $this->resource->placed_at?->toIso8601String(),
            'paid_at' => $this->resource->paid_at?->toIso8601String(),
            'fulfilled_at' => $this->resource->fulfilled_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->resource->items->map(fn ($i) => [
                'title' => $i->title,
                'unit_amount_minor' => $i->unit_amount_minor,
            ])->values()),
            'invoice' => $this->whenLoaded('invoice', fn () => $this->resource->invoice ? [
                'number' => $this->resource->invoice->number,
                'status' => $this->resource->invoice->status->value,
            ] : null),
        ];
    }
}
