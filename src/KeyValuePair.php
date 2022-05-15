<?php

namespace IteratorTools;

/**
 * @psalm-template K
 * @psalm-template V
 * @psalm-immutable
 */
class KeyValuePair
{
    /** @psalm-var K */
    private $key;

    /** @psalm-var V */
    private $value;

    /**
     * @psalm-param K $key
     * @psalm-param V $value
     */
    private function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * @psalm-template TK
     * @psalm-template TV
     *
     * @psalm-param TK $key
     * @psalm-param TV $value
     * @psalm-return self<TK, TV>
     * @psalm-pure
     */
    public static function from($key, $value): self
    {
        return new self($key, $value);
    }

    /**
     * @psalm-return K
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * @psalm-return V
     */
    public function value()
    {
        return $this->value;
    }
}
