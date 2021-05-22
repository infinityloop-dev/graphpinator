<?php

declare(strict_types = 1);

namespace Graphpinator\Normalizer\RefinerModule;

final class EmptyFragmentModule implements RefinerModule, \Graphpinator\Normalizer\Selection\SelectionVisitor
{
    use \Nette\SmartObject;

    private int $index;

    public function __construct(
        private \Graphpinator\Normalizer\Selection\SelectionSet $selections,
    ) {}

    public function refine() : void
    {
        foreach ($this->selections as $index => $selection) {
            $this->index = $index;
            $selection->accept($this);
        }
    }

    public function visitField(\Graphpinator\Normalizer\Selection\Field $field) : mixed
    {
        return null;
    }

    public function visitFragmentSpread(
        \Graphpinator\Normalizer\Selection\FragmentSpread $fragmentSpread,
    ) : mixed
    {
        $refiner = new self($fragmentSpread->getSelections());
        $refiner->refine();

        if ($fragmentSpread->getSelections()->count() === 0) {
            $this->selections->offsetUnset($this->index);
        }

        return null;
    }

    public function visitInlineFragment(
        \Graphpinator\Normalizer\Selection\InlineFragment $inlineFragment,
    ) : mixed
    {
        $refiner = new self($inlineFragment->getSelections());
        $refiner->refine();

        if ($inlineFragment->getSelections()->count() === 0) {
            $this->selections->offsetUnset($this->index);
        }

        return null;
    }
}
