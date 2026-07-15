<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

/**
 * Rector configuration (Sprint 0 / A1-S02) — REPORT ONLY.
 *
 * Integrated in dry-run, non-blocking mode: it surfaces safe modernization/quality suggestions
 * but MUST NOT auto-apply changes in CI. Run locally with `composer rector` (see composer.json),
 * which invokes `rector process --dry-run`. No set here rewrites behavior; suggestions are
 * reviewed by a human before any change is made in a dedicated task.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
    ])
    ->withSkip([
        __DIR__.'/app/*/openapi/*',
    ])
    // PHP 8.3 language-level suggestions.
    ->withPhpSets(php83: true)
    // Non-destructive, high-signal quality/dead-code suggestions (report-only in dry-run).
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    );
