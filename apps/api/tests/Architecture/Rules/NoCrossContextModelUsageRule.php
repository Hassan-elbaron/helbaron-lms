<?php

declare(strict_types=1);

namespace Tests\Architecture\Rules;

use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Architecture rule (Sprint 0 / A1-S02): forbids referencing another bounded context's Eloquent
 * Model. Cross-context data must be reached via a Port or a published read model — never by
 * importing/using the other context's Model class (101 sections 3-5).
 *
 * @implements Rule<FullyQualified>
 */
final class NoCrossContextModelUsageRule implements Rule
{
    public function getNodeType(): string
    {
        return FullyQualified::class;
    }

    /**
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $fqcn = $node->toString();

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
                "Cross-context Model reference: '%s' is used from context '%s'. Access another context's data through a Port or a published read model, not its Eloquent Model.",
                $fqcn,
                $current,
            ))
                ->identifier('helbaron.crossContextModel')
                ->build(),
        ];
    }
}
