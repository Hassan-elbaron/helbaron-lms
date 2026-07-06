<?php

namespace App\Domains\Catalog\Database\Factories;

use App\Domains\Catalog\Models\CourseLanguage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseLanguage>
 */
class CourseLanguageFactory extends Factory
{
    protected $model = CourseLanguage::class;

    public function definition(): array
    {
        $code = fake()->unique()->randomElement(['en', 'ar', 'fr', 'es']);

        return [
            'code' => $code,
            'name' => strtoupper($code),
            'position' => 0,
        ];
    }
}
