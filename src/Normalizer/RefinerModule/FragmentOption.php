<?php

declare(strict_types = 1);

namespace Graphpinator\Normalizer\RefinerModule;

final class FragmentOption
{
    use \Nette\SmartObject;

    public function __construct(
        public \Graphpinator\Normalizer\Directive\DirectiveSet $directives,
        public ?\Graphpinator\Type\Contract\TypeConditionable $typeCondition,
    ) {}
}
