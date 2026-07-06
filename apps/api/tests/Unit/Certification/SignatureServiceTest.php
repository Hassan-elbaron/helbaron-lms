<?php

use App\Domains\Certification\Models\Certificate;
use App\Domains\Certification\Services\SignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('produces a deterministic signature and detects tampering', function () {
    $cert = Certificate::factory()->create();
    $svc = new SignatureService;

    $hash = $svc->hash($cert);
    $cert->forceFill(['signature_hash' => $hash])->save();

    expect($svc->verify($cert->fresh()))->toBeTrue();

    $cert->forceFill(['number' => 'TAMPERED'])->save();
    expect($svc->verify($cert->fresh()))->toBeFalse();
});
