<?php

declare(strict_types=1);

namespace IteratorTools\Source\Csv\Transformation;

use DateTimeImmutable;
use InvalidArgumentException;
use function is_string;

class DateColumns
{
    /**
     * @psalm-var array<array-key, string>
     */
    private array $dateColumns;

    /**
     * @psalm-param array<array-key, string> $dateColumns
     */
    public function __construct(array $dateColumns)
    {
        $this->dateColumns = $dateColumns;
    }

    /**
     * @psalm-param array<array-key, string> $items
     * @psalm-return array<array-key, string|\DateTimeInterface|null>
     */
    public function __invoke(array $items): array
    {
        foreach ($this->dateColumns as $column => $format) {
            if (!is_string($items[$column])) {
                continue;
            }

            if (empty($items[$column])) {
                $items[$column] = null;
                return $items;
            }

            /** @psalm-var \DateTimeInterface|false */
            $dateTime = DateTimeImmutable::createFromFormat($format, $items[$column]);

            if (false === $dateTime) {
                throw new InvalidArgumentException("Could not parse date column (\"{$column}\") \"{$items[$column]}\"using format \"{$format}\"");
            }

            $items[$column] = $dateTime;
        }

        return $items;
    }
}
