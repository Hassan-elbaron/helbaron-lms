<?php

// Regression guard for the CSP split introduced to fix the Filament admin panel.
// The global locked-down API CSP (default-src 'none'; form-action 'none') previously
// leaked onto the Filament admin panel, which broke styling and blocked the login
// form from submitting in a real browser. The middleware now serves a relaxed
// `csp_web` policy for web/admin/livewire paths while keeping the locked `csp` for the API.

it('serves a relaxed CSP for the admin panel so Filament can render and submit', function () {
    $response = $this->get('/admin/login');

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->not->toBeNull();
    // Relaxed policy: forms must be allowed to POST to self (Livewire/Filament login).
    expect($csp)->toContain("form-action 'self'");
    // Styles must be allowed from self (Filament stylesheet + inline theme vars).
    expect($csp)->toContain("style-src 'self'");
    // Must NOT carry the locked-down API default.
    expect($csp)->not->toContain("default-src 'none'");
});

it('keeps the locked-down CSP for the JSON API', function () {
    $response = $this->getJson('/api/v1/health');

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->not->toBeNull();
    expect($csp)->toContain("default-src 'none'");
    expect($csp)->toContain("form-action 'none'");
});
