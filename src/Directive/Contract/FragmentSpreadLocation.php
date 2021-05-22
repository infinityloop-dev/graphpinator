<?php

declare(strict_types = 1);

namespace Graphpinator\Directive\Contract;

interface FragmentSpreadLocation extends ExecutableDefinition
{
    public function resolveFragmentSpreadBefore(
        \Graphpinator\Value\ArgumentValueSet $arguments,
    ) : string;

    public function resolveFragmentSpreadAfter(
        \Graphpinator\Value\ArgumentValueSet $arguments,
    ) : string;
}
