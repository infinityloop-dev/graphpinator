<?php

declare(strict_types = 1);

namespace Graphpinator\Resolver;

final class ResolveVisitor implements \Graphpinator\Typesystem\TypeVisitor
{
    public function __construct(
        private ?\Graphpinator\Normalizer\Selection\SelectionSet $entitySet,
        private \Graphpinator\Value\ResolvedValue $parentResult,
    ) {}

    public function visitType(\Graphpinator\Type\Type $type) : \Graphpinator\Value\TypeValue
    {
        \assert($this->entitySet instanceof \Graphpinator\Normalizer\Selection\SelectionSet);
        $resolved = [];

        foreach ($this->entitySet as $selectionEntity) {
            $resolved += $selectionEntity->accept(new ResolveSelectionVisitor($this->parentResult));
        }

        return new \Graphpinator\Value\TypeValue($type, (object) $resolved);
    }

    public function visitInterface(\Graphpinator\Type\InterfaceType $interface) : mixed
    {
        // nothing here
    }

    public function visitUnion(\Graphpinator\Type\UnionType $union) : mixed
    {
        // nothing here
    }

    public function visitInput(\Graphpinator\Type\InputType $input) : mixed
    {
        // nothing here
    }

    public function visitScalar(\Graphpinator\Type\ScalarType $scalar) : \Graphpinator\Value\ResolvedValue
    {
        return $this->parentResult;
    }

    public function visitEnum(\Graphpinator\Type\EnumType $enum) : \Graphpinator\Value\ResolvedValue
    {
        return $this->parentResult;
    }

    public function visitNotNull(\Graphpinator\Type\NotNullType $notNull) : \Graphpinator\Value\ResolvedValue
    {
        return $notNull->getInnerType()->accept($this);
    }

    public function visitList(\Graphpinator\Type\ListType $list) : \Graphpinator\Value\ListResolvedValue
    {
        \assert($this->parentResult instanceof \Graphpinator\Value\ListIntermediateValue);

        $return = [];

        foreach ($this->parentResult->getRawValue() as $rawValue) {
            $value = $list->getInnerType()->accept(new CreateResolvedValueVisitor($rawValue));
            $return[] = $value instanceof \Graphpinator\Value\NullValue
                ? $value
                : $value->getType()->accept(new self($this->entitySet, $value));
        }

        return new \Graphpinator\Value\ListResolvedValue($list, $return);
    }
}
