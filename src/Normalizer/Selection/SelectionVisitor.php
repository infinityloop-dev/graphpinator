<?php

declare(strict_types = 1);

namespace Graphpinator\Normalizer\Selection;

interface SelectionVisitor
{
    public function visitField(Field $field) : mixed;

    public function visitFragmentSpread(FragmentSpread $fragmentSpread) : mixed;

    public function visitInlineFragment(InlineFragment $inlineFragment) : mixed;
}
