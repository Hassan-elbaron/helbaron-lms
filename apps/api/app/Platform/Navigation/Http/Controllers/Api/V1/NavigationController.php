<?php

namespace App\Platform\Navigation\Http\Controllers\Api\V1;

use App\Platform\Navigation\Enums\MenuLocation;
use App\Platform\Navigation\Http\Resources\NavItemResource;
use App\Platform\Navigation\Models\NavItem;
use App\Platform\Navigation\Models\NavMenu;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public, read-only navigation. Serves the enabled, ordered, tree-structured items for a location
 * (or every active menu), each with a resolved bilingual label, a SAFE url, and the visibility
 * metadata the frontend filters on. Never emits an unsafe URL. Unauthenticated by design — the
 * frontend applies role/auth/locale/flag gating client-side and keeps a hardcoded fallback.
 */
class NavigationController extends Controller
{
    /** GET /api/v1/navigation — all active menus with their enabled item trees. */
    public function index(): JsonResponse
    {
        $menus = NavMenu::query()->active()->with(['items' => fn ($q) => $q->enabled()])->get();

        return ApiResponse::success([
            'menus' => $menus->map(fn (NavMenu $menu) => [
                'location' => $menu->location->value,
                'items' => $this->tree($menu->items)->map(fn (NavItem $item) => (new NavItemResource($item))->resolve())->values(),
            ])->values(),
        ]);
    }

    /** GET /api/v1/navigation/{location} — the enabled item tree for one location. */
    public function show(string $location): JsonResponse
    {
        $enum = MenuLocation::tryFrom($location);

        if ($enum === null) {
            throw new NotFoundHttpException('Unknown navigation location.');
        }

        $menu = NavMenu::query()->active()->forLocation($enum)
            ->with(['items' => fn ($q) => $q->enabled()])
            ->first();

        // No active menu (or empty) — return an empty tree so the frontend renders its fallback.
        $items = $menu !== null ? $this->tree($menu->items) : collect();

        return ApiResponse::success([
            'location' => $enum->value,
            'items' => $items->map(fn (NavItem $item) => (new NavItemResource($item))->resolve())->values(),
        ]);
    }

    /**
     * Assemble a flat, enabled, position-ordered item collection into a root-level tree, attaching
     * each node's children as an in-memory `children` relation for the resource to emit.
     *
     * @param  Collection<int, NavItem>  $items
     * @return Collection<int, NavItem>
     */
    private function tree(Collection $items): Collection
    {
        $byParent = $items->groupBy(fn (NavItem $item) => $item->parent_id === null ? '_root' : (string) $item->parent_id);

        $attach = function (NavItem $item) use (&$attach, $byParent): NavItem {
            $children = ($byParent->get((string) $item->id) ?? collect())
                ->map(fn (NavItem $child) => $attach($child))
                ->values();
            $item->setRelation('children', $children);

            return $item;
        };

        return ($byParent->get('_root') ?? collect())
            ->map(fn (NavItem $item) => $attach($item))
            ->values();
    }
}
