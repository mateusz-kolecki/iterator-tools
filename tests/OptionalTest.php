<?php

declare(strict_types=1);

namespace IteratorTools\Tests;

use Exception;
use InvalidArgumentException;
use IteratorTools\NotFoundException;
use IteratorTools\Optional;
use PHPUnit\Framework\TestCase;
use stdClass;

class OptionalTest extends TestCase
{
    /** @test */
    public function it_should_throw_exception_when_empty(): void
    {
        $empty = Optional::empty();

        $this->expectException(NotFoundException::class);

        $empty->getOrThrow();
    }

    /** @test */
    public function it_should_return_stored_value(): void
    {
        /** @psalm-var Optional<int> $optional */
        $optional = Optional::from(1234);

        $this->assertSame(1234, $optional->getOrThrow());
        $this->assertEquals(1234, $optional->orElse(0));
    }

    /** @test */
    public function it_should_throw_when_null_passed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Optional::from(null);
    }

    /** @test */
    public function it_should_return_stored_value_when_not_empty(): void
    {
        /** @psalm-var Optional<string> $optional */
        $optional = Optional::from('Foo');

        $this->assertSame('Foo', $optional->getOrThrow());
        $this->assertEquals('Foo', $optional->orElse('Bar'));
    }

    /** @test */
    public function it_should_return_other_when_empty(): void
    {
        /** @psalm-var Optional<string> $empty */
        $empty = Optional::empty();

        $result = $empty->orElse('Foo');

        $this->assertSame('Foo', $result);
    }

    /** @test */
    public function it_should_return_supplied_value_when_empty(): void
    {
        /** @psalm-var Optional<string> $empty */
        $empty = Optional::empty();

        $result = $empty->orElseGet(fn (): string => 'Foo');

        $this->assertSame('Foo', $result);
    }

    /** @test */
    public function it_should_return_stored_value_when_empty_and_not_call_supplier(): void
    {
        /** @psalm-var Optional<string> $optional */
        $optional = Optional::from('Foo');

        $result = $optional->orElseGet(function (): string {
            throw new Exception();
        });

        $this->assertSame('Foo', $result);
    }

    /** @test */
    public function it_should_return_false_when_empty(): void
    {
        $empty = Optional::empty();

        $this->assertFalse($empty->isPresent());
    }

    /** @test */
    public function it_should_return_true_when_not_empty(): void
    {
        $optional = Optional::from(new stdClass());

        $this->assertTrue($optional->isPresent());
    }

    /** @test */
    public function it_should_return_mapped_optional_when_not_empty(): void
    {
        $mapped = Optional::from('123')
            ->map(fn (string $stringNum): int => intval($stringNum));

        $this->assertTrue($mapped->isPresent());
        $this->assertSame(123, $mapped->orElse(0));
    }

    /** @test */
    public function it_should_not_call_the_mapper_and_return_empty_when_mapping_on_empty(): void
    {
        $empty = Optional::empty();

        $mapped = $empty->map(function (): int {
            throw new Exception();
        });

        $this->assertSame(Optional::empty(), $mapped);
    }

    /** @test */
    public function it_should_create_empty_pipeline_when_not_present(): void
    {
        $empty = Optional::empty();

        $result = $empty->pipeline()->toArray();

        $this->assertCount(0, $result);
    }

    /** @test */
    public function it_should_create_pipeline_with_single_element(): void
    {
        $optional = Optional::from(123);

        $result = $optional->pipeline()->toArray();

        $this->assertSame([123], $result);
    }

    /** @test */
    public function it_should_allow_for_converting_back_and_forth(): void
    {
        /** @psalm-var Optional<int> $optional */
        $optional = Optional::from(123);

        $result = $optional->pipeline()
            ->findAnyValue(fn () => true)
            ->orElse(0);

        $this->assertSame(123, $result);
    }

    /** @test */
    public function there_is_only_one_empty(): void
    {
        $this->assertSame(Optional::empty(), Optional::empty());
        $this->assertSame(Optional::fromNullable(null), Optional::empty());
    }
}
