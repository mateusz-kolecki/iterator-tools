<?php

declare(strict_types=1);

namespace IteratorTools\Iterator;

use Closure;
use Generator;
use Iterator;
use IteratorAggregate;
use UnexpectedValueException;

/**
 * @psalm-template K
 * @psalm-template V
 * @psalm-template RK
 * @psalm-template RV
 *
 * @template-implements IteratorAggregate<RK, RV>
 */
class ExtractingGenerator implements IteratorAggregate
{
    /** @psalm-var Iterator<K, V> */
    private Iterator $innerIterator;

    /** @psalm-var Closure(V, K):iterable<RK, RV> */
    private Closure $extractorCallback;

    /**
     * @psalm-param Iterator<K, V> $innerIterator
     * @psalm-param callable(V, K):iterable<RK, RV> $extractorCallback
     */
    public function __construct(Iterator $innerIterator, callable $extractorCallback)
    {
        $this->innerIterator = $innerIterator;
        $this->extractorCallback = Closure::fromCallable($extractorCallback);
    }

    /**
     * @psalm-return Generator<RK, RV>
     */
    public function getIterator(): Generator
    {
        $extractorCallback = $this->extractorCallback;

        foreach ($this->innerIterator as $key => $value) {
            $extracted = $extractorCallback($value, $key);

            /**
             * @psalm-suppress RedundantConditionGivenDocblockType
             * @psalm-suppress DocblockTypeContradiction
             */
            if (false === is_iterable($extracted)) {
                $type = is_object($extracted) ? get_class($extracted) : gettype($extracted);
                throw new UnexpectedValueException("Expected iterable type but {$type} returned from extracting callback");
            };

            yield from $extracted;
        }
    }
}
