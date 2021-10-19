<?php

declare(strict_types=1);

namespace MK\IteratorTools;

use ArrayIterator;
use Iterator;
use IteratorIterator;

trait IteratorConvertingTrait
{
    /**
     * @psalm-template TKey
     * @psalm-template TValue
     *
     * @psalm-param  iterable<TKey, TValue> $iterable
     * @psalm-return Iterator<TKey, TValue>
     */
    private static function toIterator(iterable $iterable): Iterator
    {
        if (is_array($iterable)) {
            return new ArrayIterator($iterable);
        }

        if ($iterable instanceof Iterator) {
            return $iterable;
        }

        return new IteratorIterator($iterable);
    }
}
