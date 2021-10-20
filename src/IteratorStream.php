<?php

declare(strict_types=1);

namespace MK\IteratorTools;

use AppendIterator;
use CallbackFilterIterator;
use EmptyIterator;
use Iterator;
use IteratorAggregate;
use LimitIterator;
use Traversable;
use function iterator_to_array;

/**
 * @psalm-template K
 * @psalm-template V
 */
class IteratorStream implements IteratorAggregate
{
    use IteratorConvertingTrait;

    /**
     * @psalm-var Traversable<K, V>
     */
    protected Traversable $innerTraversable;

    /**
     * @psalm-param Traversable<K, V> $traversable
     */
    protected function __construct(Traversable $traversable)
    {
        $this->innerTraversable = $traversable;
    }

    /**
     * @psalm-return self<empty, empty>
     */
    public static function empty(): self
    {
        return new self(new EmptyIterator());
    }

    /**
     * @psalm-template TKey
     * @psalm-template TValue
     *
     * @psalm-param  iterable<TKey, TValue> $iterable
     * @psalm-return self<TKey, TValue>
     */
    public static function from(iterable $iterable): self
    {
        return new self(self::toIterator($iterable));
    }

    /**
     * @psalm-template AV
     * @psalm-template AK
     *
     * @psalm-param  iterable<AK, AV> $iterable
     * @psalm-return self<K|AK, V|AV>
     */
    public function append(iterable $iterable): self
    {
        $appendIterator = new AppendIterator();

        $appendIterator->append(self::toIterator($this->innerTraversable));
        $appendIterator->append(self::toIterator($iterable));

        return self::from($appendIterator);
    }

    /**
     * @psalm-param  callable(V, K, Iterator<K, V>):bool $callback
     * @psalm-return self<K, V>
     */
    public function filter(callable $callback): self
    {
        return self::from(
            new CallbackFilterIterator(
                self::toIterator($this->innerTraversable),
                $callback
            )
        );
    }

    /**
     * @psalm-template R
     *
     * @psalm-param  callable(V, K, Iterator<K, V>):R $callback
     * @psalm-return self<K, R>
     */
    public function map(callable $callback): self
    {
        /** @psalm-var Iterator<K, R> $mapIterator */
        $mapIterator = new CallbackMapIterator(
            $this->innerTraversable,
            $callback
        );

        return self::from($mapIterator);
    }

    /**
     * @psalm-template R
     *
     * @psalm-param callable(V):R $callback
     * @psalm-return self<K,R>
     */
    public function mapValue(callable $callback): self
    {
        /** @psalm-var Iterator<K, R> $mapIterator */
        $mapIterator = new CallbackMapIterator(
            $this->innerTraversable,
            /**
             * @psalm-param V $value
             */
            function ($value) use ($callback) {
                return $callback($value);
            }
        );

        return self::from($mapIterator);
    }

    /**
     * @psalm-template S
     *
     * @psalm-param S $accumulator
     * @psalm-param callable(V, S, K):S $callback
     *
     * @psalm-return S
     */
    public function reduce($accumulator, callable $callback)
    {
        foreach ($this->innerTraversable as $key => $value) {
            $accumulator = $callback($value, $accumulator, $key);
        }

        return $accumulator;
    }

    /**
     * @psalm-return self<K, V>
     */
    public function reverse(): self
    {
        return self::from(
            new ReverseIterator(
                $this->innerTraversable
            )
        );
    }

    /**
     * @psalm-return self<K,V>
     */
    public function limit(int $count): self
    {
        return self::from(
            new LimitIterator(
                self::toIterator($this->innerTraversable),
                0,
                $count
            )
        );
    }

    /**
     * @psalm-return self<K,V>
     */
    public function skip(int $count): self
    {
        return self::from(
            new LimitIterator(
                self::toIterator($this->innerTraversable),
                $count
            )
        );
    }

    /**
     * @template R
     *
     * @psalm-param callable(self<K,V>):R $consumer
     * @psalm-return R
     */
    public function consume(callable $consumer)
    {
        return $consumer($this);
    }

    /**
     * @psalm-return Iterator<K, V>
     */
    public function getIterator(): Iterator
    {
        return self::toIterator($this->innerTraversable);
    }

    /**
     * @psalm-return array<K, V>
     */
    public function toArrayPreserveKeys(): array
    {
        return iterator_to_array($this->innerTraversable, true);
    }

    /**
     * @psalm-return list<V>
     */
    public function toArray(): array
    {
        return iterator_to_array($this->innerTraversable, false);
    }
}
