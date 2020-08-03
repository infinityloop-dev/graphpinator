<?php

declare(strict_types = 1);

namespace Graphpinator\Exception\Parser;

final class NamedValueNotDefined extends \Graphpinator\Exception\Parser\ParserError
{
    public const MESSAGE = 'NamedValue is not defined.';
}
