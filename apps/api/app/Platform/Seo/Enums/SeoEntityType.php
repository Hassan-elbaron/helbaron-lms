<?php

namespace App\Platform\Seo\Enums;

/**
 * The set of surfaces the centralized SEO Manager can override. Each value is a stable key stored on
 * a seo_metas row (entity_type). Per-entity types (Course/Category/Trainer/Event) address a specific
 * record by its slug/public_id; the remaining types are singletons keyed by a fixed string
 * (e.g. 'homepage', 'certificate-verify', 'organization').
 *
 * This enum owns the mapping from an (entity_type, entity_key) pair to the public site-relative path,
 * so the resolver can default a canonical to the entity's URL without duplicating routing knowledge.
 */
enum SeoEntityType: string
{
    case Homepage = 'homepage';
    case StaticPage = 'static_page';
    case Course = 'course';
    case Category = 'category';
    case Trainer = 'trainer';
    case Event = 'event';
    case MarketingPage = 'marketing_page';
    case CertificateVerify = 'certificate_verify';
    case Organization = 'organization';

    public function label(): string
    {
        return match ($this) {
            self::Homepage => 'Homepage',
            self::StaticPage => 'Static page',
            self::Course => 'Course',
            self::Category => 'Category',
            self::Trainer => 'Trainer',
            self::Event => 'Event',
            self::MarketingPage => 'Marketing page',
            self::CertificateVerify => 'Certificate verification',
            self::Organization => 'Organization',
        };
    }

    /**
     * Singletons have a single logical instance (one homepage, one org profile) and use a fixed key.
     * Per-entity types address a specific record by slug/public_id.
     */
    public function isSingleton(): bool
    {
        return match ($this) {
            self::Homepage, self::CertificateVerify, self::Organization => true,
            default => false,
        };
    }

    /** The conventional fixed key for a singleton type (ignored for per-entity types). */
    public function singletonKey(): string
    {
        return match ($this) {
            self::Homepage => 'homepage',
            self::CertificateVerify => 'certificate-verify',
            self::Organization => 'organization',
            default => $this->value,
        };
    }

    /**
     * The public, site-relative path for an entity of this type. Used to default a canonical URL and
     * to place the entity in the sitemap. Keeps routing knowledge in one place.
     */
    public function path(string $key): string
    {
        $key = ltrim($key, '/');

        return match ($this) {
            self::Homepage, self::Organization => '/',
            self::CertificateVerify => '/verify',
            self::StaticPage => '/p/'.$key,
            self::Course => '/courses/'.$key,
            self::Category => '/categories/'.$key,
            self::Trainer => '/trainers/'.$key,
            self::Event => '/events/'.$key,
            self::MarketingPage => '/'.$key,
        };
    }

    /** @return array<string, string> value => label, for Filament selects. */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
