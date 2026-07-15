<?php

namespace App\Platform\Seo\Database\Seeders;

use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Live\Enums\LiveSessionStatus;
use App\Platform\Pages\Enums\PageStatus;
use App\Platform\Seo\Enums\SeoEntityType;
use App\Platform\Seo\Models\SeoMeta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the managed SEO records. Two layers:
 *  1. High-value singleton surfaces (homepage, certificate verify, organization).
 *  2. Sitemap-enabled override rows derived from the ACTUAL published public content — a sample of
 *     published static pages, published courses, active categories, instructors (trainers) and live
 *     events — so /api/v1/seo/sitemap reflects real content instead of returning an empty list.
 *
 * Cross-context content is read through raw DB queries (the same approach DemoSeeder uses) so the
 * seeder stays decoupled from other contexts' Eloquent models. The SeoResolver still provides the
 * per-field defaults at read time; these rows only give each entity a managed, sitemap-enabled
 * presence. Fully idempotent: firstOrCreate on (entity_type, entity_key), so re-running (and
 * migrate:fresh --seed) never duplicates rows.
 */
class SeoSeeder extends Seeder
{
    /** Cap per entity type so a large seeded/demo DB yields a representative — not exhaustive — sitemap. */
    private const SAMPLE = 50;

    public function run(): void
    {
        foreach ($this->rows() as $row) {
            SeoMeta::firstOrCreate(
                ['entity_type' => $row['entity_type']->value, 'entity_key' => $row['entity_key']],
                $row['attributes'],
            );
        }
    }

    /**
     * @return array<int, array{entity_type: SeoEntityType, entity_key: string, attributes: array<string, mixed>}>
     */
    private function rows(): array
    {
        return array_merge(
            self::singletons(),
            $this->staticPageRows(),
            $this->courseRows(),
            $this->categoryRows(),
            $this->trainerRows(),
            $this->eventRows(),
        );
    }

    /**
     * The pinned singleton surfaces.
     *
     * @return array<int, array{entity_type: SeoEntityType, entity_key: string, attributes: array<string, mixed>}>
     */
    private static function singletons(): array
    {
        $name = (string) config('app.name', 'HElbaron');

        return [
            [
                'entity_type' => SeoEntityType::Homepage,
                'entity_key' => SeoEntityType::Homepage->singletonKey(),
                'attributes' => [
                    'meta_title' => ['en' => "{$name} · Bilingual Professional Academy", 'ar' => "{$name} · أكاديمية مهنية ثنائية اللغة"],
                    'meta_description' => [
                        'en' => "{$name} is a bilingual academy for professional courses, live cohorts and workshops — learn from expert instructors and earn verifiable certificates.",
                        'ar' => "{$name} أكاديمية ثنائية اللغة للدورات المهنية والأفواج المباشرة والورش — تعلّم من خبراء واحصل على شهادات قابلة للتحقّق.",
                    ],
                    'canonical' => '/',
                    'sitemap_enabled' => true,
                    'robots_index' => true,
                    'sitemap_priority' => 1.0,
                    'sitemap_changefreq' => 'daily',
                ],
            ],
            [
                'entity_type' => SeoEntityType::CertificateVerify,
                'entity_key' => SeoEntityType::CertificateVerify->singletonKey(),
                'attributes' => [
                    'meta_title' => ['en' => 'Verify a certificate', 'ar' => 'التحقّق من شهادة'],
                    'meta_description' => [
                        'en' => "Verify the authenticity of a {$name} certificate by its unique code.",
                        'ar' => "تحقّق من صحّة شهادة {$name} عبر رمزها الفريد.",
                    ],
                    'canonical' => '/verify',
                    'robots_index' => false,
                    'sitemap_enabled' => false,
                ],
            ],
            [
                'entity_type' => SeoEntityType::Organization,
                'entity_key' => SeoEntityType::Organization->singletonKey(),
                'attributes' => [
                    'meta_title' => ['en' => $name, 'ar' => $name],
                    'canonical' => '/',
                    'sitemap_enabled' => false,
                ],
            ],
        ];
    }

