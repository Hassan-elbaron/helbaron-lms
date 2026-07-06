<?php

use App\Domains\Analytics\Providers\AnalyticsServiceProvider;
use App\Domains\Authoring\Providers\AuthoringServiceProvider;
use App\Domains\Catalog\Providers\CatalogServiceProvider;
use App\Domains\Certification\Providers\CertificationServiceProvider;
use App\Domains\Commerce\Providers\CommerceServiceProvider;
use App\Domains\Crm\Providers\CrmServiceProvider;
use App\Domains\Identity\Providers\IdentityServiceProvider;
use App\Domains\Learning\Providers\LearningServiceProvider;
use App\Domains\Live\Providers\LiveServiceProvider;
use App\Domains\Notifications\Providers\NotificationsServiceProvider;
use App\Providers\AdminPanelProvider;
use App\Providers\AppServiceProvider;
use App\Shared\Providers\SharedServiceProvider;

/*
 | Provider registration order matters: the Shared foundation loads first, then Identity as
 | the shared kernel, then the remaining domains; Analytics and Notifications load last as
 | event consumers. The Filament admin panel loads last so resource discovery sees every domain.
 */
return [
    AppServiceProvider::class,

    SharedServiceProvider::class,

    IdentityServiceProvider::class,
    CatalogServiceProvider::class,
    AuthoringServiceProvider::class,
    LearningServiceProvider::class,
    CommerceServiceProvider::class,
    CertificationServiceProvider::class,
    LiveServiceProvider::class,
    CrmServiceProvider::class,
    AnalyticsServiceProvider::class,
    NotificationsServiceProvider::class,

    // Filament admin panel (/admin).
    AdminPanelProvider::class,
];
