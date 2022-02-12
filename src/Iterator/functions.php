<?php

declare(strict_types=1);

namespace MK\IteratorTools\Iterator;

use ArrayIterator;
use Iterator;
use IteratorAggregate;
use IteratorIterator;
use MK\IteratorTools\IteratorStream;
use function is_array;

/**
 * @psalm-template K
 * @psalm-template V
 *
 * @psalm-param  iterable<K,V> $iterable
 * @psalm-return Iterator<K,V>
 */
function iterator(iterable $iterable): Iterator
{
    if (is_array($iterable)) {
        return new ArrayIterator($iterable);
    }

    if ($iterable instanceof Iterator) {
        return $iterable;
    }

    if ($iterable instanceof IteratorAggregate) {
        return iterator($iterable->getIterator());
    }

    return new IteratorIterator($iterable);
}

/**
 * @psalm-template K
 * @psalm-template V
 *
 * @psalm-param iterable<K,V> $iterable
 * @psalm-return IteratorStream<K,V>
 */
function stream(iterable $iterable): IteratorStream
{
    if ([] === $iterable) {
        return IteratorStream::empty();
    }

    return IteratorStream::from($iterable);
}
