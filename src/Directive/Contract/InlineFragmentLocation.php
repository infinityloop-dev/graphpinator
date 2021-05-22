<?php

declare(strict_types = 1);

namespace Graphpinator\Directive\Contract;

interface InlineFragmentLocation extends ExecutableDefinition
{
    public function resolveInlineFragmentBefore(
        \Graphpinator\Value\ArgumentValueSet $arguments,
    ) : string;

    public function resolveInlineFragmentAfter(
        \Graphpinator\Value\ArgumentValueSet $arguments,
    ) : string;
}
