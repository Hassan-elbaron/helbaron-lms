<?php

namespace App\Platform\Shared\Publishing\Data;

/**
 * The full outcome of a publish-readiness evaluation.
 *
 * This is the SINGLE source of truth for whether a course may publish: the guard derives its
 * verdict from `isPublishable()` rather than running its own rules. That is deliberate — a readiness
 * panel that disagrees with the guard is worse than no panel at all, because it teaches an author to
 * distrust it. There is exactly one rule set, evaluated once, read two ways.
 *
 * `passedChecks` carries the checks that succeeded so the panel can show completed requirements
 * rather than only complaints, and so `score` means something when there is nothing wrong.
 */
final readonly class ReadinessReport
{
    /**
     * @param  list<ReadinessIssue>  $issues
     * @param  list<string>  $passedChecks  codes of checks that passed
     */
    public function __construct(
        public array $issues,
        public array $passedChecks,
        public string $evaluatedAt,
    ) {}

    public function isPublishable(): bool
    {
        return $this->blockers() === [];
    }

    /** @return list<ReadinessIssue> */
    public function blockers(): array
    {
        return array_values(array_filter($this->issues, fn (ReadinessIssue $i) => $i->blocksPublishing()));
    }

    /** @return list<ReadinessIssue> */
    public function warnings(): array
    {
        return array_values(array_filter($this->issues, fn (ReadinessIssue $i) => ! $i->blocksPublishing()));
    }

    /**
     * Percentage of checks that passed, 0-100.
     *
     * Warnings count against the score even though they do not block: a course that publishes with
     * no description is publishable but not finished, and a panel reading 100% would be lying about
     * that. Rounded DOWN so a course with any outstanding issue can never display 100.
     */
    public function score(): int
    {
        $total = count($this->issues) + count($this->passedChecks);

        if ($total === 0) {
            return 100;
        }

        return (int) floor((count($this->passedChecks) / $total) * 100);
    }

    /** First blocker's title, for the terse single-line message the publish guard reports. */
    public function firstBlockerReason(): ?string
    {
        return $this->blockers()[0]->title ?? null;
    }
}
