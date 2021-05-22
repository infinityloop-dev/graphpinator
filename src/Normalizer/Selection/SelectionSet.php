<?php

declare(strict_types = 1);

namespace Graphpinator\Normalizer\Selection;

/**
 * @method \Graphpinator\Normalizer\Selection\Selection current() : object
 * @method \Graphpinator\Normalizer\Selection\Selection offsetGet($offset) : object
 */
final class SelectionSet extends \Infinityloop\Utils\ObjectSet
{
    protected const INNER_CLASS = Selection::class;

    public function applyVariables(\Graphpinator\Normalizer\VariableValueSet $variables) : void
    {
        foreach ($this as $field) {
            $field->applyVariables($variables);
        }
    }
}
