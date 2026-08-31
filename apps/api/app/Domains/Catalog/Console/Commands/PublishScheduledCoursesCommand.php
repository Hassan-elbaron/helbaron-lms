<?php

namespace App\Domains\Catalog\Console\Commands;

use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Exceptions\CoursePublishBlockedException;
use App\Domains\Catalog\Models\Course;
use App\Domains\Catalog\Services\CourseLifecycle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The scheduled consumer for course scheduling. Every minute it finds Scheduled courses whose
 * scheduled_publish_at has arrived and publishes each through the CourseLifecycle state machine —
 * which routes the publish through the readiness guard. A course that is due but not yet ready is
 * left Scheduled (never silently corrupted), so it will be retried on the next tick once fixed.
 *
 * TWO SEPARATE FAILURE MODES, HANDLED SEPARATELY.
 *
 * 1. READINESS-BLOCKED (expected, self-healing). The course is not ready. Logged at WARNING with the
 *    course id and blocker codes, and the run still reports success — the operator does not need to
 *    be paged because an author has not finished writing. This used to be swallowed into a bare
 *    counter, so the only trace was "0 published, 7 pending (not ready)" — no course id, no reason —
 *    repeated every minute forever with nothing alerting.
 *
 * 2. ANYTHING ELSE (unexpected). A CourseTransitionException from the state machine, a listener
 *    throwing on the CoursePublished event (dispatched OUTSIDE the publish transaction), a
 *    QueryException, a deadlock. Only CoursePublishBlockedException used to be caught, so any of
 *    these escaped the loop and aborted the whole run. Because the ordering is deterministic
 *    (scheduled_publish_at ascending), the same course poisoned every subsequent tick and every
 *    course behind it missed its launch date indefinitely. These are now caught per course, logged
 *    at ERROR, and the run continues — but the command exits non-zero so a scheduler health check
 *    can actually see that something is wrong.
 */
class PublishScheduledCoursesCommand extends Command
{
    protected $signature = 'courses:publish-scheduled';

    protected $description = 'Publish scheduled courses whose publish time has arrived and that pass readiness';

    public function handle(CourseLifecycle $lifecycle): int
    {
        $published = 0;
        $blocked = 0;
        $failed = 0;

        // chunkById rather than get(): the due set is unbounded (a backlog builds whenever the
        // scheduler stops), and publishing mutates `status`/`scheduled_publish_at` so the rows drop
        // out of the query as they succeed — which is exactly the case a plain offset paginator gets
        // wrong and chunkById does not.
        Course::query()
            ->scheduledDue()
            ->orderBy('id')
            ->chunkById(100, function ($courses) use ($lifecycle, &$published, &$blocked, &$failed): void {
                foreach ($courses as $course) {
                    $outcome = $this->publishOne($lifecycle, $course);

                    match ($outcome) {
                        'published' => $published++,
                        'blocked' => $blocked++,
                        default => $failed++,
                    };
                }
            });

        // One fact per line: the console wraps, and a wrapped line splits the numbers an operator
        // (and the tests) are scanning for.
        $this->info("Published: {$published}");
        $this->info("Pending (not ready): {$blocked}");
        $this->info("Failed unexpectedly: {$failed}");

        // A readiness block alone is success: it is expected and it fixes itself. An unexpected
        // failure is not, and must be visible to whatever watches the scheduler's exit code.
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @return 'published'|'blocked'|'failed' */
    private function publishOne(CourseLifecycle $lifecycle, Course $course): string
    {
        $publicId = (string) $course->getAttribute('public_id');

        try {
            $lifecycle->transition($course, CourseStatus::Published);

            return 'published';
        } catch (CoursePublishBlockedException $e) {
            Log::warning('Scheduled course publish blocked by readiness rules.', [
                'course_public_id' => $publicId,
                'course_id' => (int) $course->getKey(),
                'scheduled_publish_at' => (string) $course->getAttribute('scheduled_publish_at'),
                // Stable codes, so this is greppable and alertable without matching prose that gets
                // reworded.
                'blockers' => $e->blockerCodes,
                'reason' => $e->getMessage(),
            ]);

            $this->warn("Course {$publicId} not published: readiness.");
            $this->warn('Blockers: '.($e->blockerCodes === [] ? 'none reported' : implode(', ', $e->blockerCodes)));

            return 'blocked';
        } catch (Throwable $e) {
            // One course must never take the run down with it. Caught here, per course, so every
            // course behind this one still gets its turn.
            Log::error('Scheduled course publish failed unexpectedly.', [
                'course_public_id' => $publicId,
                'course_id' => (int) $course->getKey(),
                'exception' => $e::class,
                'reason' => $e->getMessage(),
            ]);

            $this->error("Course {$publicId} failed unexpectedly: ".$e::class);

            return 'failed';
        }
    }
}
