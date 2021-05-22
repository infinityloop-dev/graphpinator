<?php

declare(strict_types = 1);

namespace Graphpinator\Resolver;

final class Resolver
{
    use \Nette\SmartObject;

    public function resolve(\Graphpinator\Normalizer\FinalizedRequest $finalizedRequest) : \Graphpinator\Result
    {
        $operation = $finalizedRequest->getOperation();

        return match ($operation->getType()) {
            \Graphpinator\Tokenizer\OperationType::QUERY => $this->resolveQuery($operation),
            \Graphpinator\Tokenizer\OperationType::MUTATION => $this->resolveMutation($operation),
            \Graphpinator\Tokenizer\OperationType::SUBSCRIPTION => $this->resolveSubscription($operation),
        };
    }

    private function resolveQuery(\Graphpinator\Normalizer\Operation\Operation $operation) : \Graphpinator\Result
    {
        foreach ($operation->getDirectives() as $directive) {
            $directiveDef = $directive->getDirective();

            \assert($directiveDef instanceof \Graphpinator\Directive\Contract\QueryLocation);

            $directiveDef->resolveQueryBefore($directive->getArguments());
        }

        $resolver = new \Graphpinator\Resolver\ResolveVisitor(
            $operation->getSelections(),
            new \Graphpinator\Value\TypeIntermediateValue($operation->getRootObject(), null),
        );

        $operationValue = $operation->getRootObject()->accept($resolver);

        foreach ($operation->getDirectives() as $directive) {
            $directiveDef = $directive->getDirective();

            \assert($directiveDef instanceof \Graphpinator\Directive\Contract\QueryLocation);

            $directiveDef->resolveQueryAfter($directive->getArguments(), $operationValue);
        }

        return new \Graphpinator\Result($operationValue);
    }

    private function resolveMutation(\Graphpinator\Normalizer\Operation\Operation $operation) : \Graphpinator\Result
    {
        foreach ($operation->getDirectives() as $directive) {
            $directiveDef = $directive->getDirective();

            \assert($directiveDef instanceof \Graphpinator\Directive\Contract\MutationLocation);

            $directiveDef->resolveMutationBefore($directive->getArguments());
        }

        $resolver = new \Graphpinator\Resolver\ResolveVisitor(
            $operation->getSelections(),
            new \Graphpinator\Value\TypeIntermediateValue($operation->getRootObject(), null),
        );

        $operationValue = $operation->getRootObject()->accept($resolver);

        foreach ($operation->getDirectives() as $directive) {
            $directiveDef = $directive->getDirective();

            \assert($directiveDef instanceof \Graphpinator\Directive\Contract\MutationLocation);

            $directiveDef->resolveMutationAfter($directive->getArguments(), $operationValue);
        }

        return new \Graphpinator\Result($operationValue);
    }

    private function resolveSubscription(\Graphpinator\Normalizer\Operation\Operation $operation) : \Graphpinator\Result
    {
        foreach ($operation->getDirectives() as $directive) {
            $directiveDef = $directive->getDirective();

            \assert($directiveDef instanceof \Graphpinator\Directive\Contract\SubscriptionLocation);

            $directiveDef->resolveSubscriptionBefore($directive->getArguments());
        }

        $resolver = new \Graphpinator\Resolver\ResolveVisitor(
            $operation->getSelections(),
            new \Graphpinator\Value\TypeIntermediateValue($operation->getRootObject(), null),
        );

        $operationValue = $operation->getRootObject()->accept($resolver);

        foreach ($operation->getDirectives() as $directive) {
            $directiveDef = $directive->getDirective();

            \assert($directiveDef instanceof \Graphpinator\Directive\Contract\SubscriptionLocation);

            $directiveDef->resolveSubscriptionAfter($directive->getArguments(), $operationValue);
        }

        return new \Graphpinator\Result($operationValue);
    }
}
