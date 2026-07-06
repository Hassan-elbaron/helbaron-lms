<?php

namespace App\Domains\Certification\Database\Factories;

use App\Domains\Certification\Models\CertificateTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CertificateTemplate>
 */
class CertificateTemplateFactory extends Factory
{
    protected $model = CertificateTemplate::class;

    public function definition(): array
    {
        return [
            'key' => 'default',
            'version' => 1,
            'name' => 'Default Certificate',
            'html' => '<html><body><h1>Certificate</h1><p>{{ holder_name }} completed {{ course_title }}</p><p>No. {{ number }}</p></body></html>',
            'orientation' => 'landscape',
            'is_active' => true,
        ];
    }
}
