<?php

/**
 * Architecture coverage for the Assessment boundary.
 *
 * Deptrac enforces this in CI, but Deptrac only fails when someone adds an import. These tests
 * state the intent in the test suite too, so the reason the boundary exists is visible to whoever
 * is tempted to cross it — and so a Deptrac misconfiguration cannot silently disable the rule.
 */

use App\Platform\Shared\Assessment\Contracts\LessonAssessmentPort;
use App\Platform\Shared\Assessment\Data\AssessmentRef;
use Illuminate\Support\Facades\File;

/** @return list<string> every PHP file under a domain directory */
function domainSources(string $relativePath): array
{
    $root = app_path($relativePath);

    if (! is_dir($root)) {
        return [];
    }

    return array_map(
        fn ($file) => $file->getPathname(),
        array_filter(File::allFiles($root), fn ($file) => $file->getExtension() === 'php'),
    );
}

it('never lets Authoring import an Assessment class', function () {
    // The lesson→assessment reference must travel through LessonAssessmentPort. A direct import
    // would couple curriculum authoring to the assessment engine's internals.
    $offenders = [];

    foreach (domainSources('Domains/Authoring') as $path) {
        $contents = (string) file_get_contents($path);
        if (str_contains($contents, 'App\\Domains\\Assessment')) {
            $offenders[] = basename($path);
        }
    }

    expect($offenders)->toBe([]);
});

it('never lets Assessment import another context', function () {
    // Assessment is greenfield and carries no Deptrac baseline: it must reach Catalog, Authoring
    // and Identity only through Shared contracts.
    $forbidden = [
        'App\\Domains\\Catalog',
        'App\\Domains\\Authoring',
        'App\\Contexts\\Learning',
        // Identity IMPLEMENTATION. App\Platform\Identity\Contracts (Actor) is allowed.
        'App\\Platform\\Identity\\Models',
        'App\\Platform\\Identity\\Services',
    ];

    $offenders = [];

    foreach (domainSources('Domains/Assessment') as $path) {
        $contents = (string) file_get_contents($path);

        foreach ($forbidden as $namespace) {
            if (str_contains($contents, $namespace)) {
                $offenders[] = basename($path).' → '.$namespace;
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('keeps the lesson-assessment port narrow', function () {
    // Guards against the port drifting into a generic repository. If a new method is genuinely
    // needed, this assertion should be updated deliberately — not incidentally.
    $methods = get_class_methods(LessonAssessmentPort::class);

    sort($methods);

    expect($methods)->toBe(['describe', 'resolveAttachable']);
});

it('exposes assessments across the boundary only as an immutable DTO', function () {
    $reflection = new ReflectionClass(AssessmentRef::class);

    expect($reflection->isReadOnly())->toBeTrue()
        ->and($reflection->isFinal())->toBeTrue();

    // Every property must be a scalar — an Eloquent model or enum would leak domain internals.
    foreach ($reflection->getProperties() as $property) {
        $type = $property->getType();
        expect($type instanceof ReflectionNamedType && $type->isBuiltin())
            ->toBeTrue("AssessmentRef::\${$property->getName()} must be a scalar");
    }
});
