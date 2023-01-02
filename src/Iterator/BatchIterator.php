<?php

declare(strict_types=1);

namespace IteratorTools\Iterator;

use InvalidArgumentException;
use Iterator;

use function count;

/**
 * @psalm-template V
 *
 * @implements Iterator<int, list<V>>
 */
class BatchIterator implements Iterator
{
    /** @psalm-var Iterator<mixed, V> $original */
    private Iterator $original;

    /** @psalm-var list<V> */
    private array $batch;

    private int $batchSize;

    private int $batchIndex = 0;

    /**
     * @psalm-param Iterator<mixed, V> $original
     */
    public function __construct(Iterator $original, int $batchSize)
    {
        if (0 >= $batchSize) {
            throw new InvalidArgumentException("Batch size must be positive but {$batchSize} given");
        }

        $this->original = $original;
        $this->batchSize = $batchSize;
        $this->batch = [];
    }

    public function next(): void
    {
        $this->loadBatch();
        $this->batchIndex += 1;
    }

    public function valid(): bool
    {
        return !empty($this->batch);
    }

    public function key()
    {
        return $this->batchIndex;
    }

    public function current()
    {
        return $this->batch;
    }

    public function rewind(): void
    {
        $this->original->rewind();
        $this->loadBatch();
        $this->batchIndex = 0;
    }

    private function loadBatch(): void
    {
        $this->batch = [];

        while (
            $this->original->valid() &&
            $this->batchSize > count($this->batch)
        ) {
            $this->batch[] = $this->original->current();
            $this->original->next();
        }
    }
}
