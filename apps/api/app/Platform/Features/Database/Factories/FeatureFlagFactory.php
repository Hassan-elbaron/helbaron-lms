<?php

namespace App\Platform\Features\Database\Factories;

use App\Platform\Features\Models\FeatureFlag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FeatureFlag>
 */
class FeatureFlagFactory extends Factory
{
    protected $model = FeatureFlag::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'key' => Str::of($name)->slug('_')->toString(),
            'name' => Str::of($name)->title()->toString(),
            'description' => null,
            'is_enabled' => true,
            'environment' => null,
            'roles' => null,
            'rollout_percentage' => null,
            'starts_at' => null,
            'ends_at' => null,
            'owner' => null,
        ];
    }

    /** Build a flag with an explicit key (handy for deterministic evaluation tests). */
    public function key(string $key): static
    {
        return $this->state(fn () => ['key' => $key]);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['is_enabled' => false]);
    }

    public function forEnvironment(string $environment): static
    {
        return $this->state(fn () => ['environment' => $environment]);
    }

    /** @param  array<int, string>  $roles */
    public function forRoles(array $roles): static
    {
        return $this->state(fn () => ['roles' => $roles]);
    }

    public function rollout(int $percentage): static
    {
        return $this->state(fn () => ['rollout_percentage' => $percentage]);
    }

    public function window(?\DateTimeInterface $startsAt, ?\DateTimeInterface $endsAt): static
    {
        return $this->state(fn () => ['starts_at' => $startsAt, 'ends_at' => $endsAt]);
    }
}
