<?php

namespace App\Domains\Authoring\Database\Factories;

use App\Domains\Authoring\Enums\LessonType;
use App\Domains\Authoring\Enums\PublishState;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'title' => rtrim(fake()->sentence(3), '.'),

            // Article, NOT a random type. `quiz` carries an obligation the factory cannot satisfy —
            // a quiz lesson is expected to reference a published assessment — so a random default
            // intermittently produced a lesson that is invalid by construction. That made every
            // test which publishes a course flaky the moment publish-readiness began rejecting
            // quiz lessons with no assessment: the same test passed or failed depending on the dice.
            // Tests needing another type say so explicitly via ofType().
            'type' => LessonType::Article->value,
            'content' => [],
            'position' => 0,
            'publish_state' => PublishState::Draft->value,
            'is_preview' => false,
        ];
    }

    /**
     * A published lesson — WITH content, because a published lesson without any is invalid by
     * construction.
     *
     * Same reasoning as the `type` default above. Once `lesson.empty_content` became a publish
     * BLOCKER, `published()` on its own produced a lesson that could never be published: the
     * definition's `content` default is `[]`, which is genuinely empty. Every test that built a
     * course and published it then failed on a fixture problem rather than on the behaviour it was
     * written to check.
     *
     * A test that wants a published-but-empty lesson passes `'content' => null` explicitly, which
     * overrides this state — several do, and they still work.
     */
    public function published(): static
    {
        return $this->state(fn () => [
            'publish_state' => PublishState::Published->value,
            'content' => ['html' => '<p>Lesson body.</p>'],
        ]);
    }

    public function preview(): static
    {
        return $this->state(fn () => ['is_preview' => true]);
    }

    public function ofType(LessonType $type): static
    {
        return $this->state(fn () => ['type' => $type->value]);
    }
}
