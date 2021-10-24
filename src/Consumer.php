<?php

declare(strict_types=1);

namespace MK\IteratorTools;

use Exception;
use Traversable;

final class Consumer
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
     * @psalm-return callable(IteratorStream<mixed, float>):float
     */
    public static function floatAverage(): callable
    {
        return function (IteratorStream $floatStream) {
            $sum = 0.0;
            $count = 0;

            foreach ($floatStream as $floatValue) {
                $sum += $floatValue;
                $count += 1;
            }

            return $sum / $count;
        };
    }

    /**
     * @psalm-template K
     * @psalm-template V
     *
     * @psalm-param callable(V, K):string $callable
     *
     * @psalm-return callable(IteratorStream<K,V>): array<string,list<V>>
     */
    public static function groupBy(callable $callable): callable
    {
        return function (IteratorStream $stream) use ($callable): array {
            $map = [];

            foreach ($stream as $key => $value) {
                $groupBy = $callable($value, $key);

                if (!isset($map[$groupBy])) {
                    $map[$groupBy] = [];
                }

                $map[$groupBy][] = $value;
            }

            return $map;
        };
    }
}
