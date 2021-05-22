<?php

declare(strict_types = 1);

namespace Graphpinator\Tests\Unit\Normalizer;

final class EmptyFragmentModuleTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyFragmentSpread() : void
    {
        $fragmentSpread = new \Graphpinator\Normalizer\Selection\FragmentSpread(
            'someName',
            new \Graphpinator\Normalizer\Selection\SelectionSet(),
            new \Graphpinator\Normalizer\Directive\DirectiveSet(),
            new class extends \Graphpinator\Type\Type {
                public function validateNonNullValue(mixed $rawValue): bool {}
                protected function getFieldDefinition(): \Graphpinator\Field\ResolvableFieldSet {}
            },
        );

        $refiner = new \Graphpinator\Normalizer\SelectionSetRefiner(new \Graphpinator\Normalizer\Selection\SelectionSet([
            $fragmentSpread,
        ]), \Graphpinator\Container\Container::String());

        $result = $refiner->refine();

        self::assertCount(0, $result);
    }

    public function testEmptyInlineFragment() : void
    {
        $inlineFragment = new \Graphpinator\Normalizer\Selection\InlineFragment(
            new \Graphpinator\Normalizer\Selection\SelectionSet(),
            new \Graphpinator\Normalizer\Directive\DirectiveSet(),
            null,
        );

        $refiner = new \Graphpinator\Normalizer\SelectionSetRefiner(new \Graphpinator\Normalizer\Selection\SelectionSet([
            $inlineFragment,
        ]), \Graphpinator\Container\Container::String());

        $result = $refiner->refine();

        self::assertCount(0, $result);
    }

    public function testEmptyCombined() : void
    {
        $fragmentSpread = new \Graphpinator\Normalizer\Selection\FragmentSpread(
            'someName',
            new \Graphpinator\Normalizer\Selection\SelectionSet(),
            new \Graphpinator\Normalizer\Directive\DirectiveSet(),
            new class extends \Graphpinator\Type\Type {
                public function validateNonNullValue(mixed $rawValue): bool {}
                protected function getFieldDefinition(): \Graphpinator\Field\ResolvableFieldSet {}
            },
        );
        $inlineFragment = new \Graphpinator\Normalizer\Selection\InlineFragment(
            new \Graphpinator\Normalizer\Selection\SelectionSet([
                $fragmentSpread,
            ]),
            new \Graphpinator\Normalizer\Directive\DirectiveSet(),
            null,
        );

        $refiner = new \Graphpinator\Normalizer\SelectionSetRefiner(new \Graphpinator\Normalizer\Selection\SelectionSet([
            $inlineFragment,
        ]), \Graphpinator\Container\Container::String());

        $result = $refiner->refine();

        self::assertCount(0, $result);
    }
}
