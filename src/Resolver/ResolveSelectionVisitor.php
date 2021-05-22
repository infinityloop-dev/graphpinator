<?php

declare(strict_types = 1);

namespace Graphpinator\Resolver;

final class ResolveSelectionVisitor implements \Graphpinator\Normalizer\Selection\SelectionVisitor
{
    use \Nette\SmartObject;

    public function __construct(
        private \Graphpinator\Value\ResolvedValue $parentResult,
    ) {}

    public function visitField(\Graphpinator\Normalizer\Selection\Field $field) : array
    {
        $type = $this->parentResult->getType();
        \assert($type instanceof \Graphpinator\Type\Type);

        foreach ($field->getDirectives() as $directive) {
            $directiveResult = $directive->getDirective()->resolveFieldBefore($directive->getArguments());

            if (self::shouldSkip($directiveResult)) {
                return [];
            }
        }

        $fieldDef = $type->getMetaFields()[$field->getName()]
            ?? $type->getFields()[$field->getName()];

        foreach ($fieldDef->getDirectiveUsages() as $directive) {
            $directive->getDirective()->resolveFieldDefinitionStart($directive->getArgumentValues(), $this->parentResult);
        }

        $arguments = $field->getArguments();

        foreach ($arguments as $argumentValue) {
            $argumentValue->resolveNonPureDirectives();
        }

        foreach ($fieldDef->getDirectiveUsages() as $directive) {
            $directive->getDirective()->resolveFieldDefinitionBefore($directive->getArgumentValues(), $this->parentResult, $arguments);
        }

        $rawArguments = $arguments->getValuesForResolver();
        \array_unshift($rawArguments, $this->parentResult->getRawValue());
        $rawValue = \call_user_func_array($fieldDef->getResolveFunction(), $rawArguments);
        $resolvedValue = $fieldDef->getType()->accept(new CreateResolvedValueVisitor($rawValue));

        if (!$resolvedValue->getType()->isInstanceOf($fieldDef->getType())) {
            throw new \Graphpinator\Resolver\Exception\FieldResultTypeMismatch();
        }

        foreach ($fieldDef->getDirectiveUsages() as $directive) {
            $directive->getDirective()->resolveFieldDefinitionAfter($directive->getArgumentValues(), $resolvedValue, $arguments);
        }

        $fieldValue = new \Graphpinator\Value\FieldValue($fieldDef, $resolvedValue instanceof \Graphpinator\Value\NullValue
            ? $resolvedValue
            : $resolvedValue->getType()->accept(new ResolveVisitor($field->getSelections(), $resolvedValue))
        );

        foreach ($field->getDirectives() as $directive) {
            $directiveResult = $directive->getDirective()->resolveFieldAfter($directive->getArguments(), $fieldValue);

            if (self::shouldSkip($directiveResult)) {
                return [];
            }
        }

        return [
            $field->getOutputName() => $fieldValue,
        ];
    }

    public function visitFragmentSpread(\Graphpinator\Normalizer\Selection\FragmentSpread $fragmentSpread) : array
    {
        if (!$this->parentResult->getType()->isInstanceOf($fragmentSpread->getTypeCondition())) {
            return [];
        }

        foreach ($fragmentSpread->getDirectives() as $directive) {
            $directiveResult = $directive->getDirective()->resolveFragmentSpreadBefore($directive->getArguments());

            if (self::shouldSkip($directiveResult)) {
                return [];
            }
        }

        $return = [];

        foreach ($fragmentSpread->getSelections() as $selection) {
            $return += $selection->accept(new ResolveSelectionVisitor($this->parentResult));
        }

        foreach ($fragmentSpread->getDirectives() as $directive) {
            $directiveResult = $directive->getDirective()->resolveFragmentSpreadAfter($directive->getArguments());

            if (self::shouldSkip($directiveResult)) {
                return [];
            }
        }

        return $return;
    }

    public function visitInlineFragment(\Graphpinator\Normalizer\Selection\InlineFragment $inlineFragment) : array
    {
        if ($inlineFragment->getTypeCondition() instanceof \Graphpinator\Type\Contract\NamedDefinition &&
            !$this->parentResult->getType()->isInstanceOf($inlineFragment->getTypeCondition())) {
            return [];
        }

        foreach ($inlineFragment->getDirectives() as $directive) {
            $directiveResult = $directive->getDirective()->resolveInlineFragmentBefore($directive->getArguments());

            if (self::shouldSkip($directiveResult)) {
                return [];
            }
        }

        $return = [];

        foreach ($inlineFragment->getSelections() as $selection) {
            $return += $selection->accept(new ResolveSelectionVisitor($this->parentResult));
        }

        foreach ($inlineFragment->getDirectives() as $directive) {
            $directiveResult = $directive->getDirective()->resolveInlineFragmentAfter($directive->getArguments());

            if (self::shouldSkip($directiveResult)) {
                return [];
            }
        }

        return $return;
    }

    private static function shouldSkip(string $directiveResult) : bool
    {
        if (\array_key_exists($directiveResult, \Graphpinator\Directive\FieldDirectiveResult::ENUM)) {
            return $directiveResult === \Graphpinator\Directive\FieldDirectiveResult::SKIP;
        }

        throw new \Graphpinator\Resolver\Exception\InvalidDirectiveResult();
    }
}
