<?php

namespace App\Domains\Catalog\Http\Resources;

use App\Domains\Catalog\Models\Course;
use App\Platform\Shared\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * Full course detail (media-safe: no internal ids or storage keys beyond the public thumbnail).
 *
 * @property Course $resource
 */
class CourseResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->public_id,
            'title' => $this->resource->title,
            'slug' => $this->resource->slug,
            'subtitle' => $this->resource->subtitle,
            'description' => $this->resource->description,
            'status' => $this->resource->status->value,
            'visibility' => $this->resource->visibility->value,
            'is_featured' => $this->resource->is_featured,
            'thumbnail_path' => $this->resource->thumbnail_path,
            'seo' => $this->resource->seo,
            'level' => $this->whenLoaded('level', fn () => $this->resource->level ? [
                'id' => $this->resource->level->public_id,
                'name' => $this->resource->level->name,
            ] : null),
            'language' => $this->whenLoaded('language', fn () => $this->resource->language ? [
                'id' => $this->resource->language->public_id,
                'name' => $this->resource->language->name,
                'code' => $this->resource->language->code,
            ] : null),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'tags' => $this->whenLoaded('tags', fn () => $this->resource->tags->map(fn ($t) => [
                'id' => $t->public_id, 'name' => $t->name, 'slug' => $t->slug,
            ])->values()),
            'trainers' => TrainerResource::collection($this->whenLoaded('trainers')),
            'related' => CourseListResource::collection($this->whenLoaded('related')),
            'published_at' => $this->resource->published_at?->toIso8601String(),
        ];
    }
}
