<?php

namespace App\Platform\Seo\Database\Factories;

use App\Platform\Seo\Enums\SeoEntityType;
use App\Platform\Seo\Models\SeoMeta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeoMeta>
 */
class SeoMetaFactory extends Factory
{
    protected $model = SeoMeta::class;

    public function definition(): array
    {
        $title = ucfirst($this->faker->unique()->words(3, true));

        return [
            'entity_type' => SeoEntityType::MarketingPage,
            'entity_key' => $this->faker->unique()->slug(2),
            'meta_title' => ['en' => $title, 'ar' => $title],
            'meta_description' => ['en' => $this->faker->sentence(), 'ar' => $this->faker->sentence()],
            'keywords' => null,
            'canonical' => null,
            'robots_index' => true,
            'robots_follow' => true,
            'og_title' => null,
            'og_description' => null,
            'og_image' => null,
            'twitter_title' => null,
            'twitter_description' => null,
            'twitter_image' => null,
            'twitter_card' => 'summary_large_image',
            'json_ld' => null,
            'breadcrumb' => null,
            'hreflang' => null,
            'sitemap_enabled' => true,
            'sitemap_priority' => null,
            'sitemap_changefreq' => null,
        ];
    }

    public function forEntity(SeoEntityType $type, string $key): static
    {
        return $this->state(fn () => ['entity_type' => $type, 'entity_key' => $key]);
    }

    public function canonical(string $canonical): static
    {
        return $this->state(fn () => ['canonical' => $canonical]);
    }

    public function noindex(): static
    {
        return $this->state(fn () => ['robots_index' => false]);
    }

    public function sitemapDisabled(): static
    {
        return $this->state(fn () => ['sitemap_enabled' => false]);
    }
}
