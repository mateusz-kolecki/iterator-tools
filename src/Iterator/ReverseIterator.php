<?php

declare(strict_types=1);

namespace MK\IteratorTools\Iterator;

use ArrayIterator;
use EmptyIterator;
use Iterator;
use Traversable;

/**
 * @psalm-template K
 * @psalm-template V
 *
 * @implements Iterator<K, V>
 */
class ReverseIterator implements Iterator
{
    /**
     * @psalm-var Iterator<K, array{K, V}>
     */
    private Iterator $traversable;

    /**
     * @psalm-var Iterator<int, array{K, V}>
     */
    private Iterator $reversed;

    /**
     * @psalm-param Traversable<K, V> $traversable
     */
    public function __construct(Traversable $traversable)
    {
        /*
         * There is no guarantee that incoming $traversable
         * will produce unique keys so we must save all keys
         * before doing iterator_to_array() and then then array_reverse().
         *
         * Example of non-unique keys scenario:
         *
         *   $traversable = new AppendIterator();
         *   $traversable->append(new ArrayIterator([1,2,3]));
         *   $traversable->append(new ArrayIterator([4,5,6]));
         *   iterator_to_array($traversable, true); // [4, 5, 6]
         */

        $this->traversable = new CallbackMapIterator(
            iterator_from($traversable),
            /**
             * @psalm-param V $value
             * @psalm-param K $key
             */
            function ($value, $key) {
                return [$key, $value];
            }
        );

        $this->reversed = new EmptyIterator();
    }

    public function next(): void
    {
        $this->reversed->next();
    }

    public function valid(): bool
    {
        return $this->reversed->valid();
    }

    public function key()
    {
        return $this->reversed->current()[0];
    }

    public function current()
    {
        return $this->reversed->current()[1];
    }

    public function rewind(): void
    {
        if ($this->reversed instanceof EmptyIterator) {
            $this->reversed = new ArrayIterator(
                array_reverse(
                    iterator_to_array($this->traversable, false)
                )
            );
        }

        $this->reversed->rewind();
    }
}
