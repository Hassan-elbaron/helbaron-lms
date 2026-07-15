<?php

namespace App\Domains\Live\Http\Controllers\Api\V1;

use App\Domains\Live\Enums\LiveSessionStatus;
use App\Domains\Live\Http\Resources\EventDetailResource;
use App\Domains\Live\Http\Resources\EventListResource;
use App\Domains\Live\Models\LiveSession;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Public, unauthenticated "Events" surface — a thin PRESENTATION layer over the existing Live
 * domain. It reuses App\Domains\Live models and exposes only marketing-safe fields via the
 * Event resources (never the meeting join_url / internals). No new Event domain model exists.
 */
class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filter = $request->string('filter')->toString() === 'past' ? 'past' : 'upcoming';
        $q = $request->string('q')->trim()->toString();

        $query = LiveSession::query()
            ->where('status', '!=', LiveSessionStatus::Cancelled->value)
            ->withCount(['registrations as registered_count' => fn (Builder $b) => $b->where('status', 'registered')])
            ->with('trainerLinks');

        if ($q !== '') {
            $query->where(function (Builder $b) use ($q): void {
                $b->where('title', 'ilike', '%'.$q.'%')
                    ->orWhere('description', 'ilike', '%'.$q.'%');
            });
        }

        if ($filter === 'past') {
            // Past = completed OR already ended (still excluding cancelled), newest first.
            $query->where(function (Builder $b): void {
                $b->where('status', LiveSessionStatus::Completed->value)
                    ->orWhere('ends_at', '<', now());
            })->orderByDesc('starts_at');
        } else {
            // Upcoming = scheduled/live and not yet ended, soonest first.
            $query->whereIn('status', [LiveSessionStatus::Scheduled->value, LiveSessionStatus::Live->value])
                ->where('ends_at', '>=', now())
                ->orderBy('starts_at');
        }

        $events = $query->paginate((int) $request->integer('per_page', 12))->withQueryString();

        return ApiResponse::paginated($events, EventListResource::class);
    }

    public function show(LiveSession $session): JsonResponse
    {
        $session->loadCount([
            'registrations as registered_count' => fn (Builder $b) => $b->where('status', 'registered'),
            'registrations as waitlist_count' => fn (Builder $b) => $b->where('status', 'waitlisted'),
        ]);
        $session->load(['trainerLinks', 'liveCourse.course']);

        return ApiResponse::success(new EventDetailResource($session));
    }
}
