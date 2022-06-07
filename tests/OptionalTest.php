<?php

declare(strict_types=1);

namespace IteratorTools\Tests;

use IteratorTools\NotFoundException;
use IteratorTools\Optional;
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
    public function it_should_return_stored_value_when_not_empty(): void
    {
        /** @psalm-var Optional<string> */
        $optional = Optional::from('Foo');

        $this->assertSame('Foo', $optional->get());
        $this->assertEquals('Foo', $optional->orElse('Bar')->get());
    }

    /**
     * @test
     * @dataProvider emptyOptionalDataProvider
     */
    public function it_should_return_other_when_empty(Optional $empty): void
    {
        $this->assertSame('Foo', $empty->orElse('Foo')->get());
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

    /** @test */
    public function it_return_new_object_when_calling_or_else(): void
    {
        /** @psalm-var Optional<int> $optional */
        $optional = Optional::from(1);
        $alternate = $optional->orElse(2);

        $this->assertNotSame($optional, $alternate);
    }

    /** @test */
    public function there_is_only_one_empty(): void
    {
        $this->assertSame(Optional::empty(), Optional::empty());
        $this->assertSame(Optional::from(null), Optional::empty());
    }
}
