<?php

declare(strict_types=1);

namespace Tests\Architecture\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Architecture rule (Sprint 0 / A1-S02): forbids business logic (Eloquent persistence) inside
 * HTTP Controllers. Controllers validate + delegate to a domain Action/Service; they must not
 * persist directly (101 section 5). Heuristic: a persistence method call inside a file under an
 * Http/Controllers directory.
 *
 * @implements Rule<MethodCall>
 */
final class NoBusinessLogicInControllerRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! ContextResolver::isController($scope->getClassReflection(), $scope->getFile())) {
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
                "Business logic in Controller: persistence call '->%s(...)'. Controllers validate and delegate; move mutations into a domain Action/Service.",
                $method,
            ))
                ->identifier('helbaron.controllerBusinessLogic')
                ->build(),
        ];
    }
}
