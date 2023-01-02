<?php

declare(strict_types=1);

namespace IteratorTools\Consumers;

use IteratorTools\NotFoundException;
use IteratorTools\Pipeline;
use stdClass;

/**
 * @psalm-return callable(Pipeline<mixed, int>):int
 */
function int_sum(): callable
{
    return function (Pipeline $pipeline): int {
        return $pipeline->reduce(
            0,
            fn (int $value, int $sum): int => $sum + $value
        );
    };
}

/**
 * @psalm-return callable(Pipeline<mixed, float>):float
 */
function float_sum(): callable
{
    return function (Pipeline $pipeline): float {
        $s = 0.0;

        foreach ($pipeline->getIterator() as $i) {
            $s += $i;
        }

        return $s;
    };
}

/**
 * @psalm-return callable(Pipeline<mixed, int|float>):float
 */
function float_average(): callable
{
    return function (Pipeline $pipeline): float {
        $sum = 0.0;
        $count = 0;

        foreach ($pipeline->getIterator() as $number) {
            $sum += (float)$number;
            $count += 1;
        }

        return $sum / $count;
    };
}

/**
 * @psalm-return callable(Pipeline<mixed, int|float>):object{min: float, max: float}
 */
function float_min_max(): callable
{
    return function (Pipeline $pipeline): object {
        /** @var ?float $max */
        $max = null;

        /** @var ?float $min */
        $min = null;

        foreach ($pipeline->getIterator() as $number) {
            if (null === $max || $max < $number) {
                $max = $number;
            }

            if (null === $min || $min > $number) {
                $min = $number;
            }
        }

        if (null === $max) {
            throw new NotFoundException("No elements in pipeline");
        }

        $minMax = new stdClass();
        $minMax->min = $min;
        $minMax->max = $max;

        return $minMax;
    };
}

/**
 * @psalm-return callable(Pipeline<mixed, int|float>):float
 */
function float_min(): callable
{
    return function (Pipeline $pipeline): float {
        $minMax = float_min_max();
        return $minMax($pipeline)->min;
    };
}

/**
 * @psalm-return callable(Pipeline<mixed, int|float>):float
 */
function float_max(): callable
{
    return function (Pipeline $pipeline): float {
        $minMax = float_min_max();
        return $minMax($pipeline)->max;
    };
}


/**
 * @psalm-template K
 * @psalm-template V
 *
 * @psalm-param callable(V, K):(string|false) $callable
 *
 * @psalm-return callable(Pipeline<K,V>): array<string,list<V>>
 */
function group_by(callable $callable): callable
{
    return function (Pipeline $pipeline) use ($callable): array {
        $map = [];

        foreach ($pipeline->getIterator() as $key => $value) {
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
 * @psalm-return callable(Pipeline<mixed, array<string, mixed>>): array<string, list<array<string, mixed>>>
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
 * @psalm-return callable(Pipeline<mixed, string|\Stringable>):string
 */
function str_join(string $delimiter = ''): callable
{
    return function (Pipeline $pipeline) use ($delimiter): string {
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
