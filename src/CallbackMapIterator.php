<?php

declare(strict_types=1);

namespace MK\IteratorTools;

use Iterator;
use Traversable;

/**
 * @psalm-template K
 * @psalm-template V
 * @psalm-template R
 *
 * @template-implements Iterator<K, R>
 */
class CallbackMapIterator implements Iterator
{
    use IteratorConvertingTrait;

    /**
     * @psalm-var callable(V, K, Iterator<K,V>):R
     */
    private $callback;

    /**
     * @psalm-var Iterator<K, V>
     */
    private Iterator $traversable;

    /**
     * @psalm-param Traversable<K, V> $traversable
     * @psalm-param callable(V, K, Iterator<K, V>):R $callback
     */
    public function __construct(Traversable $traversable, callable $callback)
    {
        $this->traversable = self::toIterator($traversable);
        $this->callback = $callback;
    }

    public function next(): void
    {
        $this->traversable->next();
    }

    public function valid(): bool
    {
        return $this->traversable->valid();
    }

    /**
     * @psalm-return K
     */
    public function key()
    {
        return $this->traversable->key();
    }

    public function rewind(): void
    {
        $this->traversable->rewind();
    }

    /**
     * @psalm-return R
     */
    public function current()
    {
        $value = $this->traversable->current();
        $key = $this->traversable->key();

        return ($this->callback)($value, $key, $this->traversable);
    }
}
