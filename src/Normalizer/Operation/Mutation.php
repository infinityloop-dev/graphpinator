<?php

declare(strict_types = 1);

namespace Graphpinator\Normalizer\Operation;

final class Mutation extends Operation
{
    public function resolve() : \Graphpinator\Result
    {
        foreach ($this->getDirectives() as $directive) {
            $directiveDef = $directive->getDirective();

            \assert($directiveDef instanceof \Graphpinator\Directive\Contract\MutationLocation);

            $directiveDef->resolveMutationBefore($directive->getArguments());
        }

        $resolver = new \Graphpinator\Resolver\ResolveVisitor(
            $this->children,
            new \Graphpinator\Value\TypeIntermediateValue($this->operation, null),
        );

        $operationValue = $this->operation->accept($resolver);

        foreach ($this->getDirectives() as $directive) {
            $directiveDef = $directive->getDirective();

            \assert($directiveDef instanceof \Graphpinator\Directive\Contract\MutationLocation);

            $directiveDef->resolveMutationAfter($operationValue, $directive->getArguments());
        }

        return new \Graphpinator\Result($operationValue);
    }
}