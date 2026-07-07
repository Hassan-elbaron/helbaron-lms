<?php

use App\Platform\Shared\Support\ApiResponse;

it('builds a success envelope', function () {
    $res = ApiResponse::success(['id' => 1], 'ok');
    expect($res->getStatusCode())->toBe(200)
        ->and($res->getData(true))->toMatchArray(['data' => ['id' => 1], 'message' => 'ok']);
});

it('builds created/updated/deleted envelopes', function () {
    expect(ApiResponse::created(['id' => 1])->getStatusCode())->toBe(201)
        ->and(ApiResponse::updated(['id' => 1])->getStatusCode())->toBe(200)
        ->and(ApiResponse::deleted()->getStatusCode())->toBe(200);
});

it('builds the standard error envelope', function () {
    $res = ApiResponse::error('VALIDATION_ERROR', 'Invalid', ['x' => 'y'], 422);
    $body = $res->getData(true);

    expect($res->getStatusCode())->toBe(422)
        ->and($body['error']['code'])->toBe('VALIDATION_ERROR')
        ->and($body['error']['message'])->toBe('Invalid')
        ->and($body['error'])->toHaveKeys(['code', 'message', 'details', 'correlation_id', 'timestamp']);
});
