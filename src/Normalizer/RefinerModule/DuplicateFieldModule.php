<?php

declare(strict_types = 1);

namespace Graphpinator\Normalizer\RefinerModule;

final class DuplicateFieldModule implements RefinerModule, \Graphpinator\Normalizer\Selection\SelectionVisitor
{
    use \Nette\SmartObject;

    private array $fieldForName;
    private \SplStack $fragmentOptions;
    private int $index;

    public function __construct(
        private \Graphpinator\Normalizer\Selection\SelectionSet $selections,
    ) {}

    public function refine() : void
    {
        $this->fieldForName = [];
        $this->fragmentOptions = new \SplStack();

        foreach ($this->selections as $index => $selection) {
            $this->index = $index;
            $selection->accept($this);
        }
    }

    public function visitField(\Graphpinator\Normalizer\Selection\Field $field) : mixed
    {
        if (!\array_key_exists($field->getOutputName(), $this->fieldForName)) {
            /** Only check for duplicates in outer scope and not between fragments */
            if ($this->fragmentOptions->count() === 0) {
                $this->fieldForName[$field->getOutputName()] = $field;
            }

            return null;
        }

        $this->compareAndCombineFields($this->fieldForName[$field->getOutputName()], $field);

        return null;
    }

    public function visitFragmentSpread(
        \Graphpinator\Normalizer\Selection\FragmentSpread $fragmentSpread,
    ) : mixed
    {
        $this->processFragment($fragmentSpread);

        return null;
    }

    public function visitInlineFragment(
        \Graphpinator\Normalizer\Selection\InlineFragment $inlineFragment,
    ) : mixed
    {
        $this->processFragment($inlineFragment);

        return null;
    }

    private function processFragment(
        \Graphpinator\Normalizer\Selection\InlineFragment|\Graphpinator\Normalizer\Selection\FragmentSpread $fragment,
    ) : void
    {
        $oldSelections = $this->selections;
        $oldIndex = $this->index;
        $this->fragmentOptions->push(new FragmentOption($fragment->getDirectives(), $fragment->getTypeCondition()));

        $this->selections = $fragment->getSelections();

        foreach ($fragment->getSelections() as $index => $selection) {
            $this->index = $index;
            $selection->accept($this);
        }

        $this->selections = $oldSelections;
        $this->index = $oldIndex;
        $this->fragmentOptions->pop();
    }

    private function compareAndCombineFields(
        \Graphpinator\Normalizer\Selection\Field $field,
        \Graphpinator\Normalizer\Selection\Field $conflict,
    ) : void
    {
        $fieldReturnType = $field->getField()->getType();
        $conflictReturnType = $conflict->getField()->getType();

        /** Fields must have same response shape (return type) */
        if (!$fieldReturnType->isInstanceOf($conflictReturnType) ||
            !$conflictReturnType->isInstanceOf($fieldReturnType)) {
            throw new \Graphpinator\Normalizer\Exception\ConflictingFieldType();
        }

        /** Fields have same alias, but refer to different field */
        if ($field->getName() !== $conflict->getName()) {
            throw new \Graphpinator\Normalizer\Exception\ConflictingFieldAlias();
        }

        /** Fields have different arguments */
        if (!$field->getArguments()->isSame($conflict->getArguments())) {
            throw new \Graphpinator\Normalizer\Exception\ConflictingFieldArguments();
        }

        /** Fields have different directives */
        if (!$field->getDirectives()->isSame($conflict->getDirectives())) {
            throw new \Graphpinator\Normalizer\Exception\ConflictingFieldDirectives();
        }

        /** Fields are composite -> combine and refine combined fields */
        if ($conflict->getSelections() instanceof \Graphpinator\Normalizer\Selection\SelectionSet) {
            $mergedSet = $conflict->getSelections()->merge($this->getSubSelections($field->getSelections()));
            $refiner = new \Graphpinator\Normalizer\SelectionSetRefiner($mergedSet);

            $field->setSelections($refiner->refine());
        }

        /** Found identical field, we can safely exclude it */
        $this->selections->offsetUnset($this->index);
    }

    private function getSubSelections(
        \Graphpinator\Normalizer\Selection\SelectionSet $selections,
    ) : \Graphpinator\Normalizer\Selection\SelectionSet
    {
        foreach ($this->fragmentOptions as $fragmentOption) {
            \assert($fragmentOption instanceof FragmentOption);

            $selections = new \Graphpinator\Normalizer\Selection\SelectionSet([
                new \Graphpinator\Normalizer\Selection\InlineFragment(
                    $selections,
                    $fragmentOption->directives,
                    $fragmentOption->typeCondition,
                ),
            ]);
        }

        return $selections;
    }

    private static function canOccurTogether(
        \Graphpinator\Type\Contract\TypeConditionable $typeA,
        \Graphpinator\Type\Contract\TypeConditionable $typeB,
    ) : bool
    {
        return $typeA->isInstanceOf($typeB) // one is instance of other
            || $typeB->isInstanceOf($typeA)
            || !($typeA instanceof \Graphpinator\Type\Type) // one is not object type
            || !($typeB instanceof \Graphpinator\Type\Type);
    }
}
