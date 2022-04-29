<?php

declare(strict_types=1);

namespace IteratorTools;

use PHPUnit\Framework\TestCase;
use stdClass;

class OptionalTest extends TestCase
{
    /**
     * @test
     * @dataProvider emptyOptionalDataProvider
     */
    public function it_should_throw_exception_when_empty(Optional $empty): void
    {
        $this->expectException(NotFoundException::class);

        $empty->get();
    }

    /**
     * @psalm-return array<list<Optional>>
     */
    public function emptyOptionalDataProvider(): array
    {
        return [
            [Optional::empty()],
            [Optional::from(null)],
        ];
    }

    /** @test */
    public function it_should_return_value_when_not_empty(): void
    {
        $optional = Optional::from('Foo');

        /**
         * @psalm-suppress RedundantConditionGivenDocblockType
         */
        $this->assertSame('Foo', $optional->get());
    }

    /**
     * @test
     * @dataProvider emptyOptionalDataProvider
     */
    public function it_should_return_other_when_empty(Optional $empty): void
    {
        $this->assertSame('Foo', $empty->orElse('Foo'));
    }

    /**
     * @test
     * @dataProvider emptyOptionalDataProvider
     */
    public function it_should_return_false_when_empty(Optional $empty): void
    {
        $this->assertFalse($empty->isPresent());
    }

    /** @test */
    public function it_should_return_true_when_not_empty(): void
    {
        $optional = Optional::from(new stdClass());

        $this->assertTrue($optional->isPresent());
    }
}
