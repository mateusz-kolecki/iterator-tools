<?php

declare(strict_types=1);

namespace MK\IteratorTools;

use ArrayIterator;
use PHPUnit\Framework\TestCase;

class IteratorStreamTest extends TestCase
{
    /** @test */
    function it_should_append_after_map(): void
    {
        $stream = IteratorStream::empty()
            ->append([1, 2, 3])
            ->map(function (int $n): string {
                return strval($n * 3);
            })
            ->append(new ArrayIterator(['A', 'B']));

        $result = $stream->toArray();

        $expected = [
            '3', '6', '9',
            'A', 'B'
        ];

        $this->assertSame($expected, $result);
    }

    /** @test */
    function it_should_append_after_filter(): void
    {
        $stream = IteratorStream::from(['a'])
            ->append(['del_1', 'keep_2', 'del_3', 'keep_4'])

            ->filter(function (string $s): bool {
                return substr($s, 0, 4) === 'keep';
            })

            ->map(function (string $s): string {
                return strtoupper($s);
            })

            ->append(['foo_5', 'foo_6'])

            ->map(function (string $s): string {
                return str_replace('_', ' ', $s);
            });

        $result = $stream->toArray();

        $expected = [
            'KEEP 2', 'KEEP 4',
            'foo 5', 'foo 6'
        ];

        $this->assertSame($expected, $result);
    }


    /** @test */
    function it_should_preserve_original_keys(): void
    {
        $stream = IteratorStream::empty()
            ->append([
                'one' => 1,
                'foo' => 2,
            ])

            ->append((function () {
                yield 'three' => 3;
                yield 'bar' => 4;
            })())

            ->map(function (int $value): int {
                return 2 * $value;
            })

            ->filter(function ($_, string $key) {
                return in_array($key, ['foo', 'bar'], true);
            });


        $result = $stream->toArrayPreserveKeys();

        $expected = [
            'foo' => 4,
            'bar' => 8,
        ];

        $this->assertEquals($expected, $result);
    }


    /** @test */
    function it_should_return_same_starting_state_when_redicing_empty_iterator(): void
    {
        $stream = IteratorStream::empty();

        $result = $stream->reduce(0, function ($_v, $_k, int $counter): int {
            return $counter + 1;
        });

        $this->assertSame(0, $result);
    }


    /** @test */
    function it_should_call_callback_for_each_item_and_return_last_result(): void
    {
        $stream = IteratorStream::from(['Hello', 'World']);

        $result = $stream->reduce("--", function (string $value, int $key, string $acc): string {
            return "{$acc}{$key}:{$value}--";
        });

        $this->assertSame("--0:Hello--1:World--", $result);
    }

    /** @test */
    function it_should_return_items_in_reverse_order(): void
    {
        $stream = IteratorStream::from([
            1 => 'one',
            2 => 'two',
            3 => 'three',
        ]);

        $reversed = $stream->reverse()
            ->toArrayPreserveKeys();

        $expected = [
            3 => 'three',
            2 => 'two',
            1 => 'one',
        ];

        $this->assertSame($expected, $reversed);
    }
}
