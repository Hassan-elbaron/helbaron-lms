<?php

namespace App\Platform\Identity\Database\Seeders;

use App\Platform\Identity\Enums\Role as IdentityRole;
use App\Platform\Identity\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local/dev convenience: a super admin account. Skips creation if the email already exists.
 * Never run with these defaults in production.
 */
class IdentitySeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        if (! User::where('email', 'admin@helbaron.local')->exists()) {
            $admin = User::create([
                'name' => 'HElbaron Admin',
                'email' => 'admin@helbaron.local',
                'password' => Hash::make('password'),
                'locale' => 'en',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $admin->profile()->create(['first_name' => 'HElbaron', 'last_name' => 'Admin']);
            $admin->assignRole(IdentityRole::SuperAdmin->value);
        }
    }
}
