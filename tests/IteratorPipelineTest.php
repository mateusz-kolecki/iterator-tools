<?php

declare(strict_types=1);

namespace IteratorTools\Tests;

use ArrayIterator;
use Exception;
use Generator;
use IteratorTools\Pair;
use IteratorTools\Tests\TestAsset\Person;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

use function IteratorTools\Consumers\float_sum;
use function IteratorTools\Iterator\pipeline;
use function strtolower;
use function strtoupper;

class IteratorPipelineTest extends TestCase
{
    /** @test */
    public function it_should_append_after_map(): void
    {
        $pipeline = pipeline()
            ->append([1, 2, 3])
            ->map(function (int $n): string {
                return strval($n * 3);
            })
            ->append(new ArrayIterator(['A', 'B']));

        $result = $pipeline->toArray();

        $expected = [
            '3', '6', '9',
            'A', 'B'
        ];

        $this->assertSame($expected, $result);
    }

    /** @test */
    public function it_should_append_after_filter(): void
    {
        $pipeline = pipeline(['a'])
            ->append(['del_1', 'keep_2', 'del_3', 'keep_4'])
            ->filter(function (string $s): bool {
                return 'keep' === substr($s, 0, 4);
            })
            ->map(function (string $s): string {
                return strtoupper($s);
            })
            ->append(['foo_5', 'foo_6'])
            ->map(function (string $s): string {
                return str_replace('_', ' ', $s);
            });

        $result = $pipeline->toArray();

        $expected = [
            'KEEP 2', 'KEEP 4',
            'foo 5', 'foo 6'
        ];

        $this->assertSame($expected, $result);
    }


