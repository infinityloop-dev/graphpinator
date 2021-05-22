<?php

declare(strict_types = 1);

namespace Graphpinator\Normalizer\RefinerModule;

final class DuplicateFragmentSpreadModule implements RefinerModule, \Graphpinator\Normalizer\Selection\SelectionVisitor
{
    use \Nette\SmartObject;

    private array $visitedFragments;
    private int $index;

    public function __construct(
        private \Graphpinator\Normalizer\Selection\SelectionSet $selections,
    ) {}

    public function refine() : void
    {
        $this->visitedFragments = [];

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
        if (!\array_key_exists($fragmentSpread->getName(), $this->visitedFragments)) {
            $this->visitedFragments[$fragmentSpread->getName()] = true;

            return null;
        }

        /** Found identical fragment spread, we can safely exclude it */
        $this->selections->offsetUnset($this->index);

        return null;
    }

    public function visitInlineFragment(
        \Graphpinator\Normalizer\Selection\InlineFragment $inlineFragment,
    ) : mixed
    {
        $oldSelections = $this->selections;
        $oldIndex = $this->index;

        $this->selections = $inlineFragment->getSelections();

        foreach ($inlineFragment->getSelections() as $index => $selection) {
            $this->index = $index;
            $selection->accept($this);
        }

        $this->selections = $oldSelections;
        $this->index = $oldIndex;

        return null;
    }
}
