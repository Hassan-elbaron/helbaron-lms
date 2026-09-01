<?php

namespace App\Http\Controllers;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Throwable;

/**
 * GET /api/v1/version — what is this instance actually running?
 *
 * With one deployed instance per customer academy, the fleet question that matters is not "is it
 * up" (health answers that) but "is it the build I think it is". An image tag is a promise; the
 * commit baked into the image is the fact. This endpoint reports the fact, plus the one piece of
 * state that a tag cannot tell you: whether the schema was actually migrated to match the code.
 *
 * A deploy that pulls the new image but whose migration step silently failed leaves an instance that
 * is healthy, serving, and wrong. `migrations.pending` is how that is caught from outside the box.
 *
 * PUBLIC, deliberately — the same reasoning as /api/v1/health. Sweeping thirty instances from a
 * monitoring job must not require thirty credentials. It exposes a commit hash and migration counts;
 * no configuration, no secrets, no data. Restrict it at the edge if a customer's threat model calls
 * for it.
 */
class VersionController extends Controller
{
    public function __invoke(Migrator $migrator): JsonResponse
    {
        return response()->json([
            // Neutral service identifier: this string reaches uptime dashboards on every instance.
            'service' => 'lms-api',
            'version' => (string) config('app.version'),
            'commit' => (string) config('app.build_sha'),
            'built_at' => (string) config('app.built_at'),
            'environment' => (string) config('app.env'),
            'migrations' => $this->migrationState($migrator),
            'time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Applied/pending counts across every registered migration path — domain modules register their
     * own via loadMigrationsFrom(), so asking the Migrator is the only way to see all of them.
     *
     * @return array<string, mixed>
     */
    private function migrationState(Migrator $migrator): array
    {
        try {
            $files = array_keys($migrator->getMigrationFiles($migrator->paths()));

            if (! $migrator->repositoryExists()) {
                return [
                    'state' => 'never-migrated',
                    'applied' => 0,
                    'pending' => count($files),
                    'up_to_date' => false,
                ];
            }

            $ran = $migrator->getRepository()->getRan();
            $pending = array_values(array_diff($files, $ran));

            // Counts only — deliberately NOT migration NAMES. This endpoint is public, and migration
            // filenames are free text written by developers: the newest one on this branch is
            // `2026_08_30_000400_rename_why_helbaron_homepage_section_key`, which would have put the
            // vendor's name on a public URL of every customer academy. The names also read as a
            // changelog of what was recently broken. `pending` answers the operational question on
            // its own.
            return [
                'state' => 'ok',
                'applied' => count($ran),
                'pending' => count($pending),
                'up_to_date' => $pending === [],
            ];
        } catch (Throwable) {
            // The database being unreachable is readiness' problem to report, not this endpoint's to
            // crash on. Say so rather than guessing a count.
            return ['state' => 'unavailable', 'up_to_date' => null];
        }
    }
}