    /** @test */
    public function it_should_preserve_original_keys(): void
    {
        $pipeline = pipeline()
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


        $result = $pipeline->toArrayPreserveKeys();

        $expected = [
            'foo' => 4,
            'bar' => 8,
        ];

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_should_extract_iterator(): void
    {
        $names = [
            'first' => 'Mark',
            'second' => 'John'
        ];

        $extractingPipeline = pipeline($names)
            ->extract(function (string $name, string $key): Generator {
                yield "{$name}_{$key}_normal" => $name;
                yield "{$name}_{$key}_lower" => strtolower($name);
                yield "{$name}_{$key}_upper" => strtoupper($name);
            });

        $expected = [
            'Mark_first_normal' => 'Mark',
            'Mark_first_lower' => 'mark',
            'Mark_first_upper' => 'MARK',
            'John_second_normal' => 'John',
            'John_second_lower' => 'john',
            'John_second_upper' => 'JOHN',
        ];

        $this->assertSame($expected, $extractingPipeline->toArrayPreserveKeys());
    }

    /** @test */
    public function it_throws_exception_when_extracting_callback_returns_not_iterable_value(): void
    {
        /** @psalm-suppress InvalidArgument */
        $extractingPipeline = pipeline(['foo'])->extract(fn () => true);

        $this->expectException(UnexpectedValueException::class);

        $extractingPipeline->toArray();
    }

    /** @test */
    public function it_should_return_same_starting_state_when_reducing_empty_iterator(): void
    {
        $pipeline = pipeline();

        $result = $pipeline->reduce(0, function (): int {
            return 1;
        });

        $this->assertSame(0, $result);
    }


    /** @test */
    public function it_should_call_callback_for_each_item_and_return_last_result(): void
    {
        $pipeline = pipeline(['Hello', 'World']);

        $result = $pipeline->reduce("--", function (string $value, string $acc, int $key): string {
            return "{$acc}{$key}:{$value}--";
        });

        $this->assertSame("--0:Hello--1:World--", $result);
    }

    /** @test */
    public function it_should_return_items_in_reverse_order(): void
    {
        $pipeline = pipeline([
            1 => 'one',
            2 => 'two',
            3 => 'three',
        ]);

        $reversed = $pipeline->reverse()
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
        $pipeline = pipeline()
            ->append([
                1 => 'one',
                2 => 'two',
                3 => 'three',
            ])
            ->map(function (string $str) {
                return strtoupper($str);
            })
            ->filter(function (string $str): bool {
                return 'T' === $str[0];
            })
            ->reverse();

        $reversed = $pipeline->toArrayPreserveKeys();

        $expected = [
            3 => 'THREE',
            2 => 'TWO',
        ];

        $this->assertSame($expected, $reversed);
    }

    /** @test */
    public function it_should_limit_the_result(): void
    {
        $pipeline = pipeline()
            ->append([
                'one' => 1,
                'two' => 2,
                'three' => 3,
                'four' => 4,
                'fife' => 5,
            ])
            ->limit(3);

        $result = $pipeline->toArrayPreserveKeys();

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
        $pipeline = pipeline()
            ->append([
                'one' => 1,
                'two' => 2,
                'three' => 3,
                'four' => 4,
                'fife' => 5,
            ])
            ->skip(3);

        $result = $pipeline->toArrayPreserveKeys();

        $expected = [
            'four' => 4,
            'fife' => 5,
        ];

        $this->assertSame($expected, $result);
    }

    /** @test */
    public function it_should_allow_consumer_callbacks(): void
    {
        $sum = pipeline(['1', '2'])
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

        $result = pipeline($people)->findAnyKeyValue(
            fn (Person $p) => 25 <= $p->age()
        );

        $this->assertSame(2, $result->getOrThrow()->key());
        $this->assertSame($people[2], $result->getOrThrow()->value());
    }

    /** @test */
    public function it_should_stop_consuming_source_when_item_found(): void
    {
        $people = function (): Generator {
            yield 0 => new Person('Nick', 10);
            yield 1 => new Person('Carl', 18);
            yield 2 => new Person('Jane', 25);

            throw new Exception('This should not happen!');
        };


        $result = pipeline($people())->findAnyKeyValue(
            fn (Person $p) => 25 <= $p->age()
        );

        $this->assertSame('Jane', $result->getOrThrow()->value()->name());
    }

    /** @test */
    public function it_should_return_empty_optional_when_item_not_found(): void
    {
        $people = [
            new Person('Nick', 10),
            new Person('Carl', 18),
            new Person('Jane', 25),
        ];

        $result = pipeline($people)->findAnyKeyValue(fn () => false);

        $this->assertFalse($result->isPresent());
    }

    /** @test */
    public function it_should_return_empty_optional_when_item_not_found2(): void
    {
        /** @psalm-var array<int, Person> */
        $people = [
            new Person('Nick', 10),
            new Person('Carl', 18),
            new Person('Jane', 25),
        ];

        $alternativePerson = new Person('', 0);

        $result = pipeline($people)
            ->findAnyKeyValue(fn () => false)
            ->orElse(
                Pair::from(-1, $alternativePerson)
            );

        $this->assertSame(-1, $result->key());
        $this->assertSame($alternativePerson, $result->value());
    }

    /** @test */
    public function it_return_value_optional_when_found(): void
    {
        /** @psalm-var list<string> $names */
        $names = [
            'Paul',
            'Marry',
            'Eric',
            'Jane',
            'Adam',
            'Brenda',
        ];

        $result = pipeline($names)
            ->findAnyValue(fn (string $name) => 'J' === $name[0])
            ->orElse('Not-Found');

        $this->assertSame('Jane', $result);
    }

    /** @test */
    public function it_return_alternative_optional_when_not_found(): void
    {
        /** @psalm-var list<string> $names */
        $names = [
            'Paul',
            'Marry',
            'Eric',
            'Jane',
            'Adam',
            'Brenda',
        ];

        $result = pipeline($names)
            ->findAnyValue(fn (string $name) => 'X' === $name[0])
            ->orElse('Not-Found');

        $this->assertSame('Not-Found', $result);
    }

    /** @test */
    public function it_return_empty_optional_when_not_found(): void
    {
        $names = [
            'Paul',
            'Marry',
            'Eric',
            'Jane',
            'Adam',
            'Brenda',
        ];

        $result = pipeline($names)
            ->findAnyValue(fn (string $name) => 'X' === $name[0]);

        $this->assertFalse($result->isPresent());
    }
}
