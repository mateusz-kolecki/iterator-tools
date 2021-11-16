<?php

declare(strict_types=1);

namespace MK\IteratorTools\Consumers;

use MK\IteratorTools\IteratorStream;

/**
 * @psalm-return callable(IteratorStream<mixed, int>):int
 */
function int_sum(): callable
{
    return function (IteratorStream $stream): int {
        return $stream->reduce(
            0,
            fn (int $value, int $sum): int => $sum + $value
        );
    };
}

/**
 * @psalm-return callable(IteratorStream<mixed, float>):float
 */
function float_sum(): callable
{
    return function (IteratorStream $stream): float {
        return $stream->reduce(
            0.0,
            fn (float $value, float $sum): float => $sum + $value
        );
    };
}

/**
 * @psalm-return callable(IteratorStream<mixed, int|float>):float
 */
function float_average(): callable
{
    return function (IteratorStream $stream): float {
        $sum = 0.0;
        $count = 0;

        foreach ($stream as $number) {
            $sum += (float)$number;
            $count += 1;
        }

        return $sum / $count;
    };
}

/**
 * @psalm-template K
 * @psalm-template V
 *
 * @psalm-param callable(V, K):(string|false) $callable
 *
 * @psalm-return callable(IteratorStream<K,V>): array<string,list<V>>
 */
function group_by(callable $callable): callable
{
    return function (IteratorStream $stream) use ($callable): array {
        $map = [];

        foreach ($stream as $key => $value) {
            $groupBy = $callable($value, $key);

            if ($groupBy === false) {
                continue;
            }

            if (!isset($map[$groupBy])) {
                $map[$groupBy] = [];
            }

            $map[$groupBy][] = $value;
        }

        return $map;
    };
}

/**
 * @psalm-return callable(IteratorStream<mixed, array<string, mixed>>): array<string, list<array<string, mixed>>>
 */
function group_by_arr_key(string $groupKey): callable
{
    return group_by(
        /**
         * @psalm-param array<string, mixed> $value
         * @psalm-return false|string
         */
        function (array $value) use ($groupKey) {
            if (!array_key_exists($groupKey, $value)) {
                return false;
            }

            return (string)$value[$groupKey];
        }
    );
}

/**
 * @psalm-return callable(IteratorStream<mixed, string>):string
 */
function str_join(string $delimiter = ''): callable
{
    return function (IteratorStream $stream) use ($delimiter): string {
        $iterator = $stream->getIterator();
        $iterator->rewind();

        if (!$iterator->valid()) {
            return '';
        }

        $output = $iterator->current();
        $iterator->next();

        while ($iterator->valid()) {
            $output .= $delimiter . $iterator->current();
            $iterator->next();
        }

        return $output;
    };
}
