<?php

declare(strict_types = 1);

namespace Graphpinator\Normalizer;

use Graphpinator\Directive\ExecutableDirectiveLocation;

final class Normalizer
{
    use \Nette\SmartObject;

    private \Graphpinator\Common\Path $path;
    private \SplStack $scopeStack;
    private \Graphpinator\Parser\Fragment\FragmentSet $fragmentDefinitions;
    private \Graphpinator\Normalizer\Variable\VariableSet $variableSet;

    public function __construct(
        private \Graphpinator\Type\Schema $schema,
    ) {}

    public function normalize(\Graphpinator\Parser\ParsedRequest $parsedRequest) : NormalizedRequest
    {
        $fragmentCycleValidator = new FragmentCycleValidator($parsedRequest->getFragments());
        $fragmentCycleValidator->validate();

        $this->path = new \Graphpinator\Common\Path();
        $this->scopeStack = new \SplStack();
        $this->fragmentDefinitions = $parsedRequest->getFragments();

        try {
            return new \Graphpinator\Normalizer\NormalizedRequest(
                $this->normalizeOperationSet($parsedRequest->getOperations())
            );
        } catch (\Graphpinator\Exception\GraphpinatorBase $e) {
            throw $e->setPath($this->path);
        }
    }

    private function normalizeOperationSet(
        \Graphpinator\Parser\Operation\OperationSet $operationSet,
    ) : \Graphpinator\Normalizer\Operation\OperationSet
    {
        $normalized = [];

        foreach ($operationSet as $operation) {
            $this->path->add($operation->getName() . ' <operation>');
            $normalized[] = $this->normalizeOperation($operation);
            $this->path->pop();
        }

        return new \Graphpinator\Normalizer\Operation\OperationSet($normalized);
    }

    private function normalizeOperation(
        \Graphpinator\Parser\Operation\Operation $operation,
    ) : \Graphpinator\Normalizer\Operation\Operation
    {
        $rootObject = match ($operation->getType()) {
            \Graphpinator\Tokenizer\OperationType::QUERY => $this->schema->getQuery(),
            \Graphpinator\Tokenizer\OperationType::MUTATION => $this->schema->getMutation(),
            \Graphpinator\Tokenizer\OperationType::SUBSCRIPTION => $this->schema->getSubscription(),
        };

        if (!$rootObject instanceof \Graphpinator\Type\Type) {
            throw new \Graphpinator\Normalizer\Exception\OperationNotSupported($operation->getType());
        }

        $this->scopeStack->push($rootObject);

        $this->variableSet = $this->normalizeVariables($operation->getVariables());
        $children = $this->normalizeFieldSet($operation->getFields());
        $directives = $this->normalizeDirectiveSet($operation->getDirectives(), \strtoupper($operation->getType()));

        $this->scopeStack->pop();

        return new \Graphpinator\Normalizer\Operation\Operation(
            $operation->getType(),
            $operation->getName(),
            $rootObject,
            $children,
            $this->variableSet,
            $directives,
        );
    }

    private function normalizeVariables(
        \Graphpinator\Parser\Variable\VariableSet $variableSet,
    ) : \Graphpinator\Normalizer\Variable\VariableSet
    {
        $normalized = [];

        foreach ($variableSet as $variable) {
            $this->path->add($variable->getName() . ' <variable>');
            $normalized[] = $this->normalizeVariable($variable);
            $this->path->pop();
        }

        return new \Graphpinator\Normalizer\Variable\VariableSet($normalized);
    }

    private function normalizeVariable(
        \Graphpinator\Parser\Variable\Variable $variable,
    ) : \Graphpinator\Normalizer\Variable\Variable
    {
        $type = $this->normalizeTypeRef($variable->getType());
        $defaultValue = $variable->getDefault();

        if (!$type->isInputable()) {
            throw new \Graphpinator\Normalizer\Exception\VariableTypeInputable($variable->getName());
        }

        \assert($type instanceof \Graphpinator\Type\Contract\Inputable);

        return new \Graphpinator\Normalizer\Variable\Variable(
            $variable->getName(),
            $type,
            $defaultValue instanceof \Graphpinator\Parser\Value\Value
                ? $defaultValue->accept(new \Graphpinator\Value\ConvertParserValueVisitor($type, null, $this->path))
                : null,
        );
    }

    private function normalizeFieldSet(
        \Graphpinator\Parser\Field\FieldSet $fieldSet,
    ) : \Graphpinator\Normalizer\Selection\SelectionSet
    {
        $normalized = [];

        foreach ($fieldSet as $field) {
            $this->path->add($field->getName() . ' <field>');
            $normalized[] = $this->normalizeField($field);
            $this->path->pop();
        }

        foreach ($fieldSet->getFragmentSpreads() as $fragmentSpread) {
            $normalized[] = $this->normalizeFragmentSpread($fragmentSpread);
            $this->path->pop();
            $this->scopeStack->pop();
        }

        $result = new \Graphpinator\Normalizer\Selection\SelectionSet($normalized);
        $selectionSetRefiner = new \Graphpinator\Normalizer\SelectionSetRefiner($result);

        return $selectionSetRefiner->refine();
    }

