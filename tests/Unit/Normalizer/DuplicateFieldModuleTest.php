<?php

declare(strict_types = 1);

namespace Graphpinator\Tests\Unit\Normalizer;

final class DuplicateFieldModuleTest extends \PHPUnit\Framework\TestCase
{
    public function testSingleField() : void
    {
        $field = new \Graphpinator\Field\Field('fieldName', \Graphpinator\Container\Container::String());

        $refiner = new \Graphpinator\Normalizer\SelectionSetRefiner(new \Graphpinator\Normalizer\Selection\SelectionSet([
            new \Graphpinator\Normalizer\Selection\Field(
                $field,
                'someName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                null,
            ),
            new \Graphpinator\Normalizer\Selection\Field(
                $field,
                'someOtherName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                null,
            ),
        ]));

        $result = $refiner->refine();

        self::assertCount(2, $result);
        self::assertInstanceOf(\Graphpinator\Normalizer\Selection\Field::class, $result->offsetGet(0));
        self::assertInstanceOf(\Graphpinator\Normalizer\Selection\Field::class, $result->offsetGet(1));
    }

    public function testDuplicateField() : void
    {
        $field = new \Graphpinator\Field\Field('fieldName', \Graphpinator\Container\Container::String());

        $refiner = new \Graphpinator\Normalizer\SelectionSetRefiner(new \Graphpinator\Normalizer\Selection\SelectionSet([
            new \Graphpinator\Normalizer\Selection\Field(
                $field,
                'someName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                null,
            ),
            new \Graphpinator\Normalizer\Selection\Field(
                $field,
                'someName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                null,
            ),
        ]));

        $result = $refiner->refine();

        self::assertCount(1, $result);
        self::assertInstanceOf(\Graphpinator\Normalizer\Selection\Field::class, $result->offsetGet(0));
    }

