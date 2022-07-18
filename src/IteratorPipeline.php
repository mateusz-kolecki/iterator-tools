<?php

declare(strict_types=1);

namespace IteratorTools;

use AppendIterator;
use CallbackFilterIterator;
use EmptyIterator;
use Iterator;
use IteratorAggregate;
use IteratorTools\Iterator\CallbackMapIterator;
use IteratorTools\Iterator\ExtractingGenerator;
use IteratorTools\Iterator\ReverseIterator;
use LimitIterator;

use function iterator_to_array;
use function IteratorTools\Iterator\iterator;

/**
 * @psalm-template K
 * @psalm-template V
 *
 * @template-implements IteratorAggregate<K,V>
 */
class IteratorPipeline implements IteratorAggregate
{
    /**
     * @psalm-var Iterator<K,V>
     */
    protected Iterator $innerIterator;

    /**
     * @psalm-param Iterator<K,V> $iterator
     */
    private function __construct(Iterator $iterator)
    {
        $this->innerIterator = $iterator;
    }

    /**
     * @psalm-return self<empty,empty>
     */
    public static function empty(): self
    {
        return new self(new EmptyIterator());
    }

    /**
     * @psalm-template TKey
     * @psalm-template TValue
     *
     * @psalm-param  iterable<TKey,TValue> $iterable
     * @psalm-return self<TKey,TValue>
     */
    public static function from(iterable $iterable): self
    {
        return new self(iterator($iterable));
    }

    /**
     * @psalm-template AV
     * @psalm-template AK
     *
     * @psalm-param  iterable<AK,AV> $iterable
     * @psalm-return self<K|AK,V|AV>
     */
    public function append(iterable $iterable): self
    {
        $appendIterator = new AppendIterator();

        $appendIterator->append($this->innerIterator);
        $appendIterator->append(iterator($iterable));

        return new self($appendIterator);
    }

    /**
     * @psalm-param  callable(V,K,Iterator<K,V>):bool $callback
     * @psalm-return self<K,V>
     */
    public function filter(callable $callback): self
    {
        return new self(
            new CallbackFilterIterator(
                $this->innerIterator,
                $callback
            )
        );
    }

    /**
     * @psalm-template R
     *
     * @psalm-param  callable(V,K):R $callback
     * @psalm-return self<K,R>
     */
    public function map(callable $callback): self
    {
        $mapIterator = new CallbackMapIterator(
            $this->innerIterator,
            $callback
        );

        return new self($mapIterator);
    }

    /**
     * @psalm-template R
     *
     * @psalm-param callable(V):R $callback
     * @psalm-return self<K,R>
     */
    public function mapValue(callable $callback): self
    {
        $mapIterator = new CallbackMapIterator(
            $this->innerIterator,
            /**
             * @psalm-param V $value
             */
            function ($value) use ($callback) {
                return $callback($value);
            }
        );

        return new self($mapIterator);
    }

    /**
     * @psalm-template S
     *
     * @psalm-param S $accumulator
     * @psalm-param callable(V,S,K):S $callback
     *
     * @psalm-return S
     */
    public function reduce($accumulator, callable $callback)
    {
        foreach ($this->innerIterator as $key => $value) {
            $accumulator = $callback($value, $accumulator, $key);
        }

        return $accumulator;
    }

    /**
     * @psalm-return self<K,V>
     */
    public function reverse(): self
    {
        return new self(
            new ReverseIterator(
                $this->innerIterator
            )
        );
    }

    /**
     * @psalm-return self<K,V>
     */
    public function limit(int $count): self
    {
        return new self(
            new LimitIterator($this->innerIterator, 0, $count)
        );
    }

    /**
     * @psalm-return self<K,V>
     */
    public function skip(int $count): self
    {
        return new self(
            new LimitIterator($this->innerIterator, $count)
        );
    }

    /**
     * @psalm-param callable(V,K,Iterator<K,V>):bool $predicate
     * @psalm-return Optional<Pair<K,V>>
     */
    public function findAnyKeyValue(callable $predicate): Optional
    {
        foreach ($this->filter($predicate) as $k => $v) {
            return Optional::fromNullable(
                Pair::from($k, $v)
            );
        }

        return Optional::empty();
    }

    /**
     * @psalm-param callable(V,K,Iterator<K,V>):bool $predicate
     * @psalm-return Optional<V>
     */
    public function findAnyValue(callable $predicate): Optional
    {
        $result = $this->findAnyKeyValue($predicate);

        try {
            $value = $result->getOrThrow()->value();
        } catch (NotFoundException $e) {
            return Optional::empty();
        }

        return Optional::fromNullable($value);
    }

    /**
     * @psalm-template RK
     * @psalm-template RV
     *
     * @psalm-param callable(V, K):iterable<RK, RV> $extractorCallback
     * @psalm-return self<RK,RV>
     */
    public function extract(callable $extractorCallback): self
    {
        $generator = new ExtractingGenerator(
            $this->innerIterator,
            $extractorCallback
        );

        return new self($generator->getIterator());
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
     * @psalm-return Iterator<K,V>
     */
    public function getIterator(): Iterator
    {
        return $this->innerIterator;
    }

    /**
     * @psalm-return array<K,V>
     */
    public function toArrayPreserveKeys(): array
    {
        return iterator_to_array($this->innerIterator, true);
    }

    /**
     * @psalm-return list<V>
     */
    public function toArray(): array
    {
        return iterator_to_array($this->innerIterator, false);
    }
}
