<?php

declare(strict_types=1);

namespace MK\IteratorTools;

use MK\IteratorTools\TestAsset\Person;
use PHPUnit\Framework\TestCase;
use function MK\IteratorTools\Iterator\stream;

class ConsumerTest extends TestCase
{
    /** @test */
    public function it_should_compute_int_sum(): void
    {
        $stream = stream([1, 2]);

        $sum = $stream->consume(Consumer::intSum());

        $this->assertSame(3, $sum);
    }

    /** @test */
    public function it_should_compute_float_sum(): void
    {
        $stream = stream([1.0, 2.0]);

        $sum = $stream->consume(Consumer::floatSum());

        $this->assertSame(3.0, $sum);
    }

    /** @test */
    public function it_should_compute_float_average(): void
    {
        $stream = stream([2.0, 4]);

        $sum = $stream->consume(Consumer::average());

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

            6 => new Person('Skip me', 62),
        ];

        $stream = stream($people);


        $map = $stream->consume(Consumer::groupBy(
            fn (Person $person) => ($person->name() !== 'Skip me'
                ? $person->name()
                : false)
        ));


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
    public function it_should_group_by_array_key(): void
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


        $map = $stream->consume(Consumer::groupByArrKey('name'));


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
}
