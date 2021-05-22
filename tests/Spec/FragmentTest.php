<?php

declare(strict_types = 1);

namespace Graphpinator\Tests\Spec;

use Infinityloop\Utils\Json;

final class FragmentTest extends \PHPUnit\Framework\TestCase
{
    public function simpleDataProvider() : array
    {
        return [
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { fieldUnion { ... on Xyz { name } } }',
                ]),
                Json::fromNative((object) ['data' => ['fieldUnion' => new \stdClass()]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { fieldUnion { ... on Abc { fieldXyz { ... on Xyz { name } } } } }',
                ]),
                Json::fromNative((object) ['data' => ['fieldUnion' => ['fieldXyz' => ['name' => 'Test 123']]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { ... on Query { fieldUnion { ... on Abc { fieldXyz { ... on Xyz { name } } } } } }',
                ]),
                Json::fromNative((object) ['data' => ['fieldUnion' => ['fieldXyz' => ['name' => 'Test 123']]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { ... namedFragment } 
                    fragment namedFragment on Query { fieldUnion { ... on Abc { fieldXyz { name } } } }',
                ]),
                Json::fromNative((object) ['data' => ['fieldUnion' => ['fieldXyz' => ['name' => 'Test 123']]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { ... namedFragment } 
                    fragment namedFragment on Query { fieldUnion { ... on Abc { fieldXyz { ... on Xyz { name } } } } }',
                ]),
                Json::fromNative((object) ['data' => ['fieldUnion' => ['fieldXyz' => ['name' => 'Test 123']]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { 
                        ... namedFragment 
                    } 
                    fragment innerFragment on Xyz { 
                        name 
                    } 
                    fragment namedFragment on Query { 
                        fieldUnion { 
                            ... on Abc { 
                                fieldXyz { 
                                    ... innerFragment 
                                }
                            } 
                        } 
                    }',
                ]),
                Json::fromNative((object) ['data' => ['fieldUnion' => ['fieldXyz' => ['name' => 'Test 123']]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { 
                        fieldFragment {
                            ... interfaceAbcFragment
                        }
                    }
                    fragment interfaceAbcFragment on InterfaceAbc { 
                        name 
                        ... on InterfaceEfg { name number }
                        ... on FragmentTypeB { name number bool }
                    }',
                ]),
                Json::fromNative((object) ['data' => ['fieldFragment' => ['name' => 'defaultA']]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { 
                        fieldFragment {
                            ... interfaceAbcFragment
                        }
                    }
                    fragment interfaceAbcFragment on InterfaceAbc { 
                        name 
                        ... on InterfaceEfg { number }
                        ... on FragmentTypeB { bool }
                    }',
                ]),
                Json::fromNative((object) ['data' => ['fieldFragment' => ['name' => 'defaultA']]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { 
                        fieldFragment {
                            ... interfaceAbcFragment
                        }
                    }
                    fragment interfaceAbcFragment on InterfaceAbc { 
                        name 
                        ... @include(if: true) {
                            ... on InterfaceEfg { name number }
                            ... on FragmentTypeB { name number bool }
                        }
                    }',
                ]),
                Json::fromNative((object) ['data' => ['fieldFragment' => ['name' => 'defaultA']]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { 
                        fieldFragment {
                            ... interfaceAbcFragment
                        }
                    }
                    fragment interfaceAbcFragment on InterfaceAbc { 
                        name 
                        ... @include(if: true) {
                            ... on InterfaceEfg { number }
                            ... on FragmentTypeB { bool }
                        }
                    }',
                ]),
                Json::fromNative((object) ['data' => ['fieldFragment' => ['name' => 'defaultA']]]),
            ],
        ];
    }

    /**
     * @dataProvider simpleDataProvider
     * @param Json $request
     * @param Json $expected
     */
    public function testSimple(Json $request, Json $expected) : void
    {
        $graphpinator = new \Graphpinator\Graphpinator(TestSchema::getSchema());
        $result = $graphpinator->run(new \Graphpinator\Request\JsonRequestFactory($request));

        self::assertSame($expected->toString(), $result->toString());
    }

    public function fieldSelectionMergingDataProvider() : array
    {
        return [
            [
                Json::fromNative((object) [
                    'query' => 'query { fieldUnion { ... on Abc { fieldXyz { __typename ... on Xyz { __typename } } } } }',
                ]),
                Json::fromNative((object) ['data' => ['fieldUnion' => ['fieldXyz' => ['__typename' => 'Xyz']]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query { fieldUnion { ... on Abc { fieldXyz { __typename ... on Xyz { __typename name } } } } }',
                ]),
                Json::fromNative((object) ['data' => ['fieldUnion' => ['fieldXyz' => ['__typename' => 'Xyz', 'name' => 'Test 123']]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query { fieldUnion { ... on Abc { fieldXyz { ... on Xyz { __typename name } __typename } } } }',
                ]),
                Json::fromNative((object) ['data' => ['fieldUnion' => ['fieldXyz' => ['__typename' => 'Xyz', 'name' => 'Test 123']]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query { fieldUnion { ... on Abc { 
                        fieldXyz { __typename } ... on Abc { fieldXyz { ... on Xyz { __typename name } } } 
                     }}}',
                ]),
                Json::fromNative((object) ['data' => ['fieldUnion' => ['fieldXyz' => ['__typename' => 'Xyz', 'name' => 'Test 123']]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query { fieldUnion { ... on Abc { 
                        fieldXyz { name: __typename ... on Xyz { __typename } } 
                    }}}',
                ]),
                Json::fromNative((object) ['data' => ['fieldUnion' => ['fieldXyz' => ['name' => 'Xyz', '__typename' => 'Xyz']]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query { fieldUnion { 
                        ... on Abc { fieldXyz(arg1: 456) { name } }
                        ... on Abc { fieldXyz(arg1: 456) { name } } 
                    }}',
                ]),
                Json::fromNative((object) ['data' => ['fieldUnion' => ['fieldXyz' => ['name' => 'Test 456']]]]),
            ],
        ];
    }

    /**
     * @dataProvider fieldSelectionMergingDataProvider
     * @param Json $request
     * @param Json $expected
     */
    public function testFieldSelectionMerging(Json $request, Json $expected) : void
    {
        $graphpinator = new \Graphpinator\Graphpinator(TestSchema::getSchema(), true);
        $result = $graphpinator->run(new \Graphpinator\Request\JsonRequestFactory($request));

        self::assertSame($expected->toString(), $result->toString());
    }

    public function invalidDataProvider() : array
    {
        return [
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { ...namedFragment }',
                ]),
                \Graphpinator\Normalizer\Exception\UnknownFragment::class,
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { ...namedFragment } 
                    fragment namedFragment on Query { 
                        ...cycleFragment 
                        fieldUnion
                    } 
                    fragment cycleFragment on Query { 
                        ...namedFragment 
                    }',
                ]),
                \Graphpinator\Normalizer\Exception\FragmentCycle::class,
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { fieldUnion { ... on Abc { 
                    fieldXyz { name: __typename ... on Xyz { name } } 
                    } } }',
                ]),
                \Graphpinator\Normalizer\Exception\ConflictingFieldAlias::class,
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { fieldUnion { ... on Abc { 
                    fieldXyz { typename: __typename ... on Xyz { typename: name } } 
                    } } }',
                ]),
                \Graphpinator\Normalizer\Exception\ConflictingFieldAlias::class,
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { fieldUnion { ... on Abc { 
                    fieldXyz(arg1: 456) { name } ... on Abc { fieldXyz(arg1: 123) { name } } 
                    } } }',
                ]),
                \Graphpinator\Normalizer\Exception\ConflictingFieldArguments::class,
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { fieldUnion { ... on Abc { 
                    fieldXyz(arg1: 456) { name } ... on Abc { fieldXyz { name } } 
                    } } }',
                ]),
                \Graphpinator\Normalizer\Exception\ConflictingFieldArguments::class,
            ],
        ];
    }

    /**
     * @dataProvider invalidDataProvider
     * @param Json $request
     * @param string $exception
     */
    public function testInvalid(Json $request, string $exception) : void
    {
        $this->expectException($exception);

        $graphpinator = new \Graphpinator\Graphpinator(TestSchema::getSchema());
        $graphpinator->run(new \Graphpinator\Request\JsonRequestFactory($request));
    }
}
