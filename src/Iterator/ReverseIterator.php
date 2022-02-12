<?php

declare(strict_types=1);

namespace MK\IteratorTools\Iterator;

use EmptyIterator;
use Iterator;
use function array_reverse;

/**
 * @psalm-template K
 * @psalm-template V
 *
 * @implements Iterator<K, V>
 */
class ReverseIterator implements Iterator
{
    /** @psalm-var Iterator<K, V> $original */
    private Iterator $original;

    /** @psalm-var Iterator<K> */
    private Iterator $keys;

    /** @psalm-var Iterator<V> */
    private Iterator $values;

    private bool $initialized = false;

    /**
     * @psalm-param Iterator<K, V> $original
     */
    public function __construct(Iterator $original)
    {
        $this->original = $original;
        $this->keys = new EmptyIterator();
        $this->values = new EmptyIterator();
    }

    public function next(): void
    {
        $this->keys->next();
        $this->values->next();
    }

    public function valid(): bool
    {
        return $this->keys->valid()
            && $this->values->valid();
    }

    public function key()
    {
        return $this->keys->current();
    }

    public function current()
    {
        return $this->values->current();
    }

    public function rewind(): void
    {
        if (false === $this->initialized) {
            $this->original->rewind();
            $this->revert();
            $this->initialized = true;
        }

        $this->keys->rewind();
        $this->values->rewind();
    }

    private function revert(): void
    {
        $keys = [];
        $values = [];

        foreach ($this->original as $key => $value) {
            $keys[] = $key;
            $values[] = $value;
        }

        $this->keys = iterator(array_reverse($keys));
        $this->values = iterator(array_reverse($values));
    }
}
