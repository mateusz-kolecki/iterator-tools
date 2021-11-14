<?php

declare(strict_types=1);

namespace MK\IteratorTools;

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
     * @psalm-return self<V>
     */
    public static function empty(): self
    {
        return new self(null);
    }

    /**
     * @psalm-template V
     * @psalm-param V $value
     * @psalm-return self<V>
     */
    public static function from($value): self
    {
        return new self($value);
    }

    /**
     * @psalm-param T $alternative
     * @psalm-return T
     */
    public function orElse($alternative)
    {
        if (null === $this->value) {
            return $alternative;
        }

        return $this->value;
    }

    /**
     * @psalm-return T
     */
    public function get()
    {
        if (null === $this->value) {
            throw new \Exception();
        }

        return $this->value;
    }

    public function isPresent(): bool
    {
        return null !== $this->value;
    }
}
