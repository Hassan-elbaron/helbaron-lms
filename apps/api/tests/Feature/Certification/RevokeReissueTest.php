<?php

use App\Domains\Catalog\Models\Course;
use App\Domains\Certification\Actions\GenerateCertificateAction;
use App\Domains\Certification\Models\CertificateTemplate;
use App\Platform\Identity\Database\Seeders\RolePermissionSeeder;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lets an admin revoke and reissue a certificate', function () {
    $this->seed(RolePermissionSeeder::class);
    CertificateTemplate::factory()->create(['is_active' => true]);

    $cert = app(GenerateCertificateAction::class)->executeByUserId(User::factory()->create()->id, Course::factory()->published()->create());

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/admin/certificates/{$cert->public_id}/revoke")
        ->assertOk()->assertJsonPath('data.status', 'revoked');

    $this->postJson("/api/v1/admin/certificates/{$cert->public_id}/reissue")
        ->assertOk()->assertJsonPath('data.status', 'issued');

    expect($cert->fresh()->reissued_at)->not->toBeNull();
});
