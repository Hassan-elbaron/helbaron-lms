<?php

namespace App\Shared\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Wraps a paginated resource collection into the standard { data, meta, links } envelope,
 * matching ApiResponse::paginated(). Use when returning a ResourceCollection directly:
 *   return new PaginatedCollection($paginator, CourseResource::class);
 */
class PaginatedCollection extends ResourceCollection
{
    public function __construct($resource, ?string $collects = null)
    {
        if ($collects !== null) {
            $this->collects = $collects;
        }

        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    /** @return array<string, mixed> */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        $paginator = $this->resource;

        return [
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }
}
