<?php

declare(strict_types=1);

namespace MK\IteratorTools;

use ArrayIterator;
use Exception;
use MK\IteratorTools\TestAsset\Person;
use PHPUnit\Framework\TestCase;

use function MK\IteratorTools\Consumers\float_sum;
use function MK\IteratorTools\Iterator\stream;

class IteratorStreamTest extends TestCase
{
    /** @test */
    public function it_should_append_after_map(): void
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
    public function it_should_append_after_filter(): void
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
    public function it_should_preserve_original_keys(): void
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
    public function it_should_return_same_starting_state_when_redicing_empty_iterator(): void
    {
        $stream = IteratorStream::empty();

        $result = $stream->reduce(0, function (): int {
            return 1;
        });

        $this->assertSame(0, $result);
    }


    /** @test */
    public function it_should_call_callback_for_each_item_and_return_last_result(): void
    {
        $stream = IteratorStream::from(['Hello', 'World']);

        $result = $stream->reduce("--", function (string $value, string $acc, int $key): string {
            return "{$acc}{$key}:{$value}--";
        });

        $this->assertSame("--0:Hello--1:World--", $result);
    }

    /** @test */
    public function it_should_return_items_in_reverse_order(): void
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


    /** @test */
    public function it_should_reverse_after_mapping_filtering_and_append(): void
    {
        $stream = IteratorStream::empty()
            ->append([
                1 => 'one',
                2 => 'two',
                3 => 'three',
            ])

            ->map(function (string $str) {
                return strtoupper($str);
            })

            ->filter(function (string $str): bool {
                return $str[0] === 'T';
            })

            ->reverse();

        $reversed = $stream->toArrayPreserveKeys();

        $expected = [
            3 => 'THREE',
            2 => 'TWO',
        ];

        $this->assertSame($expected, $reversed);
    }

    /** @test */
    public function it_should_limit_the_result(): void
    {
        $stream = IteratorStream::empty()
            ->append([
                'one' => 1,
                'two' => 2,
                'three' => 3,
                'four' => 4,
                'fife' => 5,
            ])

            ->limit(3);

        $result = $stream->toArrayPreserveKeys();

        $expected = [
            'one' => 1,
            'two' => 2,
            'three' => 3,
        ];

        $this->assertSame($expected, $result);
    }

    /** @test */
    public function it_should_skip_the_result(): void
    {
        $stream = IteratorStream::empty()
            ->append([
                'one' => 1,
                'two' => 2,
                'three' => 3,
                'four' => 4,
                'fife' => 5,
            ])

            ->skip(3);

        $result = $stream->toArrayPreserveKeys();

        $expected = [
            'four' => 4,
            'fife' => 5,
        ];

        $this->assertSame($expected, $result);
    }

    /** @test */
    public function it_should_allow_consumer_callbacks(): void
    {
        $sum = IteratorStream::from(['1', '2'])
            ->mapValue('floatval')
            ->filter(function () {
                return true;
            })
            ->consume(float_sum());

        $this->assertSame(3.0, $sum);
    }

    /** @test */
    public function it_should_return_first_matching_element(): void
    {
        $people = [
            0 => new Person('Nick', 10),
            1 => new Person('Carl', 18),
            2 => new Person('Jane', 25),
            3 => new Person('Mark', 42),
        ];

        $result = stream($people)->findAny(
            fn (Person $p) => $p->age() >= 25
        );

        $this->assertSame($people[2], $result->get());
    }

    /** @test */
    public function it_should_stop_consuming_source_when_item_found(): void
    {
        $people = (function () {
            yield 0 => new Person('Nick', 10);
            yield 1 => new Person('Carl', 18);
            yield 2 => new Person('Jane', 25);

            throw new Exception('This should not happen!');
        })();


        $result = stream($people)->findAny(
            fn (Person $p) => $p->age() >= 25
        );

        $this->assertSame('Jane', $result->get()->name());
    }

    /** @test */
    public function it_should_return_null_when_item_not_found(): void
    {
        $people = [
            new Person('Nick', 10),
            new Person('Carl', 18),
            new Person('Jane', 25),
        ];

        $result = stream($people)->findAny(fn () => false);

        $this->assertFalse($result->isPresent());
    }
}
