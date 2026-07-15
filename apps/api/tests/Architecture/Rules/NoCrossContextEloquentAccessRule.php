<?php

declare(strict_types=1);

namespace Tests\Architecture\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Architecture rule (Sprint 0 / A1-S02): forbids direct Eloquent access across contexts, e.g.
 * `OtherContext\Models\Thing::query()` / `::where()` / `::find()` invoked from another context.
 * Querying another context's tables bypasses its ownership and ports (101 sections 3-5).
 *
 * @implements Rule<StaticCall>
 */
final class NoCrossContextEloquentAccessRule implements Rule
{
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->class instanceof Name) {
            return [];
        }

        $fqcn = $node->class->toString();

        if (! ContextResolver::isModelClass($fqcn)) {
            return [];
        }

        $target = ContextResolver::fromClass($fqcn);
        $current = ContextResolver::contextOf($scope->getClassReflection(), $scope->getFile());

        if ($target === null || $current === null || $target === $current) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                "Cross-context Eloquent access: static call on '%s' from context '%s'. Query another context only through its Port / read model.",
                $fqcn,
                $current,
            ))
                ->identifier('helbaron.crossContextEloquent')
                ->build(),
        ];
    }
}
