<?php

use App\Platform\Shared\Support\CloudFrontUrlSigner;

it('produces a CloudFront signed URL whose signature verifies with the public key', function () {
    $keypair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($keypair, $privatePem);
    $publicPem = openssl_pkey_get_details($keypair)['key'];

    $expires = time() + 600;
    $signer = new CloudFrontUrlSigner('https://cdn.helbaron.test', 'KPID123', $privatePem);
    $url = $signer->sign('media/x.mp4', $expires);

    expect($url)->toContain('https://cdn.helbaron.test/media/x.mp4?')
        ->and($url)->toContain('Expires='.$expires)
        ->and($url)->toContain('Key-Pair-Id=KPID123')
        ->and($url)->toContain('Signature=');

    // Rebuild the canned policy and verify the RSA-SHA1 signature.
    parse_str(parse_url($url, PHP_URL_QUERY), $q);
    $signature = base64_decode(strtr($q['Signature'], '-~_', '+/='));
    $policy = json_encode([
        'Statement' => [[
            'Resource' => 'https://cdn.helbaron.test/media/x.mp4',
            'Condition' => ['DateLessThan' => ['AWS:EpochTime' => $expires]],
        ]],
    ], JSON_UNESCAPED_SLASHES);

    expect(openssl_verify($policy, $signature, $publicPem, OPENSSL_ALGO_SHA1))->toBe(1);
});
