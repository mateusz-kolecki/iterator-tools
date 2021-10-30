<?php

declare(strict_types=1);

namespace MK\IteratorTools\Iterator;

use ArrayIterator;
use Generator;
use PHPUnit\Framework\TestCase;
use function iterator_to_array;

class ReverseIteratorTest extends TestCase
{
    /** @test */
    public function it_should_return_values_and_keys_in_reverse_order(): void
    {
        $input = new ArrayIterator([
            'one' => 1,
            'two' => 2,
            3 => 'three',
        ]);

        $reverseIterator = new ReverseIterator($input);

        $result = iterator_to_array($reverseIterator, true);


        $expected = [
            3 => 'three',
            'two' => 2,
            'one' => 1,
        ];

        $this->assertSame($expected, $result);
    }


    /** @test */
    public function it_will_not_consume_input_iterator_on_creation(): void
    {
        $consumed = false;

        $input = (function () use (&$consumed): Generator {
            $consumed = true;

            yield 'one' => 1;
            yield 'two' => 2;
        })();

        new ReverseIterator($input);

        $this->assertFalse($consumed);
    }


    /** @test */
    public function it_will_consume_input_only_once_and_return_same_values_and_keys_when_consumed_more_than_once(): void
    {
        // Cannot rewind a generator that was already run
        $input = (function (): Generator {
            yield 1;
            yield 2;
            yield 3;
        })();

        $reverseIterator = new ReverseIterator($input);

        $r1 = iterator_to_array($reverseIterator, true);
        $r2 = iterator_to_array($reverseIterator, true);

        $expected = [
            2 => 3,
            1 => 2,
            0 => 1,
        ];

        $this->assertSame($expected, $r1);
        $this->assertSame($expected, $r2);
    }
}
