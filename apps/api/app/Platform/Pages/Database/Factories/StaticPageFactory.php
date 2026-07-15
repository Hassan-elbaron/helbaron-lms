<?php

namespace App\Platform\Pages\Database\Factories;

use App\Platform\Pages\Enums\PageStatus;
use App\Platform\Pages\Enums\TemplateType;
use App\Platform\Pages\Models\StaticPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaticPage>
 */
class StaticPageFactory extends Factory
{
    protected $model = StaticPage::class;

    public function definition(): array
    {
        $title = ucfirst($this->faker->unique()->words(2, true));

        return [
            'slug' => $this->faker->unique()->slug(2),
            'template' => TemplateType::Standard,
            'title' => ['en' => $title, 'ar' => $title],
            'body' => [
                'en' => '<p>'.$this->faker->paragraph().'</p>',
                'ar' => '<p>'.$this->faker->paragraph().'</p>',
            ],
            'excerpt' => ['en' => $this->faker->sentence(), 'ar' => $this->faker->sentence()],
            'hero_image' => null,
            'status' => PageStatus::Draft,
            'published_at' => null,
            'unpublished_at' => null,
            'position' => $this->faker->numberBetween(0, 100),
            'show_in_nav' => false,
            'seo' => null,
            'author_id' => null,
            'reviewer_id' => null,
        ];
    }

    /** A live, published page (published_at in the past). */
    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PageStatus::Published,
            'published_at' => now()->subDay(),
            'unpublished_at' => null,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => PageStatus::Draft, 'published_at' => null]);
    }

    /** Published status but scheduled to go live in the future — must NOT be served yet. */
    public function scheduledFuture(): static
    {
        return $this->state(fn () => [
            'status' => PageStatus::Published,
            'published_at' => now()->addWeek(),
            'unpublished_at' => null,
        ]);
    }

    /** Published in the past but already unpublished — must NOT be served anymore. */
    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => PageStatus::Published,
            'published_at' => now()->subWeek(),
            'unpublished_at' => now()->subDay(),
        ]);
    }

    public function template(TemplateType $template): static
    {
        return $this->state(fn () => ['template' => $template]);
    }

    public function slug(string $slug): static
    {
        return $this->state(fn () => ['slug' => $slug]);
    }
}
