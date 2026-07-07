<?php

declare(strict_types=1);

namespace Tests\Architecture\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Architecture rule (Sprint 0 / A1-S02): forbids business logic (Eloquent persistence) inside
 * Filament Resources. Resources are UI only and must delegate every mutation to a domain
 * Action/Service (101 section 5, ADR-04). Heuristic: a persistence method call inside a file
 * under a Filament/Resources directory.
 *
 * @implements Rule<MethodCall>
 */
final class NoBusinessLogicInFilamentResourceRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @return list<\PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! ContextResolver::isFilamentResource($scope->getClassReflection(), $scope->getFile())) {
            return [];
        }

        if (! $node->name instanceof Identifier) {
            return [];
        }

        $method = $node->name->toString();

        if (! in_array($method, ContextResolver::PERSISTENCE_METHODS, true)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                "Business logic in Filament Resource: persistence call '->%s(...)'. Filament is UI only; delegate mutations to a domain Action/Service.",
                $method,
            ))
                ->identifier('helbaron.filamentBusinessLogic')
                ->build(),
        ];
    }
}
