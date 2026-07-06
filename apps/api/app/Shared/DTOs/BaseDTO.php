<?php

namespace App\Shared\DTOs;

use ReflectionClass;
use ReflectionNamedType;

/**
 * Base Data Transfer Object. Immutable value bag with generic array hydration/serialization
 * built on constructor-promoted properties. No business logic.
 *
 * Subclasses declare a promoted constructor, e.g.:
 *   public function __construct(public readonly string $name, public readonly ?int $age = null) {}
 */
abstract class BaseDTO
{
    /**
     * Hydrate the DTO from an associative array, mapping keys to constructor parameters.
     * Missing optional parameters fall back to their defaults.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        $constructor = (new ReflectionClass(static::class))->getConstructor();

        if ($constructor === null) {
            return new static;
        }

        $args = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $data)) {
                $args[] = $data[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif ($param->getType() instanceof ReflectionNamedType && $param->getType()->allowsNull()) {
                $args[] = null;
            } else {
                $args[] = null;
            }
        }

        return new static(...$args);
    }

    /**
     * Serialize public properties to an array (nested BaseDTOs are expanded).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $out = [];

        foreach (get_object_vars($this) as $key => $value) {
            $out[$key] = $value instanceof self ? $value->toArray() : $value;
        }

        return $out;
    }
}
