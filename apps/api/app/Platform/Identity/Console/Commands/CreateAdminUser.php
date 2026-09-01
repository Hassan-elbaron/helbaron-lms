<?php

namespace App\Platform\Identity\Console\Commands;

use App\Platform\Identity\Database\Seeders\IdentitySeeder;
use App\Platform\Identity\Enums\Role as IdentityRole;
use App\Platform\Identity\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Creates the first (or an additional) administrator on a real instance.
 *
 * This is the production-safe counterpart to {@see IdentitySeeder},
 * which deliberately refuses to create its well-known development account outside local/testing.
 *
 * The password is never echoed, never passed on the command line by default (so it does not land in
 * shell history or process listings), and never written to the log.
 *
 * Usage:
 *     php artisan identity:create-admin
 *     php artisan identity:create-admin --email=ops@academy.com --name="Ops" --role=admin
 *     php artisan identity:create-admin --email=ops@academy.com --reset-password
 */
class CreateAdminUser extends Command
{
    protected $signature = 'identity:create-admin
        {--email= : Administrator email address (prompted when omitted)}
        {--name= : Display name (prompted when omitted)}
        {--role=super_admin : Role to assign (super_admin or admin)}
        {--reset-password : Set a new password for an existing account instead of failing}';

    protected $description = 'Create the first administrator for this instance (production-safe).';

    /** Minimum length accepted for an administrator password. */
    private const MIN_PASSWORD_LENGTH = 12;

    /** Rejected outright — these are the credentials people reach for under time pressure. */
    private const FORBIDDEN_PASSWORDS = [
        'password', 'password1', 'password123', '123456', '12345678', 'admin',
        'admin123', 'changeme', 'letmein', 'secret', 'qwerty',
    ];

    public function handle(): int
    {
        $role = (string) $this->option('role');

        if (! in_array($role, [IdentityRole::SuperAdmin->value, IdentityRole::Admin->value], true)) {
            $this->error("Invalid --role '{$role}'. Use super_admin or admin.");

            return self::FAILURE;
        }

        if (! SpatieRole::where('name', $role)->exists()) {
            $this->error("Role '{$role}' does not exist. Run the structural seeders first:");
            $this->line('  php artisan db:seed --class="App\\Platform\\Identity\\Database\\Seeders\\RolePermissionSeeder" --force');

            return self::FAILURE;
        }

        $email = trim((string) ($this->option('email') ?: $this->ask('Administrator email')));
        $emailError = $this->validateEmail($email);

        if ($emailError !== null) {
            $this->error($emailError);

            return self::FAILURE;
        }

        $existing = User::withTrashed()->where('email', $email)->first();

        if ($existing !== null && ! $this->option('reset-password')) {
            $this->error("A user with email {$email} already exists.");
            $this->line('Re-run with --reset-password to set a new password and ensure the role.');

            return self::FAILURE;
        }

        $password = $this->promptForPassword();

        if ($password === null) {
            return self::FAILURE;
        }

        if ($existing !== null) {
            return $this->resetExisting($existing, $password, $role);
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Display name', 'Administrator')));

        if ($name === '') {
            $name = 'Administrator';
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'locale' => (string) config('shared.default_locale', 'en'),
            'is_active' => true,
        ]);

        // Not mass-assignable; an unverified administrator is blocked by RequireVerifiedEmail on
        // every authenticated API route, so the account must start verified.
        $user->forceFill(['email_verified_at' => now()])->save();

        $parts = preg_split('/\s+/', $name, 2) ?: [$name];
        $user->profile()->create([
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? '',
        ]);

        $user->assignRole($role);

        $this->newLine();
        $this->info("Administrator created: {$email} ({$role})");
        $this->line('The password was not written to the console, the log, or shell history.');

        return self::SUCCESS;
    }

    private function resetExisting(User $user, string $password, string $role): int
    {
        if ($user->trashed()) {
            $user->restore();
            $this->warn('The account was soft-deleted and has been restored.');
        }

        $user->forceFill([
            'password' => Hash::make($password),
            'is_active' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        $this->newLine();
        $this->info("Password reset for {$user->email} ({$role}).");

        return self::SUCCESS;
    }

    private function validateEmail(string $email): ?string
    {
        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email:rfc', 'max:255']],
        );

        return $validator->fails() ? 'A valid email address is required.' : null;
    }

    /**
     * Prompts twice, hidden, and rejects weak or well-known passwords. Returns null when the
     * operator cannot supply an acceptable password.
     */
    private function promptForPassword(): ?string
    {
        foreach (range(1, 3) as $attempt) {
            $password = (string) $this->secret('Password (input hidden, min '.self::MIN_PASSWORD_LENGTH.' characters)');
            $confirm = (string) $this->secret('Confirm password');

            if ($password !== $confirm) {
                $this->error('The passwords do not match.');

                continue;
            }

            $problem = $this->passwordProblem($password);

            if ($problem !== null) {
                $this->error($problem);

                continue;
            }

            return $password;
        }

        $this->error('Too many invalid attempts. No account was created or modified.');

        return null;
    }

    private function passwordProblem(string $password): ?string
    {
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return 'The password must be at least '.self::MIN_PASSWORD_LENGTH.' characters.';
        }

        if (in_array(mb_strtolower($password), self::FORBIDDEN_PASSWORDS, true)) {
            return 'That password is on the well-known-password deny list. Choose another.';
        }

        if (preg_match('/[A-Za-z]/', $password) !== 1 || preg_match('/\d/', $password) !== 1) {
            return 'The password must contain at least one letter and one number.';
        }

        return null;
    }
}
