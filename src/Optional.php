<?php

declare(strict_types=1);

namespace IteratorTools;

use InvalidArgumentException;

/**
 * @psalm-template T
 */
class Optional
{
    /** @psalm-var Optional<null> */
    private static ?Optional $empty = null;

    /**
     * @psalm-var ?T
     */
    private $value;

    /**
     * @psalm-param ?T $value
     */
    private function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @psalm-template V
     *
     * @psalm-return self<V>
     */
    public static function empty(): self
    {
        if (null === self::$empty) {
            self::$empty = new self(null);
        }

        return self::$empty;
    }

    /**
     * @psalm-template V
     *
     * @psalm-param ?V $value
     * @psalm-return self<V>
     *
     * @throws InvalidArgumentException
     */
    public static function from($value): self
    {
        if (null === $value) {
            throw new InvalidArgumentException("Non-null value expected bug NULL given");
        }

        return new self($value);
    }

    /**
     * @psalm-template V
     *
     * @psalm-param ?V $value
     * @psalm-return self<V>
     */
    public static function fromNullable($value): self
    {
        if (null === $value) {
            return self::empty();
        }

        return new self($value);
    }

    /**
     * @psalm-param T $other
     * @psalm-return T
     */
    public function orElse($other)
    {
        return $this->value ?? $other;
    }

    /**
     * @psalm-param pure-callable():T $supplier
     * @psalm-return T
     */
    public function orElseGet(callable $supplier)
    {
        return $this->value ?? $supplier();
    }

    /**
     * @psalm-return T
     * @throws NotFoundException
     */
    public function getOrThrow()
    {
        if (null === $this->value) {
            throw new NotFoundException("No value present");
        }

        return $this->value;
    }

    public function isPresent(): bool
    {
        return null !== $this->value;
    }

    /**
     * @psalm-template U
     *
     * @psalm-param pure-callable(T): U $mapper
     *
     * @psalm-return Optional<U>
     */
    public function map(callable $mapper): self
    {
        if (null === $this->value) {
            return self::empty();
        } else {
            return self::fromNullable(
                $mapper($this->value)
            );
        }
    }

    /**
     * @psalm-return IteratorPipeline<array-key, T>
     */
    public function pipeline(): IteratorPipeline
    {
        if (null === $this->value) {
            return IteratorPipeline::empty();
        } else {
            return IteratorPipeline::from([$this->value]);
        }
    }
}