    private function normalizeField(
        \Graphpinator\Parser\Field\Field $field,
    ) : \Graphpinator\Normalizer\Selection\Field
    {
        $parentType = $this->scopeStack->top();

        $fieldDef = $parentType->accept(new GetFieldVisitor($field->getName()));
        $fieldType = $fieldDef->getType()->getNamedType();

        $this->scopeStack->push($fieldType);

        $arguments = $this->normalizeArgumentValueSet($field->getArguments(), $fieldDef->getArguments());
        $directives = $field->getDirectives() instanceof \Graphpinator\Parser\Directive\DirectiveSet
            ? $this->normalizeDirectiveSet($field->getDirectives(), ExecutableDirectiveLocation::FIELD, $fieldDef)
            : new \Graphpinator\Normalizer\Directive\DirectiveSet();
        $children = $field->getFields() instanceof \Graphpinator\Parser\Field\FieldSet
            ? $this->normalizeFieldSet($field->getFields())
            : null;

        if ($children === null && !$fieldType instanceof \Graphpinator\Type\Contract\LeafDefinition) {
            throw new \Graphpinator\Normalizer\Exception\SelectionOnComposite();
        }

        $this->scopeStack->pop();

        return new \Graphpinator\Normalizer\Selection\Field(
            $fieldDef,
            $field->getAlias()
                ?? $fieldDef->getName(),
            $arguments,
            $directives,
            $children,
        );
    }

    private function normalizeDirectiveSet(
        \Graphpinator\Parser\Directive\DirectiveSet $directiveSet,
        string $location,
        \Graphpinator\Field\Field|null $usage = null,
    ) : \Graphpinator\Normalizer\Directive\DirectiveSet
    {
        $normalized = [];
        $directiveTypes = [];

        foreach ($directiveSet as $directive) {
            $this->path->add($directive->getName() . ' <directive>');
            $normalizedDirective = $this->normalizeDirective($directive, $usage, $location);
            $directiveDef = $normalizedDirective->getDirective();

            if (!$directiveDef->isRepeatable()) {
                if (\array_key_exists($directiveDef->getName(), $directiveTypes)) {
                    throw new \Graphpinator\Normalizer\Exception\DuplicatedDirective($directiveDef->getName());
                }

                $directiveTypes[$directiveDef->getName()] = true;
            }

            $normalized[] = $normalizedDirective;
            $this->path->pop();
        }

        return new \Graphpinator\Normalizer\Directive\DirectiveSet($normalized);
    }

    private function normalizeDirective(
        \Graphpinator\Parser\Directive\Directive $directive,
        \Graphpinator\Field\Field|null $usage,
        string $location,
    ) : \Graphpinator\Normalizer\Directive\Directive
    {
        $directiveDef = $this->schema->getContainer()->getDirective($directive->getName());

        if (!$directiveDef instanceof \Graphpinator\Directive\Directive) {
            throw new \Graphpinator\Normalizer\Exception\UnknownDirective($directive->getName());
        }

        if (!$directiveDef instanceof \Graphpinator\Directive\Contract\ExecutableDefinition) {
            throw new \Graphpinator\Normalizer\Exception\DirectiveNotExecutable($directive->getName());
        }

        if (!\in_array($location, $directiveDef->getLocations(), true)) {
            throw new \Graphpinator\Normalizer\Exception\DirectiveIncorrectLocation($directive->getName());
        }

        $arguments = $this->normalizeArgumentValueSet($directive->getArguments(), $directiveDef->getArguments());

        if ($location === \Graphpinator\Directive\ExecutableDirectiveLocation::FIELD &&
            !$directiveDef->validateFieldUsage($usage, $arguments)) {
            throw new \Graphpinator\Normalizer\Exception\DirectiveIncorrectUsage($directive->getName());
        }

        return new \Graphpinator\Normalizer\Directive\Directive($directiveDef, $arguments);
    }

    private function normalizeArgumentValueSet(
        ?\Graphpinator\Parser\Value\ArgumentValueSet $argumentValueSet,
        \Graphpinator\Argument\ArgumentSet $argumentSet,
    ) : \Graphpinator\Value\ArgumentValueSet
    {
        $argumentValueSet ??= new \Graphpinator\Parser\Value\ArgumentValueSet();
        $items = [];

        foreach ($argumentValueSet as $value) {
            if (!$argumentSet->offsetExists($value->getName())) {
                throw new \Graphpinator\Normalizer\Exception\UnknownArgument($value->getName());
            }
        }

        foreach ($argumentSet as $argument) {
            $this->path->add($argument->getName() . ' <argument>');

            if (!$argumentValueSet->offsetExists($argument->getName())) {
                $items[] = \Graphpinator\Value\ConvertRawValueVisitor::convertArgument($argument, null, $this->path);
                $this->path->pop();

                continue;
            }

            $parsedArg = $argumentValueSet->offsetGet($argument->getName());
            $items[] = \Graphpinator\Value\ConvertParserValueVisitor::convertArgumentValue(
                $parsedArg->getValue(),
                $argument,
                $this->variableSet,
                $this->path,
            );
            $this->path->pop();
        }

        return new \Graphpinator\Value\ArgumentValueSet($items);
    }

