<?php

declare(strict_types=1);

namespace IteratorTools;

use Iterator;
use IteratorAggregate;

/**
 * @psalm-template K
 * @psalm-template V
 *
 * @template-extends IteratorAggregate<K,V>
 */
interface Pipeline
{
    /**
     * @psalm-template AV
     * @psalm-template AK
     *
     * @psalm-param  iterable<AK,AV> $iterable
     * @psalm-return Pipeline<K|AK,V|AV>
     */
    public function append(iterable $iterable): Pipeline;

    /**
     * @psalm-param  callable(V,K,Iterator<K,V>):bool $callback
     * @psalm-return Pipeline<K,V>
     */
    public function filter(callable $callback): Pipeline;

    /**
     * @psalm-template R
     *
     * @psalm-param  callable(V,K):R $callback
     * @psalm-return Pipeline<K,R>
     */
    public function map(callable $callback): Pipeline;

    /**
     * @psalm-template R
     *
     * @psalm-param callable(V):R $callback
     * @psalm-return Pipeline<K,R>
     */
    public function mapValue(callable $callback): Pipeline;

    /**
     * @psalm-template S
     *
     * @psalm-param S $accumulator
     * @psalm-param callable(V,S,K):S $callback
     *
     * @psalm-return S
     */
    public function reduce($accumulator, callable $callback);

    /**
     * @psalm-return Pipeline<K,V>
     */
    public function reverse(): Pipeline;

    /**
     * @psalm-return Pipeline<K,V>
     */
    public function limit(int $count): Pipeline;

    /**
     * @psalm-return Pipeline<int, list<Pair<K, V>>>
     */
    public function batchKeysAndValues(int $batchSize): Pipeline;

    /**
     * @psalm-return Pipeline<int, list<V>>
     */
    public function batchValues(int $batchSize): Pipeline;

    /**
     * @psalm-return Pipeline<K,V>
     */
    public function skip(int $count): Pipeline;

    /**
     * @psalm-param callable(V,K,Iterator<K,V>):bool $predicate
     * @psalm-return Optional<Pair<K,V>>
     */
    public function findAnyKeyAndValue(callable $predicate): Optional;

    /**
     * @psalm-param callable(V,K,Iterator<K,V>):bool $predicate
     * @psalm-return Optional<V>
     */
    public function findAnyValue(callable $predicate): Optional;

    /**
     * @psalm-template RK
     * @psalm-template RV
     *
     * @psalm-param callable(V, K):iterable<RK, RV> $extractorCallback
     * @psalm-return Pipeline<RK,RV>
     */
    public function extract(callable $extractorCallback): Pipeline;

    /**
     * @template R
     *
     * @psalm-param callable(Pipeline<K,V>):R $consumer
     * @psalm-return R
     */
    public function consume(callable $consumer);

    /**
     * @psalm-return Iterator<K,V>
     */
    public function getIterator(): Iterator;

    /**
     * @psalm-return array<K,V>
     */
    public function toArrayPreserveKeys(): array;

    /**
     * @psalm-return list<V>
     */
    public function toArray(): array;
}
