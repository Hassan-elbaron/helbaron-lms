<?php

namespace App\Platform\Navigation\Enums;

/**
 * The fixed set of navigation MENU LOCATIONS (mount points) an admin can populate. Each location
 * is a slot the frontend renders — a public header, a role sidebar, a legal strip, etc. The value
 * is a URL-safe slug used as the public API path segment (GET /api/v1/navigation/{location}).
 *
 * Locations are seeded once (NavigationSeeder) and cannot be created ad hoc; admins edit the
 * NavItems inside each location. The frontend always keeps a hardcoded fallback so nav never
 * disappears when a location is empty or the API is unreachable.
 */
enum MenuLocation: string
{
    case PublicHeader = 'public-header';
    case PublicFooter = 'public-footer';
    case LearnerSidebar = 'learner-sidebar';
    case InstructorSidebar = 'instructor-sidebar';
    case OrganizationSidebar = 'organization-sidebar';
    case AdminQuickLinks = 'admin-quick-links';
    case MobileMenu = 'mobile-menu';
    case UtilityMenu = 'utility-menu';
    case MegaMenu = 'mega-menu';
    case LegalMenu = 'legal-menu';

    public function label(): string
    {
        return match ($this) {
            self::PublicHeader => 'Public Header',
            self::PublicFooter => 'Public Footer',
            self::LearnerSidebar => 'Learner Sidebar',
            self::InstructorSidebar => 'Instructor Sidebar',
            self::OrganizationSidebar => 'Organization Sidebar',
            self::AdminQuickLinks => 'Admin Quick Links',
            self::MobileMenu => 'Mobile Menu',
            self::UtilityMenu => 'Utility Menu',
            self::MegaMenu => 'Mega Menu',
            self::LegalMenu => 'Legal Menu',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $l) => $l->value, self::cases());
    }

    /** @return array<string, string> value => label, for Filament option lists. */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
