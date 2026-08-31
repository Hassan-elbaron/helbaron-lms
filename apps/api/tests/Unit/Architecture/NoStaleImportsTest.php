<?php

/**
 * Every `use App\…;` must point at something that exists.
 *
 * WHY THIS EXISTS. Deptrac silently SKIPS references it cannot resolve, so a `use` statement naming
 * a class that has been moved or deleted passes the architecture gate without comment. That is not a
 * hypothetical: after `CreateAdminUser` moved from `App\Console\Commands` to
 * `App\Platform\Identity\Console\Commands`, a stale import of the old path survived in
 * `IdentitySeeder` through a fully green Deptrac run.
 *
 * PHPStan would catch a stale import only where the imported name is actually USED in a way it can
 * check; an import left behind after its last usage was removed, or one referenced only from a
 * docblock, slips through both gates. This closes that gap for the whole tree.
 *
 * Implemented by parsing files rather than calling class_exists(), deliberately: class_exists()
 * triggers the autoloader, which would make the result depend on composer's dump state and could run
 * side effects. Parsing is hermetic and needs no optimized autoloader.
 */
function declaredSymbolsAndNamespaces(array $roots): array
{
    $classes = [];
    $namespaces = [];

    foreach ($roots as $root) {
        if (! is_dir($root)) {
            continue;
        }

        /** @var iterable<SplFileInfo> $files */
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());

            if (preg_match('/^namespace\s+([^;]+);/m', $source, $ns) !== 1) {
                continue;
            }

            $namespace = trim($ns[1]);
            $namespaces[$namespace] = true;

            // Record every namespace SEGMENT too: `use App\A\B\Pages;` is a valid namespace import
            // even when no file declares the namespace `App\A\B\Pages` itself.
            $parts = explode('\\', $namespace);
            for ($i = 1; $i <= count($parts); $i++) {
                $namespaces[implode('\\', array_slice($parts, 0, $i))] = true;
            }

            $pattern = '/^\s*(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s+(\w+)/m';
            if (preg_match_all($pattern, $source, $types) !== false) {
                foreach ($types[1] as $type) {
                    $classes[$namespace.'\\'.$type] = $file->getPathname();
                }
            }
        }
    }

    return [$classes, $namespaces];
}

/** @return list<array{file: string, import: string}> */
function unresolvableAppImports(array $roots): array
{
    [$classes, $namespaces] = declaredSymbolsAndNamespaces($roots);
    $bad = [];

    foreach ($roots as $root) {
        if (! is_dir($root)) {
            continue;
        }

        /** @var iterable<SplFileInfo> $files */
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());

            if (preg_match_all('/^use\s+(?:function\s+|const\s+)?(App\\\\[^\s;]+)/m', $source, $uses) === false) {
                continue;
            }

            foreach ($uses[1] as $import) {
                $fq = rtrim($import, ';');

                if (isset($classes[$fq]) || isset($namespaces[$fq])) {
                    continue;
                }

                $bad[] = [
                    'file' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()),
                    'import' => $fq,
                ];
            }
        }
    }

    return $bad;
}

it('has no `use App\\...` import pointing at a class that does not exist', function (): void {
    $roots = [base_path('app'), base_path('database'), base_path('tests')];

    $bad = unresolvableAppImports($roots);

    $message = $bad === []
        ? ''
        : "Stale imports found:\n".implode("\n", array_map(
            fn (array $b): string => "  {$b['file']} -> {$b['import']}",
            $bad,
        ));

    expect($bad)->toBe([], $message);
});

/*
 * The scanner itself must be able to fail, or it is decoration.
 *
 * Both halves are asserted: a genuinely missing class IS reported, and a namespace import (Filament's
 * `use App\…\FooResource\Pages;`, which is valid PHP) is NOT. An early version of this scanner had
 * only the first half and reported 57 false positives.
 */
it('reports a genuinely missing class', function (): void {
    $dir = sys_get_temp_dir().'/stale-import-probe-'.uniqid();
    mkdir($dir.'/Sub', 0777, true);

    file_put_contents($dir.'/Real.php', "<?php\nnamespace App\\Probe;\nclass Real {}\n");
    file_put_contents(
        $dir.'/Sub/Consumer.php',
        "<?php\nnamespace App\\Probe\\Sub;\nuse App\\Probe\\Real;\nuse App\\Probe\\Ghost;\nclass Consumer {}\n",
    );

    $bad = unresolvableAppImports([$dir]);

    expect($bad)->toHaveCount(1)
        ->and($bad[0]['import'])->toBe('App\Probe\Ghost');

    array_map('unlink', glob($dir.'/Sub/*.php') ?: []);
    array_map('unlink', glob($dir.'/*.php') ?: []);
    rmdir($dir.'/Sub');
    rmdir($dir);
});

it('does not flag a namespace import', function (): void {
    $dir = sys_get_temp_dir().'/stale-import-ns-'.uniqid();
    mkdir($dir.'/Pages', 0777, true);

    file_put_contents(
        $dir.'/FooResource.php',
        "<?php\nnamespace App\\Probe;\nuse App\\Probe\\FooResource\\Pages;\nclass FooResource {}\n",
    );
    file_put_contents(
        $dir.'/Pages/ListFoo.php',
        "<?php\nnamespace App\\Probe\\FooResource\\Pages;\nclass ListFoo {}\n",
    );

    expect(unresolvableAppImports([$dir]))->toBe([]);

    array_map('unlink', glob($dir.'/Pages/*.php') ?: []);
    array_map('unlink', glob($dir.'/*.php') ?: []);
    rmdir($dir.'/Pages');
    rmdir($dir);
});
