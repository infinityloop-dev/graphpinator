<?php

declare(strict_types = 1);

namespace Graphpinator\Tests\Unit\Normalizer;

final class NormalizerTest extends \PHPUnit\Framework\TestCase
{
    public function testVariableTypeReferences() : void
    {
        $parseResult = new \Graphpinator\Parser\ParsedRequest(
            new \Graphpinator\Parser\Operation\OperationSet([
                new \Graphpinator\Parser\Operation\Operation(
                    \Graphpinator\Tokenizer\OperationType::QUERY,
                    'operationName',
                    new \Graphpinator\Parser\Variable\VariableSet([
                        new \Graphpinator\Parser\Variable\Variable(
                            'varName',
                            new \Graphpinator\Parser\TypeRef\NotNullRef(new \Graphpinator\Parser\TypeRef\NamedTypeRef('String')),
                            null,
                            new \Graphpinator\Parser\Directive\DirectiveSet(),
                        ),
                        new \Graphpinator\Parser\Variable\Variable(
                            'varNameList',
                            new \Graphpinator\Parser\TypeRef\ListTypeRef(new \Graphpinator\Parser\TypeRef\NamedTypeRef('String')),
                            null,
                            new \Graphpinator\Parser\Directive\DirectiveSet(),
                        ),
                    ]),
                    new \Graphpinator\Parser\Directive\DirectiveSet(),
                    new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet()),
                ),
            ]),
            new \Graphpinator\Parser\Fragment\FragmentSet(),
        );

        $normalizer = new \Graphpinator\Normalizer\Normalizer(\Graphpinator\Tests\Spec\TestSchema::getSchema());
        $operation = $normalizer->normalize($parseResult)->getOperations()->current();

