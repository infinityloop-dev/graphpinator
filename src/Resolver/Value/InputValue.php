<?php

declare(strict_types = 1);

namespace Graphpinator\Resolver\Value;

final class InputValue extends \Graphpinator\Resolver\Value\ValidatedValue implements \ArrayAccess
{
    public function __construct(\stdClass $fields, \Graphpinator\Type\InputType $type)
    {
        $value = new \stdClass();

        foreach ($type->getArguments() as $argument) {
            $usedValue = $fields->{$argument->getName()}
                ?? $argument->getDefaultValue();

            // default values are already validated
            $value->{$argument->getName()} = $usedValue instanceof \Graphpinator\Resolver\Value\ValidatedValue
                ? $usedValue
                : $argument->getType()->createValue($usedValue);

            $argument->validateConstraints($value->{$argument->getName()});
        }

        foreach ($fields as $name => $temp) {
            if (isset($type->getArguments()[$name])) {
                continue;
            }

            throw new \Exception('Unknown field for input value');
        }

        parent::__construct($value, $type);
    }

    public function getRawValue() : \stdClass
    {
        $return = new \stdClass();

        foreach ($this->value as $name => $listItem) {
            \assert($listItem instanceof ValidatedValue);

            $return->{$name} = $listItem->getRawValue();
        }

        return $return;
    }

    public function printValue() : string
    {
        $component = [];

        foreach ($this->value as $key => $value) {
            \assert($value instanceof ValidatedValue);

            $component[$key] = $key . ':' . $value->printValue();
        }

        return '{' . \implode(',', $component) . '}';
    }

    public function offsetExists($name) : bool
    {
        return \property_exists($this->value, $name);
    }

    public function offsetGet($offset) : ValidatedValue
    {
        return $this->value->{$offset};
    }

    public function offsetSet($offset, $value) : void
    {
        throw new \Graphpinator\Exception\OperationNotSupported();
    }

    public function offsetUnset($offset) : void
    {
        throw new \Graphpinator\Exception\OperationNotSupported();
    }
}
