<?php

declare(strict_types=1);

namespace IteratorTools\Consumers;

use IteratorTools\IteratorPipeline;

/**
 * @psalm-return callable(IteratorPipeline<mixed, int>):int
 */
function int_sum(): callable
{
    return function (IteratorPipeline $pipeline): int {
        return $pipeline->reduce(
            0,
            fn (int $value, int $sum): int => $sum + $value
        );
    };
}

/**
 * @psalm-return callable(IteratorPipeline<mixed, float>):float
 */
function float_sum(): callable
{
    return function (IteratorPipeline $pipeline): float {
        return $pipeline->reduce(
            0.0,
            fn (float $value, float $sum): float => $sum + $value
        );
    };
}

/**
 * @psalm-return callable(IteratorPipeline<mixed, int|float>):float
 */
function float_average(): callable
{
    return function (IteratorPipeline $pipeline): float {
        $sum = 0.0;
        $count = 0;

        foreach ($pipeline as $number) {
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
 * @psalm-return callable(IteratorPipeline<K,V>): array<string,list<V>>
 */
function group_by(callable $callable): callable
{
    return function (IteratorPipeline $pipeline) use ($callable): array {
        $map = [];

        foreach ($pipeline as $key => $value) {
            $groupBy = $callable($value, $key);

            if (false === $groupBy) {
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
 * @psalm-return callable(IteratorPipeline<mixed, array<string, mixed>>): array<string, list<array<string, mixed>>>
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
 * @psalm-return callable(IteratorPipeline<mixed, string|\Stringable>):string
 */
function str_join(string $delimiter = ''): callable
{
    return function (IteratorPipeline $pipeline) use ($delimiter): string {
        $iterator = $pipeline->getIterator();
        $iterator->rewind();

        if (!$iterator->valid()) {
            return '';
        }

        $output = (string)$iterator->current();
        $iterator->next();

        while ($iterator->valid()) {
            $output .= $delimiter . (string)$iterator->current();
            $iterator->next();
        }

        return $output;
    };
}
