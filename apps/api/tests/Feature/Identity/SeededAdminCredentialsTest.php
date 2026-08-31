<?php

use App\Platform\Identity\Database\Seeders\IdentitySeeder;
use App\Platform\Identity\Database\Seeders\RolePermissionSeeder;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    putenv('SEED_ADMIN_PASSWORD');
    unset($_ENV['SEED_ADMIN_PASSWORD'], $_SERVER['SEED_ADMIN_PASSWORD']);
});

afterEach(function (): void {
    putenv('SEED_ADMIN_PASSWORD');
    unset($_ENV['SEED_ADMIN_PASSWORD'], $_SERVER['SEED_ADMIN_PASSWORD']);
});

function setSeedPassword(?string $value): void
{
    if ($value === null) {
        putenv('SEED_ADMIN_PASSWORD');
        unset($_ENV['SEED_ADMIN_PASSWORD'], $_SERVER['SEED_ADMIN_PASSWORD']);

        return;
    }

    putenv("SEED_ADMIN_PASSWORD={$value}");
    $_ENV['SEED_ADMIN_PASSWORD'] = $value;
    $_SERVER['SEED_ADMIN_PASSWORD'] = $value;
}

function seededAdmin(): ?User
{
    return User::where('email', env('SEED_ADMIN_EMAIL', 'admin@helbaron.local'))->first();
}

/*
 * THE BUG. `.env.example` shipped `SEED_ADMIN_PASSWORD=`, which is present-with-value-''. env()'s
 * default argument does not fire for '', so the seeder ran Hash::make('') and the development admin
 * was created with an EMPTY password. Nobody could log into a fresh local install, and the failure
 * was silent — the account existed and looked correct.
 */
it('seeds a usable password when SEED_ADMIN_PASSWORD is present but empty', function (): void {
    setSeedPassword('');

    $this->seed(IdentitySeeder::class);

    $admin = seededAdmin();

    expect($admin)->not->toBeNull()
        ->and(Hash::check('', (string) $admin->password))->toBeFalse()
        ->and(Hash::check('password', (string) $admin->password))->toBeTrue();
});

it('seeds a usable password when SEED_ADMIN_PASSWORD is absent entirely', function (): void {
    setSeedPassword(null);

    $this->seed(IdentitySeeder::class);

    expect(Hash::check('password', (string) seededAdmin()->password))->toBeTrue();
});

it('honours an explicitly configured seed password', function (): void {
    setSeedPassword('S3curePassphrase!');

    $this->seed(IdentitySeeder::class);

    $admin = seededAdmin();

    expect(Hash::check('S3curePassphrase!', (string) $admin->password))->toBeTrue()
        ->and(Hash::check('password', (string) $admin->password))->toBeFalse();
});

/*
 * The account must be able to actually authenticate — the point of the fix. An empty password also
 * interacts with RequireVerifiedEmail, so this asserts the whole login path rather than the hash.
 */
it('produces a seeded admin that can log in', function (): void {
    setSeedPassword('');

    $this->seed(IdentitySeeder::class);

    $this->postJson('/api/v1/auth/login', [
        'email' => (string) env('SEED_ADMIN_EMAIL', 'admin@helbaron.local'),
        'password' => 'password',
    ])->assertOk()->assertJsonPath('data.user.email_verified', true);
});

it('refuses an empty password at the login endpoint', function (): void {
    setSeedPassword('');

    $this->seed(IdentitySeeder::class);

    // Belt and braces: even if a blank hash were ever written again, it must not be a valid login.
    $this->postJson('/api/v1/auth/login', [
        'email' => (string) env('SEED_ADMIN_EMAIL', 'admin@helbaron.local'),
        'password' => '',
    ])->assertStatus(422);
});
