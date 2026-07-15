<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Regression: an unauthenticated api/* request must render the standard JSON 401 envelope for ANY
 * Accept header. Previously, without `Accept: application/json` the framework tried to redirect to a
 * (non-existent) named `login` route and returned HTTP 500 (RouteNotFoundException).
 */
it('returns JSON 401 for an unauthenticated api request without an Accept header', function () {
    $response = $this->get('/api/v1/reports', ['Accept' => '*/*']);

    $response->assertStatus(401)
        ->assertHeader('content-type', 'application/json')
        ->assertJsonPath('error.code', 'UNAUTHENTICATED')
        ->assertJsonPath('error.message', 'Unauthenticated.');
});

it('returns JSON 401 for an unauthenticated api request with no Accept header at all', function () {
    $response = $this->get('/api/v1/my-learning');

    $response->assertStatus(401)->assertJsonPath('error.code', 'UNAUTHENTICATED');
});

it('still returns JSON 401 for an unauthenticated api request that asks for JSON', function () {
    $this->getJson('/api/v1/reports')
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'UNAUTHENTICATED');
});
