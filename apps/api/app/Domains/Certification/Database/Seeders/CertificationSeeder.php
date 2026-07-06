<?php

namespace App\Domains\Certification\Database\Seeders;

use App\Domains\Certification\Enums\CertificationPermission;
use App\Domains\Certification\Models\Badge;
use App\Domains\Certification\Models\CertificateSetting;
use App\Domains\Certification\Models\CertificateTemplate;
use App\Domains\Identity\Enums\Role;
use App\Shared\Helpers\Slug;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds certification permissions, an active default template, settings, and a sample badge.
 * Idempotent.
 */
class CertificationSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (CertificationPermission::values() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        SpatieRole::findByName(Role::Admin->value, 'web')->givePermissionTo(CertificationPermission::values());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $template = CertificateTemplate::firstOrCreate(
            ['key' => 'default', 'version' => 1],
            [
                'name' => 'Default Certificate',
                'html' => '<html><body><h1>Certificate of Completion</h1>'
                    .'<p>{{ holder_name }} has successfully completed {{ course_title }}.</p>'
                    .'<p>Certificate No. {{ number }}</p><p>Verify: {{ verify_url }}</p>{{ qr_svg }}</body></html>',
                'orientation' => 'landscape',
                'is_active' => true,
            ],
        );

        CertificateSetting::firstOrCreate([], [
            'issuer_name' => (string) config('certification.issuer.name'),
            'signature_name' => 'Academy Director',
            'signature_title' => 'Director',
            'default_template_id' => $template->id,
        ]);

        Badge::firstOrCreate(
            ['slug' => Slug::make('fast-learner')],
            ['name' => 'Fast Learner', 'description' => 'Completed a course.', 'is_active' => true],
        );
    }
}