    public function testInnerField() : void
    {
        $field = new \Graphpinator\Field\Field('fieldName', \Graphpinator\Container\Container::String());

        $refiner = new \Graphpinator\Normalizer\SelectionSetRefiner(new \Graphpinator\Normalizer\Selection\SelectionSet([
            new \Graphpinator\Normalizer\Selection\Field(
                $field,
                'fieldName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                new \Graphpinator\Normalizer\Selection\SelectionSet([
                    new \Graphpinator\Normalizer\Selection\Field(
                        $field,
                        'field1',
                        new \Graphpinator\Value\ArgumentValueSet(),
                        new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                        new \Graphpinator\Normalizer\Selection\SelectionSet([

                        ]),
                    ),
                ]),
            ),
            new \Graphpinator\Normalizer\Selection\Field(
                $field,
                'fieldName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                new \Graphpinator\Normalizer\Selection\SelectionSet([
                    new \Graphpinator\Normalizer\Selection\Field(
                        $field,
                        'field2',
                        new \Graphpinator\Value\ArgumentValueSet(),
                        new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                        new \Graphpinator\Normalizer\Selection\SelectionSet([

                        ]),
                    ),
                ]),
            ),
        ]));

        $result = $refiner->refine();

        self::assertCount(1, $result);
        self::assertInstanceOf(\Graphpinator\Normalizer\Selection\Field::class, $result->offsetGet(0));
        self::assertCount(2, $result->offsetGet(0)->getSelections());
    }

    public function testConflictingType() : void
    {
        $this->expectException(\Graphpinator\Normalizer\Exception\ConflictingFieldType::class);

        $field1 = new \Graphpinator\Field\Field('field1', \Graphpinator\Container\Container::String());
        $field2 = new \Graphpinator\Field\Field('field2', \Graphpinator\Container\Container::Int());

        $refiner = new \Graphpinator\Normalizer\SelectionSetRefiner(new \Graphpinator\Normalizer\Selection\SelectionSet([
            new \Graphpinator\Normalizer\Selection\Field(
                $field1,
                'someName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                null,
            ),
            new \Graphpinator\Normalizer\Selection\Field(
                $field2,
                'someName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                null,
            ),
        ]));

        $refiner->refine();
    }

    public function testConflictingAlias() : void
    {
        $this->expectException(\Graphpinator\Normalizer\Exception\ConflictingFieldAlias::class);

        $field1 = new \Graphpinator\Field\Field('field1', \Graphpinator\Container\Container::String());
        $field2 = new \Graphpinator\Field\Field('field2', \Graphpinator\Container\Container::String());

        $refiner = new \Graphpinator\Normalizer\SelectionSetRefiner(new \Graphpinator\Normalizer\Selection\SelectionSet([
            new \Graphpinator\Normalizer\Selection\Field(
                $field1,
                'someName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                null,
            ),
            new \Graphpinator\Normalizer\Selection\Field(
                $field2,
                'someName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                null,
            ),
        ]));

        $refiner->refine();
    }

    public function testConflictingArguments() : void
    {
        $this->expectException(\Graphpinator\Normalizer\Exception\ConflictingFieldArguments::class);

        $field = new \Graphpinator\Field\Field('field1', \Graphpinator\Container\Container::String());
        $argument = new \Graphpinator\Argument\Argument('argument', \Graphpinator\Container\Container::String());

        $refiner = new \Graphpinator\Normalizer\SelectionSetRefiner(new \Graphpinator\Normalizer\Selection\SelectionSet([
            new \Graphpinator\Normalizer\Selection\Field(
                $field,
                'someName',
                new \Graphpinator\Value\ArgumentValueSet([
                    new \Graphpinator\Value\ArgumentValue(
                        $argument,
                        new \Graphpinator\Value\ScalarValue(\Graphpinator\Container\Container::String(), '123', true),
                        true,
                    ),
                ]),
                new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                null,
            ),
            new \Graphpinator\Normalizer\Selection\Field(
                $field,
                'someName',
                new \Graphpinator\Value\ArgumentValueSet([
                    new \Graphpinator\Value\ArgumentValue(
                        $argument,
                        new \Graphpinator\Value\ScalarValue(\Graphpinator\Container\Container::String(), '456', true),
                        true,
                    ),
                ]),
                new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                null,
            ),
        ]));

        $refiner->refine();
    }

    public function testConflictingDirectives() : void
    {
        $this->expectException(\Graphpinator\Normalizer\Exception\ConflictingFieldDirectives::class);

        $field = new \Graphpinator\Field\Field('field1', \Graphpinator\Container\Container::String());

        $refiner = new \Graphpinator\Normalizer\SelectionSetRefiner(new \Graphpinator\Normalizer\Selection\SelectionSet([
            new \Graphpinator\Normalizer\Selection\Field(
                $field,
                'someName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet([
                    new \Graphpinator\Normalizer\Directive\Directive(
                        \Graphpinator\Container\Container::directiveSkip(),
                        new \Graphpinator\Value\ArgumentValueSet([
                            new \Graphpinator\Value\ArgumentValue(
                                \Graphpinator\Container\Container::directiveSkip()->getArguments()['if'],
                                new \Graphpinator\Value\ScalarValue(\Graphpinator\Container\Container::Boolean(), false, true),
                                true,
                            ),
                        ])
                    ),
                ]),
                null,
            ),
            new \Graphpinator\Normalizer\Selection\Field(
                $field,
                'someName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet([
                    new \Graphpinator\Normalizer\Directive\Directive(
                        \Graphpinator\Container\Container::directiveInclude(),
                        new \Graphpinator\Value\ArgumentValueSet([
                            new \Graphpinator\Value\ArgumentValue(
                                \Graphpinator\Container\Container::directiveInclude()->getArguments()['if'],
                                new \Graphpinator\Value\ScalarValue(\Graphpinator\Container\Container::Boolean(), true, true),
                                true,
                            ),
                        ])
                    ),
                ]),
                null,
            ),
        ]));

        $refiner->refine();
    }

    public function testConflictingDirectiveArguments() : void
    {
        $this->expectException(\Graphpinator\Normalizer\Exception\ConflictingFieldDirectives::class);

        $field = new \Graphpinator\Field\Field('field1', \Graphpinator\Container\Container::String());

        $refiner = new \Graphpinator\Normalizer\SelectionSetRefiner(new \Graphpinator\Normalizer\Selection\SelectionSet([
            new \Graphpinator\Normalizer\Selection\Field(
                $field,
                'someName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet([
                    new \Graphpinator\Normalizer\Directive\Directive(
                        \Graphpinator\Container\Container::directiveSkip(),
                        new \Graphpinator\Value\ArgumentValueSet([
                            new \Graphpinator\Value\ArgumentValue(
                                \Graphpinator\Container\Container::directiveSkip()->getArguments()['if'],
                                new \Graphpinator\Value\ScalarValue(\Graphpinator\Container\Container::Boolean(), false, true),
                                true,
                            ),
                        ])
                    ),
                ]),
                null,
            ),
            new \Graphpinator\Normalizer\Selection\Field(
                $field,
                'someName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet([
                    new \Graphpinator\Normalizer\Directive\Directive(
                        \Graphpinator\Container\Container::directiveSkip(),
                        new \Graphpinator\Value\ArgumentValueSet([
                            new \Graphpinator\Value\ArgumentValue(
                                \Graphpinator\Container\Container::directiveSkip()->getArguments()['if'],
                                new \Graphpinator\Value\ScalarValue(\Graphpinator\Container\Container::Boolean(), true, true),
                                true,
                            ),
                        ])
                    ),
                ]),
                null,
            ),
        ]));

        $refiner->refine();
    }

    public function testDuplicateFieldInFragment() : void
    {
        $field = new \Graphpinator\Field\Field('field1', \Graphpinator\Container\Container::String());
        $fragmentSpread = new \Graphpinator\Normalizer\Selection\FragmentSpread(
            'someName',
            new \Graphpinator\Normalizer\Selection\SelectionSet([
                new \Graphpinator\Normalizer\Selection\Field(
                    $field,
                    'someName',
                    new \Graphpinator\Value\ArgumentValueSet(),
                    new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                ),
            ]),
            new \Graphpinator\Normalizer\Directive\DirectiveSet(),
            new class extends \Graphpinator\Type\Type {
                public function validateNonNullValue(mixed $rawValue): bool {}
                protected function getFieldDefinition(): \Graphpinator\Field\ResolvableFieldSet {}
            },
        );

        $refiner = new \Graphpinator\Normalizer\SelectionSetRefiner(new \Graphpinator\Normalizer\Selection\SelectionSet([
            new \Graphpinator\Normalizer\Selection\Field(
                $field,
                'someName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet(),
            ),
            $fragmentSpread,
        ]));

        $result = $refiner->refine();

        self::assertCount(1, $result);
        self::assertInstanceOf(\Graphpinator\Normalizer\Selection\Field::class, $result->offsetGet(0));
    }

    public function testDuplicateFieldInFragmentWithSelection() : void
    {
        $field = new \Graphpinator\Field\Field('field1', \Graphpinator\Container\Container::String());
        $fragmentSpread = new \Graphpinator\Normalizer\Selection\FragmentSpread(
            'fragmentName',
            new \Graphpinator\Normalizer\Selection\SelectionSet([
                new \Graphpinator\Normalizer\Selection\Field(
                    $field,
                    'someName',
                    new \Graphpinator\Value\ArgumentValueSet(),
                    new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                    new \Graphpinator\Normalizer\Selection\SelectionSet([
                        new \Graphpinator\Normalizer\Selection\Field(
                            $field,
                            'someOtherName',
                            new \Graphpinator\Value\ArgumentValueSet(),
                            new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                        ),
                    ]),
                ),
            ]),
            new \Graphpinator\Normalizer\Directive\DirectiveSet(),
            new class extends \Graphpinator\Type\Type {
                public function validateNonNullValue(mixed $rawValue): bool {}
                protected function getFieldDefinition(): \Graphpinator\Field\ResolvableFieldSet {}
            },
        );
        $selections = new \Graphpinator\Normalizer\Selection\SelectionSet([
            new \Graphpinator\Normalizer\Selection\Field(
                $field,
                'someName',
                new \Graphpinator\Value\ArgumentValueSet(),
                new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                new \Graphpinator\Normalizer\Selection\SelectionSet([
                    new \Graphpinator\Normalizer\Selection\Field(
                        $field,
                        'someName',
                        new \Graphpinator\Value\ArgumentValueSet(),
                        new \Graphpinator\Normalizer\Directive\DirectiveSet(),
                    ),
                ]),
            ),
            $fragmentSpread,
        ]);

        self::assertCount(2, $selections);
        self::assertInstanceOf(\Graphpinator\Normalizer\Selection\Field::class, $selections->offsetGet(0));
        self::assertInstanceOf(\Graphpinator\Normalizer\Selection\FragmentSpread::class, $selections->offsetGet(1));
        self::assertCount(1, $selections->offsetGet(0)->getSelections());
        self::assertInstanceOf(\Graphpinator\Normalizer\Selection\Field::class, $selections->offsetGet(0)->getSelections()->offsetGet(0));

        $refiner = new \Graphpinator\Normalizer\SelectionSetRefiner($selections);
        $result = $refiner->refine();

        self::assertCount(1, $result);
        self::assertInstanceOf(\Graphpinator\Normalizer\Selection\Field::class, $result->offsetGet(0));
        self::assertCount(2, $result->offsetGet(0)->getSelections());
        self::assertInstanceOf(\Graphpinator\Normalizer\Selection\Field::class, $result->offsetGet(0)->getSelections()->offsetGet(0));
        self::assertInstanceOf(\Graphpinator\Normalizer\Selection\InlineFragment::class, $result->offsetGet(0)->getSelections()->offsetGet(1));
    }
}
