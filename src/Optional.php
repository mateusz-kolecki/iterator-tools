<?php

declare(strict_types=1);

namespace IteratorTools;

/**
 * @psalm-template T
 */
class Optional
{
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
        return new self(null);
    }

    /**
     * @psalm-template V
     *
     * @psalm-param ?V $value
     * @psalm-return self<V>
     */
    public static function from($value): self
    {
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

        return self::from($value);
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
