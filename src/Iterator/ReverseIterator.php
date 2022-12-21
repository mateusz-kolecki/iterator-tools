<?php

declare(strict_types=1);

namespace IteratorTools\Iterator;

use EmptyIterator;
use Iterator;
use IteratorTools\Pair;

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

    /** @psalm-var Iterator<Pair<K, V>> */
    private Iterator $reverted;

    private bool $initialized = false;

    /**
     * @psalm-param Iterator<K, V> $original
     */
    public function __construct(Iterator $original)
    {
        $this->original = $original;
        $this->reverted = new EmptyIterator();
    }

    public function next(): void
    {
        $this->reverted->next();
    }

    public function valid(): bool
    {
        return $this->reverted->valid();
    }

    public function key()
    {
        return $this->reverted->current()->key();
    }

    public function current()
    {
        return $this->reverted->current()->value();
    }

    public function rewind(): void
    {
        if (false === $this->initialized) {
            $this->revert();
            $this->initialized = true;
        }

        $this->reverted->rewind();
    }

    private function revert(): void
    {
        $pairs = [];

        foreach ($this->original as $key => $value) {
            $pairs[] = Pair::from($key, $value);
        }

        $this->reverted = iterator(array_reverse($pairs));
    }
}
