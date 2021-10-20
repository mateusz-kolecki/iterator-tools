<?php

declare(strict_types=1);

namespace MK\IteratorTools;

use Exception;

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
            return $stream->reduce(0, function (int $value, int $sum): int {
                return $sum + $value;
            });
        };
    }

    /**
     * @psalm-return callable(IteratorStream<mixed, float>):float
     */
    public static function floatSum(): callable
    {
        return function (IteratorStream $stream) {
            return $stream->reduce(0.0, function (float $value, float $sum): float {
                return $sum + $value;
            });
        };
    }
}
