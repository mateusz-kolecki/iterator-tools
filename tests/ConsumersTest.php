<?php

declare(strict_types=1);

namespace MK\IteratorTools;

use MK\IteratorTools\TestAsset\Person;
use PHPUnit\Framework\TestCase;
use function MK\IteratorTools\Iterator\stream;

class ConsumersTest extends TestCase
{
    /** @test */
    public function it_should_compute_int_sum(): void
    {
        $stream = stream([1, 2]);

        $sum = $stream->consume(Consumers::intSum());

        $this->assertSame(3, $sum);
    }

    /** @test */
    public function it_should_compute_float_sum(): void
    {
        $stream = stream([1.0, 2.0]);

        $sum = $stream->consume(Consumers::floatSum());

        $this->assertSame(3.0, $sum);
    }

    /** @test */
    public function it_should_compute_float_average(): void
    {
        $stream = stream([2.0, 4]);

        $sum = $stream->consume(Consumers::average());

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

        $map = stream($people)->consume(
            Consumers::groupBy(fn (Person $p) => $p->name())
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

        $map = stream($people)->consume(
            Consumers::groupBy(function (Person $p) {
                if ($p->name() === 'skip me') {
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
        $stream = stream([
            ['name' => 'Adam', 'age' => 35],
            ['name' => 'Mark', 'age' => 30],
            ['name' => 'Adam', 'age' => 18],
            ['name' => 'John', 'age' => 28],
            ['name' => 'Mark', 'age' => 46],
            ['name' => 'John', 'age' => 62],

            ['not-a-name' => 'Foo'],
        ]);


        $map = $stream->consume(Consumers::groupByArrKey('name'));


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
        $stream = stream(['foo', 'bar', 'baz', 'qux']);

        $result = $stream->consume(Consumers::join());

        $this->assertSame('foobarbazqux', $result);
    }

    /** @test */
    public function it_should_join_string_elements_using_delimiter(): void
    {
        $stream = stream(['foo', 'bar', 'baz', 'qux']);

        $result = $stream->consume(Consumers::join('--'));

        $this->assertSame('foo--bar--baz--qux', $result);
    }
}