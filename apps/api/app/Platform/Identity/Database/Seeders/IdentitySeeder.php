<?php

namespace App\Platform\Identity\Database\Seeders;

use App\Platform\Identity\Console\Commands\CreateAdminUser;
use App\Platform\Identity\Enums\Role as IdentityRole;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Support\Env;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Roles/permissions (always) + a local development super admin (never in production).
 *
 * Hard safety rails — this seeder ships in every customer instance, so the convenience account
 * must be impossible to create by accident on a real deployment:
 *   - REFUSES to create the admin in the `production` environment, always. `db:seed --force`
 *     on a production database therefore yields roles and permissions but no login.
 *   - The credentials are env-driven (SEED_ADMIN_EMAIL / SEED_ADMIN_PASSWORD) so a shared
 *     staging box can seed a non-default password.
 *   - REFUSES the built-in default password outside `local` and `testing` unless
 *     SEED_ADMIN_PASSWORD is set explicitly.
 *
 * To create the first administrator on a real instance, use:
 *     php artisan identity:create-admin
 *
 * @see CreateAdminUser
 */
class IdentitySeeder extends Seeder
{
    /** Development-only fallback password. Never reachable outside local/testing (see run()). */
    private const DEV_PASSWORD = 'password';

    public function run(): void
    {
        // Roles and permissions are structural and safe everywhere — always seed them.
        $this->call(RolePermissionSeeder::class);

        if (! $this->mayCreateDevAdmin()) {
            return;
        }

        // Env::string, not env(). `.env.example` ships `SEED_ADMIN_PASSWORD=`, which is
        // PRESENT-but-empty, so env()'s default argument never fired and the local development
        // admin was created with Hash::make('') — an empty password nobody could log in with.
        // The production guard below is unaffected: it uses blank(), which treats '' as blank.
        $email = Env::string('SEED_ADMIN_EMAIL', 'admin@academy.local');
        $password = Env::string('SEED_ADMIN_PASSWORD', self::DEV_PASSWORD);

        if (User::where('email', $email)->exists()) {
            return;
        }

        $admin = User::create([
            'name' => Env::string('SEED_ADMIN_NAME', 'Local Admin'),
            'email' => $email,
            'password' => Hash::make($password),
            'locale' => 'en',
            'is_active' => true,
        ]);

        // `email_verified_at` is not mass-assignable, so it must be set explicitly. Without this the
        // account is blocked by RequireVerifiedEmail on every authenticated API route.
        $admin->forceFill(['email_verified_at' => now()])->save();

        $admin->profile()->create(['first_name' => 'Local', 'last_name' => 'Admin']);
        $admin->assignRole(IdentityRole::SuperAdmin->value);
    }

    /**
     * The development admin is created only where a well-known credential is harmless.
     */
    private function mayCreateDevAdmin(): bool
    {
        if (app()->environment('production')) {
            $this->command?->warn(
                'IdentitySeeder: skipping the development admin in production. '
                .'Run `php artisan identity:create-admin` to create the first administrator.'
            );

            return false;
        }

        // Anywhere that is not local/testing (staging, review apps, demo boxes) must supply its own
        // password rather than inherit the shared default.
        if (! app()->environment(['local', 'testing']) && blank(env('SEED_ADMIN_PASSWORD'))) {
            $this->command?->warn(
                'IdentitySeeder: skipping the development admin — set SEED_ADMIN_PASSWORD to seed an '
                .'admin outside local/testing, or run `php artisan identity:create-admin`.'
            );

            return false;
        }

        return true;
    }
}
