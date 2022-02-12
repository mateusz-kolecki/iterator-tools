<?php

declare(strict_types=1);

namespace MK\IteratorTools\Iterator;

use Iterator;

/**
 * @psalm-template K
 * @psalm-template V
 * @psalm-template R
 *
 * @template-implements Iterator<K,R>
 */
abstract class MapIterator implements Iterator
{
    /**
     * @psalm-var Iterator<K,V>
     */
    private Iterator $innerIterator;

    /**
     * @psalm-param Iterator<K,V> $iterable
     */
    public function __construct(Iterator $innerIterator)
    {
        $this->innerIterator = $innerIterator;
    }

    public function next(): void
    {
        $this->innerIterator->next();
    }

    public function valid(): bool
    {
        return $this->innerIterator->valid();
    }

    /**
     * @psalm-return K
     */
    public function key()
    {
        return $this->innerIterator->key();
    }

    public function rewind(): void
    {
        $this->innerIterator->rewind();
    }

    /**
     * @psalm-return R
     */
    public function current()
    {
        return $this->mapValue(
            $this->innerIterator->current(),
            $this->innerIterator->key(),
        );
    }

    /**
     * @psalm-param V $value
     * @psalm-param K $key
     * @psalm-return R
     */
    abstract public function mapValue($value, $key);
}
