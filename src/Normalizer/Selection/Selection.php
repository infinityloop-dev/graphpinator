<?php

declare(strict_types = 1);

namespace Graphpinator\Normalizer\Selection;

interface Selection
{
    public function applyVariables(\Graphpinator\Normalizer\VariableValueSet $variables) : void;

    public function accept(SelectionVisitor $visitor) : mixed;
}
