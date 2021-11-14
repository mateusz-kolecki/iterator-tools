<?php

declare(strict_types=1);

namespace MK\IteratorTools;

use Exception;
use function implode;

final class Consumers
{
    private function __construct()
    {
        throw new Exception('No instances');
    }

    /**
     * @psalm-return callable(IteratorStream<mixed, int>):int
     */
    public static function intSum(): callable
    {
        return function (IteratorStream $stream) {
            return $stream->reduce(
                0,
                fn (int $value, int $sum): int => $sum + $value
            );
        };
    }

    /**
     * @psalm-return callable(IteratorStream<mixed, float>):float
     */
    public static function floatSum(): callable
    {
        return function (IteratorStream $stream) {
            return $stream->reduce(
                0.0,
                fn (float $value, float $sum): float => $sum + $value
            );
        };
    }

    /**
     * @psalm-return callable(IteratorStream<mixed, int|float>):float
     */
    public static function average(): callable
    {
        return function (IteratorStream $stream) {
            $sum = 0.0;
            $count = 0;

            foreach ($stream as $number) {
                $sum += $number;
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
    public static function groupBy(callable $callable): callable
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
    public static function groupByArrKey(string $groupKey): callable
    {
        return Consumers::groupBy(
            /**
             * @psalm-param array<string, mixed> $value
             */
            fn (array $value) => array_key_exists($groupKey, $value) ? (string)$value[$groupKey] : false
        );
    }

    /**
     * @psalm-return callable(IteratorStream<mixed, string>):string
     */
    public static function join(string $delimiter = ''): callable
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
}
