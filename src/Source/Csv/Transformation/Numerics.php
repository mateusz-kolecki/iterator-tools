<?php

declare(strict_types=1);

namespace IteratorTools\Source\Csv\Transformation;

use function is_numeric;

class Numerics
{
    /**
     * @psalm-param array<array-key, string|\DateTimeInterface|null> $row
     * @psalm-return array<array-key, string|\DateTimeInterface|null|int|float>
     */
    public function __invoke(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_numeric($value)) {
                $row[$key] = 0 + $value;
            }
        }

        return $row;
    }
}
