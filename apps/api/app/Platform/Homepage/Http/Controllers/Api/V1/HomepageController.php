<?php

namespace App\Platform\Homepage\Http\Controllers\Api\V1;

use App\Platform\Homepage\Enums\BlockType;
use App\Platform\Homepage\Http\Resources\HomepageSectionResource;
use App\Platform\Homepage\Models\HomepageSection;
use App\Platform\Homepage\Services\HomepageContentResolver;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Public homepage content (read-only) + an admin-only preview of the working draft. The SEO block is
 * folded out of the sections list and returned separately as the resolved `seo` bag.
 *
 * The public endpoint serves only blocks that are enabled AND currently live (Published status inside
 * their published_at/unpublished_at window). Blocks that reference domain entities (featured courses
 * / events / categories) have those entities resolved server-side via HomepageContentResolver.
 */
class HomepageController extends Controller
{
    public function __construct(private readonly HomepageContentResolver $resolver) {}

    /** GET /api/v1/homepage — enabled + live blocks with their published snapshot (draft fallback). */
    public function index(): JsonResponse
    {
        return $this->respond(draft: false);
    }

    /** GET /api/v1/homepage/preview — admin-only; enabled blocks (any status) with their working draft. */
    public function preview(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof Actor || ! $user->hasRole(['admin', 'super_admin'])) {
            throw new AccessDeniedHttpException('Admin access required.');
        }

        return $this->respond(draft: true);
    }

    private function respond(bool $draft): JsonResponse
    {
        /** @var Collection<int, HomepageSection> $sections */
        $sections = HomepageSection::query()
            ->enabled()
            ->when(! $draft, fn ($q) => $q->published())
            ->get();

        $seo = $sections->first(fn (HomepageSection $s) => $s->type === BlockType::Seo);
        $blocks = $sections->reject(fn (HomepageSection $s) => $s->type === BlockType::Seo);

        return ApiResponse::success([
            'sections' => $blocks
                ->map(fn (HomepageSection $s) => (new HomepageSectionResource(
                    $s,
                    $draft,
                    $this->resolveEntities($s, $draft),
                ))->resolve())
                ->values(),
            'seo' => $seo !== null
                ? (new HomepageSectionResource($seo, $draft))->resolve()['content']
                : null,
        ]);
    }

    /**
     * Resolve referenced domain entities for entity-backed blocks; null for the rest.
     *
     * @return array<string, mixed>|null
     */
    private function resolveEntities(HomepageSection $section, bool $draft): ?array
    {
        if (! $section->type->resolvesEntities()) {
            return null;
        }

        $content = $draft ? ($section->content ?? []) : $section->resolvedContent();

        return $this->resolver->resolve($section, $content);
    }
}
