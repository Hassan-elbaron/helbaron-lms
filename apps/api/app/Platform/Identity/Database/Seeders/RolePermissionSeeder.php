<?php

namespace App\Platform\Identity\Database\Seeders;

use App\Platform\Identity\Enums\Permission as IdentityPermission;
use App\Platform\Identity\Enums\Role as IdentityRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds Identity roles + permissions (additive, idempotent). super_admin is granted all
 * permissions implicitly via policy before() hooks, so it needs no explicit attachments.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (IdentityPermission::values() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (IdentityRole::values() as $role) {
            Role::findOrCreate($role, 'web');
        }

        // Admin manages users/roles; instructor/student get no identity-management perms here.
        Role::findByName(IdentityRole::Admin->value, 'web')->givePermissionTo([
            IdentityPermission::ViewUsers->value,
            IdentityPermission::ManageUsers->value,
            IdentityPermission::ViewRoles->value,
            IdentityPermission::ManageRoles->value,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
