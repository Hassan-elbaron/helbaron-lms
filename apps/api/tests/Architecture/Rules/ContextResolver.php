<?php

declare(strict_types=1);

namespace Tests\Architecture\Rules;

use PHPStan\Reflection\ClassReflection;

/**
 * Tooling helper for the custom architecture PHPStan rules (Sprint 0 / A1-S02, hardened A1-S03).
 *
 * Classifies classes by INHERITANCE, INTERFACES and PHP ATTRIBUTES first, falling back to file
 * path only when reflection is unavailable (e.g. references outside a class). NOT application
 * code: used only by static-analysis rules.
 */
final class ContextResolver
{
    /**
     * Persistence / mutation method names that indicate business logic (Eloquent + DB).
     *
     * @var list<string>
     */
    public const PERSISTENCE_METHODS = [
        'save', 'saveOrFail', 'update', 'updateOrCreate', 'updateOrInsert',
        'create', 'createMany', 'forceCreate', 'insert', 'insertGetId', 'insertOrIgnore',
        'delete', 'forceDelete', 'destroy', 'restore', 'truncate',
        'firstOrCreate', 'firstOrNew', 'increment', 'decrement', 'upsert',
    ];

    /** Ancestor FQCN prefixes that identify a Filament Resource (or its resource pages). */
    private const FILAMENT_ANCESTOR_PREFIXES = [
        'Filament\\Resources\\',
    ];

    /** Ancestors / conventions that identify an HTTP controller. */
    private const CONTROLLER_ANCESTORS = [
        'Illuminate\\Routing\\Controller',
    ];

    /** Marker interface short-names (extensible) that opt a class into a classification. */
    private const FILAMENT_MARKER_INTERFACES = ['FilamentResourceContract'];

    private const CONTROLLER_MARKER_INTERFACES = ['ControllerContract'];

    /** Marker attribute short-names (extensible) that opt a class into a classification. */
    private const FILAMENT_MARKER_ATTRIBUTES = ['AsFilamentResource'];

    private const CONTROLLER_MARKER_ATTRIBUTES = ['AsController'];

    // ---- Context resolution -------------------------------------------------

    /** Resolve the owning context, preferring reflection (namespace) over the file path. */
    public static function contextOf(?ClassReflection $class, string $path): ?string
    {
        if ($class !== null) {
            $byName = self::fromClass($class->getName());
            if ($byName !== null) {
                return $byName;
            }
            if (preg_match('#^App\\\\(?:Providers|Http|Console|Filament|Logging)\\\\#', ltrim($class->getName(), '\\')) === 1) {
                return 'Kernel';
            }
        }

        return self::fromPath($path);
    }

    /** Resolve the owning context from a file path (fallback). "Kernel" for app-level glue. */
    public static function fromPath(string $path): ?string
    {
        $path = str_replace('\\', '/', $path);

        if (preg_match('#/app/(?:Domains|Contexts|Platform)/([A-Za-z0-9]+)/#', $path, $m) === 1) {
            return $m[1];
        }

        if (preg_match('#/app/(Providers|Http|Console|Filament|Logging)/#', $path) === 1) {
            return 'Kernel';
        }

        return null;
    }

    /** Resolve the owning context from a fully-qualified class name. */
    public static function fromClass(string $fqcn): ?string
    {
        $fqcn = ltrim($fqcn, '\\');

        if (preg_match('#^App\\\\(?:Domains|Contexts|Platform)\\\\([A-Za-z0-9]+)\\\\#', $fqcn, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /** True when the FQCN lives in a context's Models namespace. */
    public static function isModelClass(string $fqcn): bool
    {
        return str_contains(ltrim($fqcn, '\\'), '\\Models\\');
    }

    // ---- Class-kind classification (reflection-first, path fallback) --------

    public static function isFilamentResource(?ClassReflection $class, string $path): bool
    {
        if ($class !== null) {
            foreach (self::lineage($class) as $name) {
                foreach (self::FILAMENT_ANCESTOR_PREFIXES as $prefix) {
                    if (str_starts_with(ltrim($name, '\\'), $prefix)) {
                        return true;
                    }
                }
            }
            if (self::implementsMarker($class, self::FILAMENT_MARKER_INTERFACES)
                || self::hasMarkerAttribute($class, self::FILAMENT_MARKER_ATTRIBUTES)) {
                return true;
            }
        }

        // Fallback: path.
        return str_contains(str_replace('\\', '/', $path), '/Filament/Resources/');
    }

    public static function isController(?ClassReflection $class, string $path): bool
    {
        if ($class !== null) {
            foreach (self::lineage($class) as $name) {
                $name = ltrim($name, '\\');
                if (in_array($name, self::CONTROLLER_ANCESTORS, true) || str_ends_with($name, '\\Controller')) {
                    return true;
                }
            }
            if (str_ends_with($class->getName(), 'Controller')) {
                return true;
            }
            if (self::implementsMarker($class, self::CONTROLLER_MARKER_INTERFACES)
                || self::hasMarkerAttribute($class, self::CONTROLLER_MARKER_ATTRIBUTES)) {
                return true;
            }
        }

        // Fallback: path.
        return str_contains(str_replace('\\', '/', $path), '/Http/Controllers/');
    }

    // ---- Reflection helpers -------------------------------------------------

    /**
     * Parent classes + implemented interfaces of a class (best-effort, exception-safe).
     *
     * @return list<string>
     */
    private static function lineage(ClassReflection $class): array
    {
        $names = [$class->getName()];

        try {
            $parent = $class->getParentClass();
            while ($parent !== null) {
                $names[] = $parent->getName();
                $parent = $parent->getParentClass();
            }
        } catch (\Throwable) {
            // ignore unresolved parents
        }

        try {
            foreach ($class->getNativeReflection()->getInterfaceNames() as $interface) {
                $names[] = $interface;
            }
        } catch (\Throwable) {
            // ignore unresolved interfaces
        }

        return $names;
    }

    /** @param list<string> $markers interface short-names */
    private static function implementsMarker(ClassReflection $class, array $markers): bool
    {
        try {
            foreach ($class->getNativeReflection()->getInterfaceNames() as $interface) {
                $short = self::shortName($interface);
                if (in_array($short, $markers, true)) {
                    return true;
                }
            }
        } catch (\Throwable) {
            // ignore
        }

        return false;
    }

    /** @param list<string> $markers attribute short-names */
    private static function hasMarkerAttribute(ClassReflection $class, array $markers): bool
    {
        try {
            foreach ($class->getNativeReflection()->getAttributes() as $attribute) {
                $short = self::shortName($attribute->getName());
                if (in_array($short, $markers, true)) {
                    return true;
                }
            }
        } catch (\Throwable) {
            // ignore
        }

        return false;
    }

    private static function shortName(string $fqcn): string
    {
        $fqcn = ltrim($fqcn, '\\');
        $pos = strrpos($fqcn, '\\');

        return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
    }
}
