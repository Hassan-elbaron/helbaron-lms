<?php

namespace App\Platform\Pages\Http\Controllers\Api\V1;

use App\Platform\Identity\Contracts\Actor;
use App\Platform\Pages\Http\Resources\StaticPageResource;
use App\Platform\Pages\Models\StaticPage;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public static-page delivery (read-only) plus an admin-only draft preview. Only pages that are
 * published AND inside their schedule window are exposed publicly; anything else 404s. The preview
 * endpoint returns the current draft regardless of status for authenticated admins.
 */
class PageController extends Controller
{
    /** GET /api/v1/pages — published pages (summary) for nav inclusion / sitemap. */
    public function index(): JsonResponse
    {
        $pages = StaticPage::query()
            ->published()
            ->orderBy('position')
            ->orderBy('slug')
            ->get();

        return ApiResponse::success([
            'pages' => $pages
                ->map(fn (StaticPage $p) => (new StaticPageResource($p, summary: true))->resolve())
                ->values(),
        ]);
    }

    /** GET /api/v1/pages/{slug} — the full published page, or 404 when not live. */
    public function show(string $slug): JsonResponse
    {
        $page = StaticPage::query()->published()->where('slug', $slug)->first();

        if ($page === null) {
            throw new NotFoundHttpException('Page not found.');
        }

        return ApiResponse::success((new StaticPageResource($page))->resolve());
    }

    /** GET /api/v1/pages/{slug}/preview — admin-only; returns the current draft in any status. */
    public function preview(Request $request, string $slug): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof Actor || ! $user->hasRole(['admin', 'super_admin'])) {
            throw new AccessDeniedHttpException('Admin access required.');
        }

        $page = StaticPage::query()->where('slug', $slug)->first();

        if ($page === null) {
            throw new NotFoundHttpException('Page not found.');
        }

        return ApiResponse::success((new StaticPageResource($page))->resolve());
    }
}
