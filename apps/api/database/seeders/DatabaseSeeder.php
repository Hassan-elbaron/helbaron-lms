<?php

namespace Database\Seeders;

use App\Contexts\Analytics\Database\Seeders\AnalyticsSeeder;
use App\Domains\Authoring\Database\Seeders\AuthoringSeeder;
use App\Domains\Catalog\Database\Seeders\CatalogSeeder;
use App\Domains\Certification\Database\Seeders\CertificationSeeder;
use App\Contexts\Commerce\Database\Seeders\CommerceSeeder;
use App\Domains\Crm\Database\Seeders\CrmSeeder;
use App\Platform\Identity\Database\Seeders\IdentitySeeder;
use App\Contexts\Learning\Database\Seeders\LearningSeeder;
use App\Domains\Live\Database\Seeders\LiveSeeder;
use App\Platform\Notifications\Database\Seeders\NotificationsSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IdentitySeeder::class,
            CatalogSeeder::class,
            AuthoringSeeder::class,
            LearningSeeder::class,
            CommerceSeeder::class,
            CertificationSeeder::class,
            LiveSeeder::class,
            CrmSeeder::class,
            AnalyticsSeeder::class,
            NotificationsSeeder::class,
        ]);
    }
}
