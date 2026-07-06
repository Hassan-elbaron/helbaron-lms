<?php

namespace App\Domains\Certification\Database\Factories;

use App\Domains\Catalog\Models\Course;
use App\Domains\Certification\Enums\CertificateStatus;
use App\Domains\Certification\Models\Certificate;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory()->published(),
            'number' => 'CERT-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'verification_code' => Str::upper(Str::random(16)),
            'status' => CertificateStatus::Issued->value,
            'issued_at' => now(),
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['status' => CertificateStatus::Revoked->value, 'revoked_at' => now()]);
    }
}
