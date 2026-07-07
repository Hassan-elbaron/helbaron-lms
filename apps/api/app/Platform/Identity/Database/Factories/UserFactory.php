<?php

namespace App\Platform\Identity\Database\Factories;

use App\Platform\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+9665'.fake()->numerify('########'),
            'password' => Hash::make('password'),
            'locale' => 'en',
            'is_active' => true,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withMfa(string $secret = 'JBSWY3DPEHPK3PXP'): static
    {
        return $this->state(fn () => [
            'mfa_enabled' => true,
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['code-one', 'code-two'],
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
