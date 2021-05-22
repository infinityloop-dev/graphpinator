<?php

declare(strict_types = 1);

namespace Graphpinator\Normalizer\RefinerModule;

interface RefinerModule
{
    public function refine() : void;
}
