<?php

declare(strict_types=1);

namespace MK\IteratorTools\TestAsset;

class Person
{
    private string $name;
    private int $age;

    public function __construct(string $name, int $age)
    {
        $this->name = $name;
        $this->age = $age;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function age(): int
    {
        return $this->age;
    }
}
