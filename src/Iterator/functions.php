<?php

declare(strict_types=1);

namespace IteratorTools\Iterator;

use ArrayIterator;
use Iterator;
use IteratorAggregate;
use IteratorIterator;
use IteratorTools\IteratorPipeline;

use IteratorTools\Pipeline;

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
 * @psalm-template TKey
 * @psalm-template TValue
 *
 * @psalm-param iterable<TKey,TValue> $iterable
 * @psalm-return Pipeline<TKey,TValue>
 */
function pipeline(iterable $iterable = []): Pipeline
{
    if ([] === $iterable) {
        return IteratorPipeline::empty();
    }

    return IteratorPipeline::from($iterable);
}
