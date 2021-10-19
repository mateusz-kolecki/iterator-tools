<?php

declare(strict_types=1);

namespace MK\IteratorTools;

use ArrayIterator;
use Closure;
use EmptyIterator;
use PHPUnit\Framework\TestCase;
use function iterator_to_array;

class CallbackMapIteratorTest extends TestCase
{
    /** @test */
    function it_should_result_in_empty_set_when_inner_iterator_do_not_return_any_element(): void
    {
        $map = new CallbackMapIterator(
            new EmptyIterator,
            function (int $i) {
                return $i;
            }
        );

        $result = iterator_to_array($map);

        $this->assertEmpty($result);
    }

    /**
     * @test
     * @dataProvider differentCountItemsProvider
     *
     * @psalm-param list<int> $items
     */
    function it_returns_same_number_of_elements_when_inner_iterator_is_not_empty(
        array $items
    ): void {
        $innerIterator = new ArrayIterator($items);

        $map = new CallbackMapIterator(
            $innerIterator,
            function (int $v) {
                return $v;
            }
        );

        $result = iterator_to_array($map);

        $this->assertCount(count($items), $result);
    }

    /**
     * @psalm-return list<list<list<int>>>
     */
    function differentCountItemsProvider(): array
    {
        return [
            [[1]],
            [[1, 2, 3, 4]],
            [array_fill(0, 100, 1)],
        ];
    }

    /** @test */
    function it_should_return_mapped_elements(): void
    {
        $innerIterator = new ArrayIterator([1, 2, 3, 4, 5]);

        $callback = function (int $num): int {
            return $num * 2;
        };

        $map = new CallbackMapIterator($innerIterator, $callback);

        $result = iterator_to_array($map);

        $this->assertSame([2, 4, 6, 8, 10], $result);
    }

    /** @test */
    function it_preserves_original_keys(): void
    {
        $innerIterator = new ArrayIterator([
            'foo' => 10,
            'bar' => 20,
        ]);

        $callback = function (int $num): int {
            return $num * 2;
        };

        $map = new CallbackMapIterator($innerIterator, $callback);

        $result = iterator_to_array($map);

        $expected = [
            'foo' => 20,
            'bar' => 40,
        ];

        $this->assertSame($expected, $result);
    }
}