    private function normalizeFragmentSpread(
        \Graphpinator\Parser\FragmentSpread\FragmentSpread $fragmentSpread,
    ) : \Graphpinator\Normalizer\Selection\Selection
    {
        return match($fragmentSpread::class) {
            \Graphpinator\Parser\FragmentSpread\NamedFragmentSpread::class =>
                $this->normalizeNamedFragmentSpread($fragmentSpread),
            \Graphpinator\Parser\FragmentSpread\InlineFragmentSpread::class =>
                $this->normalizeInlineFragmentSpread($fragmentSpread),
        };
    }

    private function normalizeNamedFragmentSpread(
        \Graphpinator\Parser\FragmentSpread\NamedFragmentSpread $fragmentSpread,
    ) : \Graphpinator\Normalizer\Selection\FragmentSpread
    {
        $this->path->add($fragmentSpread->getName() . ' <fragment spread>');

        if (!$this->fragmentDefinitions->offsetExists($fragmentSpread->getName())) {
            throw new \Graphpinator\Normalizer\Exception\UnknownFragment($fragmentSpread->getName());
        }

        $fragment = $this->fragmentDefinitions->offsetGet($fragmentSpread->getName());
        $typeCond = $this->normalizeTypeRef($fragment->getTypeCond());

        if (!$typeCond instanceof \Graphpinator\Type\Contract\TypeConditionable) {
            throw new \Graphpinator\Normalizer\Exception\TypeConditionOutputable();
        }

        if (!$typeCond->isInstanceOf($this->scopeStack->top())) {
            throw new \Graphpinator\Normalizer\Exception\InvalidFragmentType($typeCond->getName(), $this->scopeStack->top()->getName());
        }

        $this->scopeStack->push($typeCond);

        $fields = $this->normalizeFieldSet($fragment->getFields());
        $directives = $this->normalizeDirectiveSet(
            $fragmentSpread->getDirectives(),
            ExecutableDirectiveLocation::FRAGMENT_SPREAD,
        );

        return new \Graphpinator\Normalizer\Selection\FragmentSpread($fragmentSpread->getName(), $fields, $directives, $typeCond);
    }

    private function normalizeInlineFragmentSpread(
        \Graphpinator\Parser\FragmentSpread\InlineFragmentSpread $fragmentSpread,
    ) : \Graphpinator\Normalizer\Selection\InlineFragment
    {
        $this->path->add('<inline fragment>');

        $typeCond = $fragmentSpread->getTypeCond() instanceof \Graphpinator\Parser\TypeRef\NamedTypeRef
            ? $this->normalizeTypeRef($fragmentSpread->getTypeCond())
            : null;

        if ($typeCond instanceof \Graphpinator\Type\Contract\NamedDefinition) {
            if (!$typeCond instanceof \Graphpinator\Type\Contract\TypeConditionable) {
                throw new \Graphpinator\Normalizer\Exception\TypeConditionOutputable();
            }

            if (!$typeCond->isInstanceOf($this->scopeStack->top())) {
                throw new \Graphpinator\Normalizer\Exception\InvalidFragmentType($typeCond->getName(), $this->scopeStack->top()->getName());
            }

            $this->scopeStack->push($typeCond);
        } else {
            $this->scopeStack->push($this->scopeStack->top());
        }

        $fields = $this->normalizeFieldSet($fragmentSpread->getFields());
        $directives = $this->normalizeDirectiveSet(
            $fragmentSpread->getDirectives(),
            ExecutableDirectiveLocation::INLINE_FRAGMENT,
        );

        return new \Graphpinator\Normalizer\Selection\InlineFragment($fields, $directives, $typeCond);
    }

    private function normalizeTypeRef(
        \Graphpinator\Parser\TypeRef\TypeRef $typeRef,
    ) : \Graphpinator\Type\Contract\Definition
    {
        return match ($typeRef::class) {
            \Graphpinator\Parser\TypeRef\NamedTypeRef::class =>
                $this->schema->getContainer()->getType($typeRef->getName())
                    ?? throw new \Graphpinator\Normalizer\Exception\UnknownType($typeRef->getName()),
            \Graphpinator\Parser\TypeRef\ListTypeRef::class =>
                new \Graphpinator\Type\ListType($this->normalizeTypeRef($typeRef->getInnerRef())),
            \Graphpinator\Parser\TypeRef\NotNullRef::class =>
                new \Graphpinator\Type\NotNullType($this->normalizeTypeRef($typeRef->getInnerRef())),
        };
    }
}
