<?php

declare(strict_types = 1);

namespace Graphpinator\Normalizer\Directive;

/**
 * @method \Graphpinator\Normalizer\Directive\Directive current() : object
 * @method \Graphpinator\Normalizer\Directive\Directive offsetGet($offset) : object
 */
final class DirectiveSet extends \Infinityloop\Utils\ObjectSet
{
    protected const INNER_CLASS = Directive::class;

    public function applyVariables(\Graphpinator\Normalizer\VariableValueSet $variables) : void
    {
        foreach ($this as $directive) {
            $directive->applyVariables($variables);
        }
    }

    public function isSame(self $compare) : bool
    {
        if ($compare->count() !== $this->count()) {
            return false;
        }

        foreach ($compare as $index => $compareItem) {
            $thisItem = $this->offsetGet($index);

            if ($thisItem->getDirective()->getName() === $compareItem->getDirective()->getName() &&
                $thisItem->getArguments()->isSame($compareItem->getArguments())) {
                continue;
            }

            return false;
        }

        return true;
    }
}