    /**
     * Published CMS pages. Slugs with their own preserved top-level route (about/contact/privacy/terms)
     * get that canonical so no /p/{slug} duplicate is emitted; the rest default to /p/{slug}.
     *
     * @return array<int, array{entity_type: SeoEntityType, entity_key: string, attributes: array<string, mixed>}>
     */
    private function staticPageRows(): array
    {
        $ownRoute = ['about', 'contact', 'privacy', 'terms'];
        $now = now();

        $slugs = DB::table('static_pages')
            ->whereNull('deleted_at')
            ->where('status', PageStatus::Published->value)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('unpublished_at')->orWhere('unpublished_at', '>', $now))
            ->orderBy('id')
            ->limit(self::SAMPLE)
            ->pluck('slug');

        return $slugs->map(fn ($slug): array => [
            'entity_type' => SeoEntityType::StaticPage,
            'entity_key' => (string) $slug,
            'attributes' => self::attributes(
                canonical: in_array($slug, $ownRoute, true) ? '/'.$slug : null,
                priority: 0.6,
                changefreq: 'monthly',
            ),
        ])->all();
    }

    /**
     * @return array<int, array{entity_type: SeoEntityType, entity_key: string, attributes: array<string, mixed>}>
     */
    private function courseRows(): array
    {
        $slugs = DB::table('courses')
            ->whereNull('deleted_at')
            ->where('status', CourseStatus::Published->value)
            ->orderBy('id')
            ->limit(self::SAMPLE)
            ->pluck('slug');

        return $slugs->map(fn ($slug): array => [
            'entity_type' => SeoEntityType::Course,
            'entity_key' => (string) $slug,
            'attributes' => self::attributes(priority: 0.8, changefreq: 'weekly'),
        ])->all();
    }

    /**
     * @return array<int, array{entity_type: SeoEntityType, entity_key: string, attributes: array<string, mixed>}>
     */
    private function categoryRows(): array
    {
        $slugs = DB::table('categories')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(self::SAMPLE)
            ->pluck('slug');

        return $slugs->map(fn ($slug): array => [
            'entity_type' => SeoEntityType::Category,
            'entity_key' => (string) $slug,
            'attributes' => self::attributes(priority: 0.7, changefreq: 'weekly'),
        ])->all();
    }

    /**
     * Trainers = active users holding the 'instructor' role, addressed by public_id.
     *
     * @return array<int, array{entity_type: SeoEntityType, entity_key: string, attributes: array<string, mixed>}>
     */
    private function trainerRows(): array
    {
        $publicIds = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'instructor')
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->orderBy('users.public_id')
            ->distinct()
            ->limit(self::SAMPLE)
            ->pluck('users.public_id');

        return $publicIds->map(fn ($publicId): array => [
            'entity_type' => SeoEntityType::Trainer,
            'entity_key' => (string) $publicId,
            'attributes' => self::attributes(priority: 0.5, changefreq: 'monthly'),
        ])->all();
    }

    /**
     * Public events = non-cancelled live sessions (mirrors the public EventController listing),
     * addressed by public_id.
     *
     * @return array<int, array{entity_type: SeoEntityType, entity_key: string, attributes: array<string, mixed>}>
     */
    private function eventRows(): array
    {
        $publicIds = DB::table('live_sessions')
            ->whereNull('deleted_at')
            ->where('status', '!=', LiveSessionStatus::Cancelled->value)
            ->orderBy('id')
            ->limit(self::SAMPLE)
            ->pluck('public_id');

        return $publicIds->map(fn ($publicId): array => [
            'entity_type' => SeoEntityType::Event,
            'entity_key' => (string) $publicId,
            'attributes' => self::attributes(priority: 0.6, changefreq: 'daily'),
        ])->all();
    }

    /**
     * The common sitemap-enabled attribute bag for a derived override row. A null canonical lets the
     * SeoController/SeoResolver default it to the entity's own URL (SeoEntityType::path).
     *
     * @return array<string, mixed>
     */
    private static function attributes(?string $canonical = null, float $priority = 0.6, string $changefreq = 'weekly'): array
    {
        $attributes = [
            'sitemap_enabled' => true,
            'robots_index' => true,
            'sitemap_priority' => $priority,
            'sitemap_changefreq' => $changefreq,
        ];

        if ($canonical !== null) {
            $attributes['canonical'] = $canonical;
        }

        return $attributes;
    }
}
