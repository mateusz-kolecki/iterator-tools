<?php

declare(strict_types=1);

namespace IteratorTools\Iterator;

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
     * @psalm-var callable(V, K):R
     */
    private $callback;

    /**
     * @psalm-param Iterator<K, V> $traversable
     * @psalm-param callable(V, K):R $callback
     */
    public function __construct(Iterator $innerIterator, callable $callback)
    {
        parent::__construct($innerIterator);
        $this->callback = $callback;
    }

    public function mapValue($value, $key)
    {
        return ($this->callback)($value, $key);
    }
}
