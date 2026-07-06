<?php

namespace App\Domains\Crm\Database\Seeders;

use App\Domains\Crm\Enums\CrmPermission;
use App\Domains\Crm\Enums\PipelineType;
use App\Domains\Crm\Models\Organization;
use App\Domains\Crm\Models\Pipeline;
use App\Domains\Identity\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds CRM permissions, a default sales pipeline with stages, and a sample organization.
 * Idempotent.
 */
class CrmSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (CrmPermission::values() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        SpatieRole::findByName(Role::Admin->value, 'web')->givePermissionTo(CrmPermission::values());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $pipeline = Pipeline::firstOrCreate(
            ['name' => 'Sales Pipeline'],
            ['type' => PipelineType::Sales->value, 'is_default' => true],
        );

        if ($pipeline->stages()->doesntExist()) {
            foreach ((array) config('crm.pipeline.default_stages') as $i => $name) {
                $pipeline->stages()->create([
                    'name' => $name,
                    'position' => $i,
                    'is_won' => $name === 'Won',
                    'is_lost' => $name === 'Lost',
                ]);
            }
        }

        Organization::firstOrCreate(
            ['slug' => 'acme-corp'],
            ['name' => 'Acme Corp', 'status' => 'active', 'size' => 'large'],
        );
    }
}
