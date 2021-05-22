<?php

declare(strict_types = 1);

namespace Graphpinator\Normalizer;

final class Finalizer
{
    use \Nette\SmartObject;

    private \Graphpinator\Common\Path $path;

    public function finalize(NormalizedRequest $normalizedRequest, \stdClass $variables, ?string $operationName) : FinalizedRequest
    {
        $this->path = new \Graphpinator\Common\Path();

        try {
            $operation = $this->selectOperation($normalizedRequest, $operationName);
            $this->path->add($operation->getName() . ' <operation>');
            $this->applyVariables($operation, $variables);
        } catch (\Graphpinator\Exception\GraphpinatorBase $e) {
            throw $e->setPath($this->path);
        }

        return new FinalizedRequest($operation);
    }

    private function selectOperation(NormalizedRequest $normalizedRequest, ?string $operationName) : \Graphpinator\Normalizer\Operation\Operation
    {
        return $operationName === null
            ? $normalizedRequest->getOperations()->current()
            : $normalizedRequest->getOperations()->offsetGet($operationName);
    }

    private function applyVariables(\Graphpinator\Normalizer\Operation\Operation $operation, \stdClass $variables) : void
    {
        $normalized = [];

        foreach ($operation->getVariables() as $variable) {
            $this->path->add($variable->getName() . ' <variable>');
            $normalized[$variable->getName()] = $this->normalizeVariableValue($variable, $variables);
            $this->path->pop();
        }

        $operation->getSelections()->applyVariables(new \Graphpinator\Normalizer\VariableValueSet($normalized));
    }

    private function normalizeVariableValue(
        \Graphpinator\Normalizer\Variable\Variable $variable,
        \stdClass $variables,
    ) : \Graphpinator\Value\InputedValue
    {
        if (isset($variables->{$variable->getName()})) {
            return $variable->getType()->accept(new \Graphpinator\Value\ConvertRawValueVisitor($variables->{$variable->getName()}, $this->path));
        }

        if ($variable->getDefaultValue() instanceof \Graphpinator\Value\InputedValue) {
            return $variable->getDefaultValue();
        }

        return $variable->getType()->accept(new \Graphpinator\Value\ConvertRawValueVisitor(null, $this->path));
    }
}
