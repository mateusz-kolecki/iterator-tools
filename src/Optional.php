<?php

declare(strict_types=1);

namespace IteratorTools;

/**
 * @psalm-template T
 * @psalm-immutable
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
     */
    public static function from($value): self
    {
        if (null === $value) {
            return self::empty();
        }

        return new self($value);
    }

    /**
     * @psalm-param T $alternative
     * @psalm-return self<T>
     */
    public function orElse($alternative): self
    {
        $value = null !== $this->value
            ? $this->value
            : $alternative;

        return new self($value);
    }

    /**
     * @psalm-return T
     * @throws NotFoundException
     */
    public function get()
    {
        if (null === $this->value) {
            throw new NotFoundException();
        }

        return $this->value;
    }

    public function isPresent(): bool
    {
        return null !== $this->value;
    }
}
