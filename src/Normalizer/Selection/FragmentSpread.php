<?php

declare(strict_types = 1);

namespace Graphpinator\Normalizer\Selection;

final class FragmentSpread implements \Graphpinator\Normalizer\Selection\Selection
{
    use \Nette\SmartObject;

    public function __construct(
        private string $name,
        private \Graphpinator\Normalizer\Selection\SelectionSet $children,
        private \Graphpinator\Normalizer\Directive\DirectiveSet $directives,
        private \Graphpinator\Type\Contract\TypeConditionable $typeCondition,
    ) {}

    public function getName() : string
    {
        return $this->name;
    }

    public function getSelections() : \Graphpinator\Normalizer\Selection\SelectionSet
    {
        return $this->children;
    }

    public function getTypeCondition() : \Graphpinator\Type\Contract\TypeConditionable
    {
        return $this->typeCondition;
    }

    public function getDirectives() : \Graphpinator\Normalizer\Directive\DirectiveSet
    {
        return $this->directives;
    }

    public function applyVariables(\Graphpinator\Normalizer\VariableValueSet $variables): void
    {
        $this->children->applyVariables($variables);
    }

    public function accept(SelectionVisitor $visitor) : mixed
    {
        return $visitor->visitFragmentSpread($this);
    }
}
