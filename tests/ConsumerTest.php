<?php

declare(strict_types=1);

namespace MK\IteratorTools;

use MK\IteratorTools\TestAsset\Person;
use PHPUnit\Framework\TestCase;

class ConsumerTest extends TestCase
{
    /** @test */
    function it_should_compute_int_sum(): void
    {
        $stream = IteratorStream::from([1, 2]);

        $sum = $stream->consume(Consumer::intSum());

        $this->assertSame(3, $sum);
    }

    /** @test */
    function it_should_compute_float_sum(): void
    {
        $stream = IteratorStream::from([1.0, 2.0]);

        $sum = $stream->consume(Consumer::floatSum());

        $this->assertSame(3.0, $sum);
    }

    /** @test */
    function it_should_compute_float_average(): void
    {
        $stream = IteratorStream::from([2.0, 4.0]);

        $sum = $stream->consume(Consumer::floatAverage());

        $this->assertSame(3.0, $sum);
    }

    /** @test */
    function it_should_group_by_values_from_callback(): void
    {
        $people = [
            0 => new Person('Adam', 35),
            1 => new Person('Mark', 30),
            2 => new Person('Adam', 18),
            3 => new Person('John', 28),
            4 => new Person('Mark', 46),
            5 => new Person('John', 62),
        ];

        $stream = IteratorStream::from($people);


        $map = $stream->consume(Consumer::groupBy(
            fn (Person $person) => $person->name()
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
}
