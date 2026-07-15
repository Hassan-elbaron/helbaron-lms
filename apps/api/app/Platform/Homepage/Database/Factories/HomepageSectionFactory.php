<?php

namespace App\Platform\Homepage\Database\Factories;

use App\Platform\Homepage\Enums\BlockType;
use App\Platform\Homepage\Enums\HomepageStatus;
use App\Platform\Homepage\Models\HomepageSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomepageSection>
 */
class HomepageSectionFactory extends Factory
{
    protected $model = HomepageSection::class;

    public function definition(): array
    {
        $default = HomepageSection::defaults()['hero'];

        return [
            'key' => 'hero',
            'type' => BlockType::Hero,
            'position' => $default['position'],
            'is_enabled' => true,
            'status' => HomepageStatus::Published,
            'content' => $default['content'],
            'published_content' => null,
            'published_at' => null,
            'unpublished_at' => null,
            'visible_desktop' => true,
            'visible_tablet' => true,
            'visible_mobile' => true,
        ];
    }

    /** Build a section from an ORIGINAL predefined block key using its seeded default content. */
    public function block(string $key): static
    {
        $default = HomepageSection::defaults()[$key];

        return $this->state(fn () => [
            'key' => $key,
            'type' => $default['type'],
            'position' => $default['position'],
            'content' => $default['content'],
        ]);
    }

    /** Build a section for any BlockType using its placeholder default content. */
    public function ofType(BlockType $type, ?string $key = null): static
    {
        return $this->state(fn () => [
            'key' => $key ?? $type->value,
            'type' => $type,
            'content' => $type->defaultContent(),
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['is_enabled' => false]);
    }

    public function status(HomepageStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    /** Draft status — excluded from the public homepage. */
    public function draft(): static
    {
        return $this->state(fn () => ['status' => HomepageStatus::Draft]);
    }

    /** A future publish window — not yet live even though Published. */
    public function scheduledFuture(): static
    {
        return $this->state(fn () => [
            'status' => HomepageStatus::Published,
            'published_at' => now()->addWeek(),
        ]);
    }

    /** Mark the draft as already published (snapshot == current content, live now). */
    public function published(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => HomepageStatus::Published,
            'published_content' => $attrs['content'] ?? null,
            'published_at' => now(),
            'unpublished_at' => null,
        ]);
    }
}
