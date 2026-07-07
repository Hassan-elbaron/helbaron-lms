<?php

namespace App\Platform\Identity\Database\Factories;

use App\Platform\Identity\Models\User;
use App\Platform\Identity\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfile>
 */
class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'bio' => fake()->sentence(),
            'gender' => fake()->randomElement(['male', 'female', 'unspecified']),
        ];
    }
}
