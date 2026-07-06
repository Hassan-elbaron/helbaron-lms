<?php

use App\Http\Middleware\AssignCorrelationId;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
 | HElbaron API bootstrap.
 | REST only under /api/v1. Liveness: GET /up and /api/v1/health. Readiness: /api/v1/health/ready.
 | Global middleware: correlation id (early) + security headers (late). Trusted proxies/hosts
 | are enforced for correct HTTPS/host handling behind a load balancer.
 */
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind ALB/CloudFront: trust forwarded headers so isSecure()/host are correct.
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES', '*') === '*' ? '*' : explode(',', (string) env('TRUSTED_PROXIES')),
        );

        // Enforce Host allow-list in production only (avoids blocking local/test hosts).
        $middleware->trustHosts(at: static function (): array {
            $hosts = array_filter(array_map('trim', explode(',', (string) env('APP_TRUSTED_HOSTS', ''))));

            return $hosts === [] ? [] : $hosts;
        }, subdomains: true);

        $middleware->prepend(AssignCorrelationId::class);
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Domain exceptions render themselves to the standard envelope; defaults handle the rest.
    })
    ->create();