        self::assertCount(0, $operation->getSelections());
        self::assertCount(2, $operation->getVariables());
        self::assertArrayHasKey('varName', $operation->getVariables());
        self::assertSame('varName', $operation->getVariables()->offsetGet('varName')->getName());
        self::assertNull($operation->getVariables()->offsetGet('varName')->getDefaultValue());
        self::assertInstanceOf(\Graphpinator\Type\NotNullType::class, $operation->getVariables()->offsetGet('varName')->getType());
        self::assertSame('String', $operation->getVariables()->offsetGet('varName')->getType()->getNamedType()->getName());
        self::assertArrayHasKey('varNameList', $operation->getVariables());
        self::assertSame('varNameList', $operation->getVariables()->offsetGet('varNameList')->getName());
        self::assertNull($operation->getVariables()->offsetGet('varNameList')->getDefaultValue());
        self::assertInstanceOf(\Graphpinator\Type\ListType::class, $operation->getVariables()->offsetGet('varNameList')->getType());
        self::assertSame('String', $operation->getVariables()->offsetGet('varNameList')->getType()->getNamedType()->getName());
    }

    public function testDirectiveReferences() : void
    {
        $parseResult = new \Graphpinator\Parser\ParsedRequest(
            new \Graphpinator\Parser\Operation\OperationSet([
                new \Graphpinator\Parser\Operation\Operation(
                    \Graphpinator\Tokenizer\OperationType::QUERY,
                    'operationName',
                    null,
                    null,
                    new \Graphpinator\Parser\Field\FieldSet([
                        new \Graphpinator\Parser\Field\Field(
                            'fieldAbc',
                            null,
                            new \Graphpinator\Parser\Field\FieldSet([
                                new \Graphpinator\Parser\Field\Field('fieldXyz', null, new \Graphpinator\Parser\Field\FieldSet([
                                    new \Graphpinator\Parser\Field\Field('name'),
                                ], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet())),
                            ], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet()),
                            null,
                            new \Graphpinator\Parser\Directive\DirectiveSet([
                                new \Graphpinator\Parser\Directive\Directive(
                                    'skip',
                                    new \Graphpinator\Parser\Value\ArgumentValueSet([
                                        new \Graphpinator\Parser\Value\ArgumentValue(new \Graphpinator\Parser\Value\Literal(true), 'if'),
                                    ]),
                                ),
                            ]),
                        ),
                    ], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet([
                        new \Graphpinator\Parser\FragmentSpread\InlineFragmentSpread(
                            new \Graphpinator\Parser\Field\FieldSet([
                                new \Graphpinator\Parser\Field\Field('fieldListInt'),
                            ], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet()),
                            new \Graphpinator\Parser\Directive\DirectiveSet([
                                new \Graphpinator\Parser\Directive\Directive(
                                    'skip',
                                    new \Graphpinator\Parser\Value\ArgumentValueSet([
                                        new \Graphpinator\Parser\Value\ArgumentValue(new \Graphpinator\Parser\Value\Literal(true), 'if'),
                                    ]),
                                ),
                            ]),
                        ),
                        new \Graphpinator\Parser\FragmentSpread\NamedFragmentSpread(
                            'fragmentName',
                            new \Graphpinator\Parser\Directive\DirectiveSet([
                                new \Graphpinator\Parser\Directive\Directive(
                                    'include',
                                    new \Graphpinator\Parser\Value\ArgumentValueSet([
                                        new \Graphpinator\Parser\Value\ArgumentValue(new \Graphpinator\Parser\Value\Literal(true), 'if'),
                                    ]),
                                ),
                            ]),
                        ),
                    ])),
                ),
            ]),
            new \Graphpinator\Parser\Fragment\FragmentSet([
                new \Graphpinator\Parser\Fragment\Fragment(
                    'fragmentName',
                    new \Graphpinator\Parser\TypeRef\NamedTypeRef('Query'),
                    new \Graphpinator\Parser\Directive\DirectiveSet(),
                    new \Graphpinator\Parser\Field\FieldSet([
                        new \Graphpinator\Parser\Field\Field('fieldList'),
                    ], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet()),
                ),
            ]),
        );

        $normalizer = new \Graphpinator\Normalizer\Normalizer(\Graphpinator\Tests\Spec\TestSchema::getSchema());
        $operation = $normalizer->normalize($parseResult)->getOperations()->current();

        self::assertCount(0, $operation->getVariables());
        self::assertCount(3, $operation->getSelections());

        self::assertArrayHasKey(0, $operation->getSelections());
        self::assertInstanceOf(\Graphpinator\Normalizer\Selection\Field::class, $operation->getSelections()->offsetGet(0));
        self::assertSame('fieldAbc', $operation->getSelections()->offsetGet(0)->getName());
        self::assertCount(1, $operation->getSelections()->offsetGet(0)->getDirectives());
        self::assertArrayHasKey(0, $operation->getSelections()->offsetGet(0)->getDirectives());
        self::assertInstanceOf(
            \Graphpinator\Directive\Spec\SkipDirective::class,
            $operation->getSelections()->offsetGet(0)->getDirectives()->offsetGet(0)->getDirective(),
        );

        self::assertArrayHasKey(1, $operation->getSelections());
        self::assertInstanceOf(\Graphpinator\Normalizer\Selection\InlineFragment::class, $operation->getSelections()->offsetGet(1));
        self::assertSame('fieldListInt', $operation->getSelections()->offsetGet(1)->getSelections()->offsetGet(0)->getName());
        self::assertCount(1, $operation->getSelections()->offsetGet(1)->getDirectives());
        self::assertArrayHasKey(0, $operation->getSelections()->offsetGet(1)->getDirectives());
        self::assertInstanceOf(
            \Graphpinator\Directive\Spec\SkipDirective::class,
            $operation->getSelections()->offsetGet(1)->getDirectives()->offsetGet(0)->getDirective(),
        );

        self::assertArrayHasKey(2, $operation->getSelections());
        self::assertInstanceOf(\Graphpinator\Normalizer\Selection\FragmentSpread::class, $operation->getSelections()->offsetGet(2));
        self::assertSame('fieldList', $operation->getSelections()->offsetGet(2)->getSelections()->offsetGet(0)->getName());
        self::assertCount(1, $operation->getSelections()->offsetGet(2)->getDirectives());
        self::assertArrayHasKey(0, $operation->getSelections()->offsetGet(2)->getDirectives());
        self::assertInstanceOf(
            \Graphpinator\Directive\Spec\IncludeDirective::class,
            $operation->getSelections()->offsetGet(2)->getDirectives()->offsetGet(0)->getDirective(),
        );
    }

    public function invalidDataProvider() : array
    {
        return [
            [
                new \Graphpinator\Parser\ParsedRequest(
                    new \Graphpinator\Parser\Operation\OperationSet([
                        new \Graphpinator\Parser\Operation\Operation(
                            \Graphpinator\Tokenizer\OperationType::MUTATION,
                            null,
                            null,
                            null,
                            new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet()),
                        ),
                    ]),
                    new \Graphpinator\Parser\Fragment\FragmentSet(),
                ),
                \Graphpinator\Normalizer\Exception\OperationNotSupported::class,
            ],
            [
                new \Graphpinator\Parser\ParsedRequest(
                    new \Graphpinator\Parser\Operation\OperationSet([
                        new \Graphpinator\Parser\Operation\Operation(
                            \Graphpinator\Tokenizer\OperationType::SUBSCRIPTION,
                            null,
                            null,
                            null,
                            new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet()),
                        ),
                    ]),
                    new \Graphpinator\Parser\Fragment\FragmentSet(),
                ),
                \Graphpinator\Normalizer\Exception\OperationNotSupported::class,
            ],
            [
                new \Graphpinator\Parser\ParsedRequest(
                    new \Graphpinator\Parser\Operation\OperationSet([
                        new \Graphpinator\Parser\Operation\Operation(
                            \Graphpinator\Tokenizer\OperationType::QUERY,
                            null,
                            null,
                            null,
                            new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet([
                                new \Graphpinator\Parser\FragmentSpread\NamedFragmentSpread('fragmentName'),
                            ])),
                        ),
                    ]),
                    new \Graphpinator\Parser\Fragment\FragmentSet(),
                ),
                \Graphpinator\Normalizer\Exception\UnknownFragment::class,
            ],
            [
                new \Graphpinator\Parser\ParsedRequest(
                    new \Graphpinator\Parser\Operation\OperationSet([
                        new \Graphpinator\Parser\Operation\Operation(
                            \Graphpinator\Tokenizer\OperationType::QUERY,
                            null,
                            null,
                            null,
                            new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet()),
                        ),
                    ]),
                    new \Graphpinator\Parser\Fragment\FragmentSet([
                        new \Graphpinator\Parser\Fragment\Fragment(
                            'fragment1',
                            new \Graphpinator\Parser\TypeRef\NamedTypeRef('Int'),
                            new \Graphpinator\Parser\Directive\DirectiveSet(),
                            new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet([
                                new \Graphpinator\Parser\FragmentSpread\NamedFragmentSpread('fragment5'),
                                new \Graphpinator\Parser\FragmentSpread\NamedFragmentSpread('fragment2'),
                            ])),
                        ),
                        new \Graphpinator\Parser\Fragment\Fragment(
                            'fragment2',
                            new \Graphpinator\Parser\TypeRef\NamedTypeRef('Int'),
                            new \Graphpinator\Parser\Directive\DirectiveSet(),
                            new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet([
                                new \Graphpinator\Parser\FragmentSpread\NamedFragmentSpread('fragment3'),
                            ])),
                        ),
                        new \Graphpinator\Parser\Fragment\Fragment(
                            'fragment3',
                            new \Graphpinator\Parser\TypeRef\NamedTypeRef('Int'),
                            new \Graphpinator\Parser\Directive\DirectiveSet(),
                            new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet([
                                new \Graphpinator\Parser\FragmentSpread\NamedFragmentSpread('fragment4'),
                            ])),
                        ),
                        new \Graphpinator\Parser\Fragment\Fragment(
                            'fragment4',
                            new \Graphpinator\Parser\TypeRef\NamedTypeRef('Int'),
                            new \Graphpinator\Parser\Directive\DirectiveSet(),
                            new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet([
                                new \Graphpinator\Parser\FragmentSpread\NamedFragmentSpread('fragment5'),
                                new \Graphpinator\Parser\FragmentSpread\NamedFragmentSpread('fragment1'),
                            ])),
                        ),
                        new \Graphpinator\Parser\Fragment\Fragment(
                            'fragment5',
                            new \Graphpinator\Parser\TypeRef\NamedTypeRef('Int'),
                            new \Graphpinator\Parser\Directive\DirectiveSet(),
                            new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet()),
                        ),
                    ]),
                ),
                \Graphpinator\Normalizer\Exception\FragmentCycle::class,
            ],
            [
                new \Graphpinator\Parser\ParsedRequest(
                    new \Graphpinator\Parser\Operation\OperationSet([
                        new \Graphpinator\Parser\Operation\Operation(
                            \Graphpinator\Tokenizer\OperationType::QUERY,
                            null,
                            null,
                            null,
                            new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet()),
                        ),
                    ]),
                    new \Graphpinator\Parser\Fragment\FragmentSet([
                        new \Graphpinator\Parser\Fragment\Fragment(
                            'fragment1',
                            new \Graphpinator\Parser\TypeRef\NamedTypeRef('Int'),
                            new \Graphpinator\Parser\Directive\DirectiveSet(),
                            new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet([
                                new \Graphpinator\Parser\FragmentSpread\InlineFragmentSpread(new \Graphpinator\Parser\Field\FieldSet(
                                    [],
                                    new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet([]),
                                )),
                                new \Graphpinator\Parser\FragmentSpread\NamedFragmentSpread('fragment2'),
                            ])),
                        ),
                        new \Graphpinator\Parser\Fragment\Fragment(
                            'fragment2',
                            new \Graphpinator\Parser\TypeRef\NamedTypeRef('Int'),
                            new \Graphpinator\Parser\Directive\DirectiveSet(),
                            new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet([
                                new \Graphpinator\Parser\FragmentSpread\InlineFragmentSpread(new \Graphpinator\Parser\Field\FieldSet(
                                    [],
                                    new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet(),
                                )),
                                new \Graphpinator\Parser\FragmentSpread\NamedFragmentSpread('fragment1'),
                            ])),
                        ),
                    ]),
                ),
                \Graphpinator\Normalizer\Exception\FragmentCycle::class,
            ],
            [
                new \Graphpinator\Parser\ParsedRequest(
                    new \Graphpinator\Parser\Operation\OperationSet([
                        new \Graphpinator\Parser\Operation\Operation(
                            \Graphpinator\Tokenizer\OperationType::QUERY,
                            null,
                            null,
                            null,
                            new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet()),
                        ),
                    ]),
                    new \Graphpinator\Parser\Fragment\FragmentSet([
                        new \Graphpinator\Parser\Fragment\Fragment(
                            'fragment1',
                            new \Graphpinator\Parser\TypeRef\NamedTypeRef('Int'),
                            new \Graphpinator\Parser\Directive\DirectiveSet(),
                            new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet([
                                new \Graphpinator\Parser\FragmentSpread\NamedFragmentSpread('fragment2'),
                            ])),
                        ),
                        new \Graphpinator\Parser\Fragment\Fragment(
                            'fragment2',
                            new \Graphpinator\Parser\TypeRef\NamedTypeRef('Int'),
                            new \Graphpinator\Parser\Directive\DirectiveSet(),
                            new \Graphpinator\Parser\Field\FieldSet([
                                new \Graphpinator\Parser\Field\Field(
                                    'field',
                                    null,
                                    new \Graphpinator\Parser\Field\FieldSet([], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet([
                                        new \Graphpinator\Parser\FragmentSpread\NamedFragmentSpread('fragment1'),
                                    ])),
                                ),
                            ], new \Graphpinator\Parser\FragmentSpread\FragmentSpreadSet()),
                        ),
                    ]),
                ),
                \Graphpinator\Normalizer\Exception\FragmentCycle::class,
            ],
        ];
    }

    /**
     * @dataProvider invalidDataProvider
     * @param \Graphpinator\Parser\ParsedRequest $parseResult
     * @param string $exception
     */
    public function testInvalid(\Graphpinator\Parser\ParsedRequest $parseResult, string $exception) : void
    {
        $this->expectException($exception);

        $normalizer = new \Graphpinator\Normalizer\Normalizer(\Graphpinator\Tests\Spec\TestSchema::getSchema());
        $normalizer->normalize($parseResult)->getOperations()->current();
    }
}
