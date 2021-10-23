<?php

declare(strict_types=1);

namespace MK\IteratorTools;

use Iterator;

/**
 * @psalm-template K
 * @psalm-template V
 * @psalm-template R
 *
 * @template-extends MapIterator<K,V,R>
 */
class CallbackMapIterator extends MapIterator
{
    /**
     * @psalm-var callable(V, K, Iterator<K,V>):R
     */
    private $callback;

    /**
     * @psalm-param Iterator<K, V> $traversable
     * @psalm-param callable(V, K, Iterator<K, V>):R $callback
     */
    public function __construct(Iterator $innerIterator, callable $callback)
    {
        parent::__construct($innerIterator);
        $this->callback = $callback;
    }

    /**
     * @psalm-return R
     */
    public function current()
    {
        $value = $this->innerIterator->current();
        $key = $this->innerIterator->key();

        return ($this->callback)($value, $key, $this->innerIterator);
    }
}
