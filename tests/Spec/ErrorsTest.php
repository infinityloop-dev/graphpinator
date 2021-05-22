<?php

declare(strict_types = 1);

namespace Graphpinator\Tests\Spec;

use Infinityloop\Utils\Json;

final class ErrorsTest extends \PHPUnit\Framework\TestCase
{
    public function tokenizerDataProvider() : array
    {
        return [
            [
                Json::fromNative((object) [
                    'query' => 'query queryName ($ var1: Int) { }',
                ]),
                Json::fromNative((object) [
                    'errors' => [
                        [
                            'message' => \Graphpinator\Exception\Tokenizer\MissingVariableName::MESSAGE,
                            'locations' => [['line' => 1, 'column' => 18]],
                        ],
                    ],
                ]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { .. fragment }',
                ]),
                Json::fromNative((object) [
                    'errors' => [
                        [
                            'message' => \Graphpinator\Exception\Tokenizer\InvalidEllipsis::MESSAGE,
                            'locations' => [['line' => 1, 'column' => 19]],
                        ],
                    ],
                ]),
            ],
        ];
    }

    public function parserDataProvider() : array
    {
        return [
            [
                Json::fromNative((object) [
                    'query' => '   ',
                ]),
                Json::fromNative((object) [
                    'errors' => [
                        [
                            'message' => \Graphpinator\Parser\Exception\EmptyRequest::MESSAGE,
                            'locations' => [['line' => 1, 'column' => 1]],
                        ],
                    ],
                ]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'fragment Abc on Abc { field }',
                ]),
                Json::fromNative((object) [
                    'errors' => [
                        [
                            'message' => \Graphpinator\Parser\Exception\MissingOperation::MESSAGE,
                            'locations' => [['line' => 1, 'column' => 29]],
                        ],
                    ],
                ]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { fieldUnion { ',
                ]),
                Json::fromNative((object) [
                    'errors' => [
                        [
                            'message' => \Graphpinator\Parser\Exception\UnexpectedEnd::MESSAGE,
                            'locations' => [['line' => 1, 'column' => 30]],
                        ],
                    ],
                ]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { field } query queryName { field }',
                ]),
                Json::fromNative((object) [
                    'errors' => [
                        [
                            'message' => \Graphpinator\Parser\Exception\DuplicateOperation::MESSAGE,
                            'locations' => [['line' => 1, 'column' => 27]],
                        ],
                    ],
                ]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { field } query queryName { field }',
                ]),
                Json::fromNative((object) [
                    'errors' => [
                        [
                            'message' => \Graphpinator\Parser\Exception\DuplicateOperation::MESSAGE,
                            'locations' => [['line' => 1, 'column' => 27]],
                        ],
                    ],
                ]),
            ],
        ];
    }

    public function normalizerDataProvider() : array
    {
        return [
            [
                Json::fromNative((object) []),
                Json::fromNative((object) ['errors' => [[
                    'message' => 'Invalid request - "query" key not found in request body JSON.'
                ]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => '{ fieldAbc @blaDirective() { fieldXyz { name } } }',
                ]),
                Json::fromNative((object) ['errors' => [[
                    'message' => 'Unknown directive "blaDirective".',
                    'path' => [' <operation>', 'fieldAbc <field>', 'blaDirective <directive>'],
                ]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => '{ fieldAbc { ... on BlaType { fieldXyz { name } } } }',
                ]),
                Json::fromNative((object) ['errors' => [[
                    'message' => 'Unknown type "BlaType".',
                    'path' => [' <operation>', 'fieldAbc <field>', '<inline fragment>'],
                ]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => '{ fieldAbc { ... on String { fieldXyz { name } } } }',
                ]),
                Json::fromNative((object) ['errors' => [[
                    'message' => 'Fragment type condition must be outputable composite type.',
                    'path' => [' <operation>', 'fieldAbc <field>', '<inline fragment>'],
                ]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => '{ fieldAbc { ... fragmentName } } fragment fragmentName on String { fieldXyz { name } }',
                ]),
                Json::fromNative((object) ['errors' => [[
                    'message' => 'Fragment type condition must be outputable composite type.',
                    'path' => [' <operation>', 'fieldAbc <field>', 'fragmentName <fragment spread>'],
                ]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => '{ fieldAbc { ... on SimpleInput { fieldXyz { name } } } }',
                ]),
                Json::fromNative((object) ['errors' => [[
                    'message' => 'Fragment type condition must be outputable composite type.',
                    'path' => [' <operation>', 'fieldAbc <field>', '<inline fragment>'],
                ]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => '{ fieldAbc { fieldXyz @testDirective(if: true) { name } } }',
                ]),
                Json::fromNative((object) ['errors' => [[
                    'message' => 'Unknown argument "if" provided.',
                    'path' => [' <operation>', 'fieldAbc <field>', 'fieldXyz <field>', 'testDirective <directive>'],
                ]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => '{ fieldInvalidInput { fieldName fieldNumber fieldBool notDefinedField } }',
                ]),
                Json::fromNative((object) ['errors' => [[
                    'message' => 'Unknown field "notDefinedField" requested for type "SimpleType".',
                    'path' => [' <operation>', 'fieldInvalidInput <field>', 'notDefinedField <field>'],
                ]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { fieldFragment { ... interfaceEfgFragment } }
                    fragment interfaceEfgFragment on InterfaceEfg {  
                        ... on InterfaceAbc { name }
                    }',
                ]),
                Json::fromNative((object) ['errors' => [[
                    'message' => 'Invalid fragment type condition. ("InterfaceAbc" is not instance of "InterfaceEfg").',
                    'path' => ['queryName <operation>', 'fieldFragment <field>', 'interfaceEfgFragment <fragment spread>', '<inline fragment>'],
                ]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName ($var1: Int = "123") { fieldAbc { fieldXyz { name } } }',
                    'variables' => (object) [],
                ]),
                Json::fromNative((object) ['errors' => [[
                    'message' => 'Invalid value resolved for type "Int" - got "123".',
                    'path' => ['queryName <operation>', 'var1 <variable>'],
                ]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName ($var1: Int!) { fieldAbc { fieldXyz { name } } }',
                    'variables' => (object) [],
                ]),
                Json::fromNative((object) ['errors' => [[
                    'message' => 'Not-null type with null value.',
                    'path' => ['queryName <operation>', 'var1 <variable>'],
                ]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { 
                        fieldAbstractNullList { 
                            ... on Abc { fieldXyz { name } } 
                            ... on Xyz { name }
                        } 
                    }',
                ]),
                Json::fromNative((object) ['errors' => [[
                    'message' => 'Server responded with unknown error.',
                ]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => '{ fieldUnion @invalidDirectiveResult() { ... on Abc { fieldXyz { name } } } }',
                ]),
                Json::fromNative((object) ['errors' => [[
                    'message' => 'Server responded with unknown error.',
                ]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => '{ fieldThrow { fieldXyz { name } } }',
                ]),
                Json::fromNative((object) ['errors' => [[
                    'message' => 'Server responded with unknown error.',
                ]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => '{ fieldInvalidType { } }',
                ]),
                Json::fromNative((object) ['errors' => [[
                    'message' => 'Server responded with unknown error.',
                ]]]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { fieldAbc { fieldXyz @skip(if: false) @skip(if: false) { name } } }',
                ]),
                Json::fromNative((object) [
                    'errors' => [[
                        'message' => 'Duplicated directive "skip" which is not repeatable.',
                        'path' => ['queryName <operation>', 'fieldAbc <field>', 'fieldXyz <field>', 'skip <directive>'],
                    ]],
                ]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { fieldArgumentDefaults(inputNumberList: [123, "invalid"]) { fieldBool } }',
                ]),
                Json::fromNative((object) [
                    'errors' => [[
                        'message' => 'Invalid value resolved for type "Int" - got "invalid".',
                        'path' => ['queryName <operation>', 'fieldArgumentDefaults <field>', 'inputNumberList <argument>', '1 <list index>'],
                    ]],
                ]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName { fieldEnumArg(val: 123) }',
                ]),
                Json::fromNative((object) [
                    'errors' => [[
                        'message' => 'Invalid value resolved for type "SimpleEnum" - got 123.',
                        'path' => ['queryName <operation>', 'fieldEnumArg <field>', 'val <argument>'],
                    ]],
                ]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query queryName ($var1: [Int!]!) { fieldArgumentDefaults(inputNumberList: $var1) { fieldBool } }',
                    'variables' => (object) ['var1' => [123, "invalid"]]
                ]),
                Json::fromNative((object) [
                    'errors' => [[
                        'message' => 'Invalid value resolved for type "Int" - got "invalid".',
                        'path' => ['queryName <operation>', 'var1 <variable>', '1 <list index>'],
                    ]],
                ]),
            ],
            [
                Json::fromNative((object) [
                    'query' => 'query { fieldUnion { ... on Abc { fieldXyz { ... on TestInterface { __typename name } } } } }',
                ]),
                Json::fromNative((object) [
                    'errors' => [[
                        'message' => 'Invalid fragment type condition. ("TestInterface" is not instance of "Xyz").',
                        'path' => [' <operation>', 'fieldUnion <field>', '<inline fragment>', 'fieldXyz <field>', '<inline fragment>'],
                    ]],
                ]),
            ],
        ];
    }

    /**
     * @dataProvider tokenizerDataProvider
     * @dataProvider parserDataProvider
     * @dataProvider normalizerDataProvider
     * @param Json $request
     * @param Json $expected
     */
    public function testSimple(Json $request, Json $expected) : void
    {
        $graphpinator = new \Graphpinator\Graphpinator(TestSchema::getSchema(), true);
        $result = $graphpinator->run(new \Graphpinator\Request\JsonRequestFactory($request));

        self::assertSame($expected->toString(), $result->toString());
    }

    public function psrDataProvider() : array
    {
        $httpRequest = $this->createStub(\Psr\Http\Message\ServerRequestInterface::class);
        $httpRequest->method('getMethod')->willReturn('PUT');

        $httpRequest2 = $this->createStub(\Psr\Http\Message\ServerRequestInterface::class);
        $httpRequest2->method('getParsedBody')->willReturn([]);
        $httpRequest2->method('getHeader')->willReturn(['multipart/form-data; boundary=-------9051914041544843365972754266']);
        $httpRequest2->method('getMethod')->willReturn('POST');

        $httpRequest3 = $this->createStub(\Psr\Http\Message\ServerRequestInterface::class);
        $httpRequest3->method('getParsedBody')->willReturn(['operations' => '{}']);
        $httpRequest3->method('getHeader')->willReturn(['multipart/form-data; boundary=-------9051914041544843365972754266']);
        $httpRequest3->method('getMethod')->willReturn('GET');

        return [
            [
                $httpRequest,
                Json::fromNative((object) [
                    'errors' => [
                        ['message' => 'Invalid request - only GET and POST methods are supported.'],
                    ],
                ]),
            ],
            [
                $httpRequest2,
                Json::fromNative((object) [
                    'errors' => [
                        ['message' => 'Invalid multipart request - request must be POST and contain "operations" data.'],
                    ],
                ]),
            ],
            [
                $httpRequest3,
                Json::fromNative((object) [
                    'errors' => [
                        ['message' => 'Invalid multipart request - request must be POST and contain "operations" data.'],
                    ],
                ]),
            ],
        ];
    }

    /**
     * @dataProvider psrDataProvider
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param Json $expected
     */
    public function testPsrRequest(\Psr\Http\Message\ServerRequestInterface $request, Json $expected) : void
    {
        $graphpinator = new \Graphpinator\Graphpinator(TestSchema::getSchema(), true);
        $result = $graphpinator->run(new \Graphpinator\Request\PsrRequestFactory($request));

        self::assertSame($expected->toString(), $result->toString());
    }
}
