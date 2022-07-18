<?php

declare(strict_types=1);

namespace IteratorTools\Tests;

use IteratorTools\IteratorPipeline;
use IteratorTools\NotFoundException;
use IteratorTools\Tests\TestAsset\Person;
use PHPUnit\Framework\TestCase;

use function IteratorTools\Consumers\float_average;
use function IteratorTools\Consumers\float_max;
use function IteratorTools\Consumers\float_min;
use function IteratorTools\Consumers\float_min_max;
use function IteratorTools\Consumers\float_sum;
use function IteratorTools\Consumers\group_by;
use function IteratorTools\Consumers\group_by_arr_key;
use function IteratorTools\Consumers\int_sum;
use function IteratorTools\Consumers\str_join;
use function IteratorTools\Iterator\pipeline;

class ConsumersTest extends TestCase
{
    /** @test */
    public function it_should_compute_int_sum(): void
    {
        $pipeline = pipeline([1, 2]);

        $sum = $pipeline->consume(int_sum());

        $this->assertSame(3, $sum);
    }

    /** @test */
    public function it_should_compute_float_sum(): void
    {
        $pipeline = pipeline([1.0, 2.0]);

        $sum = $pipeline->consume(float_sum());

        $this->assertSame(3.0, $sum);
    }

    /** @test */
    public function it_should_compute_float_average(): void
    {
        $pipeline = pipeline([2.0, 4]);

        $sum = $pipeline->consume(float_average());

        $this->assertSame(3.0, $sum);
    }

    /** @test */
    public function it_should_group_by_values_from_callback(): void
    {
        $people = [
            0 => new Person('Adam', 35),
            1 => new Person('Mark', 30),
            2 => new Person('Adam', 18),
            3 => new Person('John', 28),
            4 => new Person('Mark', 46),
            5 => new Person('John', 62),
        ];

        $map = pipeline($people)->consume(
            group_by(fn (Person $p) => $p->name())
        );

        $expected = [
            'Adam' => [
                $people[0],
                $people[2],
            ],
            'Mark' => [
                $people[1],
                $people[4],
            ],
            'John' => [
                $people[3],
                $people[5],
            ],
        ];

        $this->assertSame($expected, $map);
    }

    /** @test */
    public function it_should_skip_when_group_by_value_is_false(): void
    {
        $people = [
            0 => new Person('Adam', 35),
            1 => new Person('Mark', 30),
            2 => new Person('Adam', 18),
            3 => new Person('John', 28),
            4 => new Person('Mark', 46),
            5 => new Person('John', 62),

            6 => new Person('skip me', 100),
        ];

        $map = pipeline($people)->consume(
            group_by(function (Person $p) {
                if ('skip me' === $p->name()) {
                    return false;
                }

                return $p->name();
            })
        );

        $expected = [
            'Adam' => [
                $people[0],
                $people[2],
            ],
            'Mark' => [
                $people[1],
                $people[4],
            ],
            'John' => [
                $people[3],
                $people[5],
            ],
        ];

        $this->assertSame($expected, $map);
    }

    /** @test */
    public function it_should_group_by_array_key_skipping_items_not_containing_given_key(): void
    {
        $pipeline = pipeline([
            ['name' => 'Adam', 'age' => 35],
            ['name' => 'Mark', 'age' => 30],
            ['name' => 'Adam', 'age' => 18],
            ['name' => 'John', 'age' => 28],
            ['name' => 'Mark', 'age' => 46],
            ['name' => 'John', 'age' => 62],

            ['not-a-name' => 'Foo'],
        ]);


        $map = $pipeline->consume(group_by_arr_key('name'));


        $expected = [
            'Adam' => [
                ['name' => 'Adam', 'age' => 35],
                ['name' => 'Adam', 'age' => 18],
            ],
            'Mark' => [
                ['name' => 'Mark', 'age' => 30],
                ['name' => 'Mark', 'age' => 46],
            ],
            'John' => [
                ['name' => 'John', 'age' => 28],
                ['name' => 'John', 'age' => 62],
            ],
        ];

        $this->assertSame($expected, $map);
    }

    /** @test */
    public function it_should_join_string_elements(): void
    {
        $pipeline = pipeline(['foo', 'bar', 'baz', 'qux']);

        $result = $pipeline->consume(str_join());

        $this->assertSame('foobarbazqux', $result);
    }

    /** @test */
    public function it_should_join_string_elements_using_delimiter(): void
    {
        $stringable = new class () {
            public function __toString(): string
            {
                return 'Stringable';
            }
        };

        $pipeline = pipeline(['foo', 'bar', 'baz', 'qux', $stringable]);

        $result = $pipeline->consume(str_join('--'));

        $this->assertSame('foo--bar--baz--qux--Stringable', $result);
    }

    /**
     * @test
     * @dataProvider delimiterDataProvider
     */
    public function it_should_return_empty_string_when_joining_empty_pipeline(
        string $delimiter
    ): void {
        $pipeline = pipeline([]);

        $result = $pipeline->consume(str_join($delimiter));

        $this->assertSame('', $result);
    }

    /**
     * @psalm-return array{string}[]
     */
    public function delimiterDataProvider(): array
    {
        return [
            [''],
            ['--'],
        ];
    }

    /** @test */
    public function it_should_find_minim_and_maximum_value(): void
    {
        $numbers = pipeline([3, 2, 5, 7, -2, 10, 30, 100, 50]);

        $minMax = $numbers->consume(float_min_max());

        $this->assertSame(-2, (int)$minMax->min);
        $this->assertSame(100, (int)$minMax->max);
    }

    /** @test */
    public function it_should_find_minim(): void
    {
        $numbers = pipeline([3, 2, 5, 7, -2, 10, 30, 100, 50]);

        $min = $numbers->consume(float_min());

        $this->assertSame(-2, (int)$min);
    }

    /** @test */
    public function it_should_find_max(): void
    {
        $numbers = pipeline([3, 2, 5, 7, -2, 10, 30, 100, 50]);

        $max = $numbers->consume(float_max());

        $this->assertSame(100, (int)$max);
    }

    /**
     * @test
     * @dataProvider minMaxDataProvider
     *
     * @psalm-param callable(IteratorPipeline<mixed,float|int>):mixed $consumer
     */
    public function it_should_throw_not_found_exception_when_pipeline_is_empty(
        callable $consumer
    ): void {
        $empty = pipeline();

        $this->expectException(NotFoundException::class);

        $empty->consume($consumer);
    }

    /**
     * @psalm-return list<list<callable(IteratorPipeline<mixed,int|float>):mixed>>
     */
    public function minMaxDataProvider(): array
    {
        return [
            [float_min_max()],
            [float_min()],
            [float_max()],
        ];
    }
}
