<?php

declare(strict_types=1);

namespace IteratorTools\Source\Csv\Transformation;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

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
     * @psalm-return array<array-key, string|DateTimeInterface|null>
     */
    public function __invoke(array $items): array
    {
        $dates = [];

        foreach ($this->dateColumns as $column => $format) {
            if (empty($items[$column])) {
                $dates[$column] = null;
                continue;
            }

            $dateTime = DateTimeImmutable::createFromFormat($format, $items[$column]);

            if (false === $dateTime) {
                throw new InvalidArgumentException("Could not parse date column (\"{$column}\") \"{$items[$column]}\"using format \"{$format}\"");
            }

            $dates[$column] = $dateTime;
        }

        return $dates + $items;
    }
}
