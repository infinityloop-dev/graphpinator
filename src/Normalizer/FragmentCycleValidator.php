<?php

declare(strict_types = 1);

namespace Graphpinator\Normalizer;

final class FragmentCycleValidator
{
    use \Nette\SmartObject;

    private array $stack = [];
    private array $validated = [];

    public function __construct(
        private \Graphpinator\Parser\Fragment\FragmentSet $fragmentSet
    ) {}

    public function validate() : void
    {
        foreach ($this->fragmentSet as $fragment) {
            $this->validateFragment($fragment);
        }
    }

    private function validateFragment(\Graphpinator\Parser\Fragment\Fragment $fragment) : void
    {
        if (\array_key_exists($fragment->getName(), $this->validated)) {
            return;
        }

        if (\array_key_exists($fragment->getName(), $this->stack)) {
            throw new \Graphpinator\Normalizer\Exception\FragmentCycle();
        }

        $this->stack[$fragment->getName()] = true;
        $this->validateFieldSet($fragment->getFields());
        unset($this->stack[$fragment->getName()]);
        $this->validated[$fragment->getName()] = true;
    }

    private function validateFieldSet(\Graphpinator\Parser\Field\FieldSet $fieldSet) : void
    {
        foreach ($fieldSet as $field) {
            if ($field->getFields() instanceof \Graphpinator\Parser\Field\FieldSet) {
                $this->validateFieldSet($field->getFields());
            }
        }

        foreach ($fieldSet->getFragmentSpreads() as $spread) {
            if (!$spread instanceof \Graphpinator\Parser\FragmentSpread\NamedFragmentSpread) {
                continue;
            }

            if (!$this->fragmentSet->offsetExists($spread->getName())) {
                throw new \Graphpinator\Normalizer\Exception\UnknownFragment($spread->getName());
            }

            $this->validateFragment($this->fragmentSet->offsetGet($spread->getName()));
        }
    }
}
