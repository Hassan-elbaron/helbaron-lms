<?php

namespace App\Providers;

use App\Platform\Identity\Http\Middleware\EnforceAdminMfa;
use App\Filament\Widgets\PlatformOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Company-operated admin panel at /admin (HElbaron).
 *
 * Access is gated by User::canAccessPanel() (active + super_admin/admin) and, when
 * ADMIN_REQUIRE_MFA is enabled, by EnforceAdminMfa. Resources are auto-discovered from every
 * domain's Filament/Resources directory; no business logic lives in the panel — resources read
 * existing models and defer mutations to domain Actions/Services.
 */
class AdminPanelProvider extends PanelProvider
{
    /**
     * Domains whose Filament/Resources are auto-discovered into the panel, in navigation order.
     *
     * @var array<int, string>
     */
    private const DOMAINS = [
        'Identity',
        'Catalog',
        'Authoring',
        'Learning',
        'Commerce',
        'Certification',
        'Live',
        'Crm',
        'Analytics',
        'Notifications',
    ];

    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard('web')
            ->brandName('HElbaron')
            ->login()
            ->navigationGroups([
                'Identity',
                'Catalog',
                'Authoring',
                'Learning',
                'Commerce',
                'Certification',
                'Live',
                'CRM',
                'Analytics',
                'Notifications',
            ])
            ->pages([Dashboard::class])
            ->widgets([PlatformOverview::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnforceAdminMfa::class,
            ]);

        foreach (self::DOMAINS as $domain) {
            if ($domain === 'Identity') {
                $panel->discoverResources(
                    in: app_path('Platform/Identity/Filament/Resources'),
                    for: 'App\Platform\Identity\Filament\Resources',
                );
                continue;
            }
            $panel->discoverResources(
                in: app_path("Domains/{$domain}/Filament/Resources"),
                for: "App\\Domains\\{$domain}\\Filament\\Resources",
            );
        }

        return $panel;
    }
}
