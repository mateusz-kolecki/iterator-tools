<?php

declare(strict_types=1);

namespace IteratorTools\Source\Csv;

use InvalidArgumentException;

use function strlen;

class CsvReaderOptions
{
    private int $maxLineLength = 0;
    private string $separator = ',';
    private string $enclosure = '"';
    private string $escape = '\\';
    private bool $convertNumerics = false;
    /** @psalm-var array<array-key,string> */
    private array $dateColumns = [];

    private function __construct()
    {
    }

    /**
     * Create options object with default settings.
     */
    public static function defaults(): self
    {
        return new self();
    }

    /**
     * Create CsvReaderOptions using assoc array.
     *
     * Recreate options from assoc array (that can be produced by calling toArray() method).
     * Should be used to restore options form persistent storage (example: array coming from a config).
     *
     * @param array $array input array with all keys being optional: max_line_length, separator, enclosure, escape, convert_numerics, date_columns
     *
     * @see: CsvReaderOptions::toArray()
     *
     * @psalm-param array{
     *    max_line_length?: int,
     *    separator?: string,
     *    enclosure?: string,
     *    escape?: string,
     *    convert_numerics?: bool,
     *    date_columns?: array<array-key, string>
     * } $array
     *
     * @return self
     */
    public static function fromArray(array $array): self
    {
        $options = self::defaults();

        if (array_key_exists('max_line_length', $array)) {
            $options = $options->withMaxLineLength($array['max_line_length']);
        }

        if (array_key_exists('separator', $array)) {
            $options = $options->withSeparator($array['separator']);
        }

        if (array_key_exists('enclosure', $array)) {
            $options = $options->withEnclosure($array['enclosure']);
        }

        if (array_key_exists('escape', $array)) {
            $options = $options->withEscape($array['escape']);
        }

        if (array_key_exists('convert_numerics', $array)) {
            $options = $options->withConvertNumerics($array['convert_numerics']);
        }

        if (array_key_exists('date_columns', $array)) {
            foreach ($array['date_columns'] as $column => $format) {
                $options = $options->withDateColumn($column, $format);
            }
        }

        return $options;
    }

    /**
     * Create assoc array representation of this options object.
     *
     * Should be used when options object settings must be preserved in storage (example: in config)
     * Array can be consumed by fromArray() method to hydrate new options instance.
     *
     * @see: CsvReaderOptions::toArray()
     *
     * @return array
     *
     * @psalm-return array{
     *    max_line_length: int,
     *    separator: string,
     *    enclosure: string,
     *    escape: string,
     *    convert_numerics: bool,
     *    date_columns: array<array-key, string>
     * }
     */
    public function toArray(): array
    {
        return [
            'max_line_length' => $this->maxLineLength(),
            'separator' => $this->separator(),
            'enclosure' => $this->enclosure(),
            'escape' => $this->escape(),
            'convert_numerics' => $this->convertNumerics(),
            'date_columns' => $this->dateColumns(),
        ];
    }

    public function maxLineLength(): int
    {
        return $this->maxLineLength;
    }

    public function separator(): string
    {
        return $this->separator;
    }

    public function enclosure(): string
    {
        return $this->enclosure;
    }

    public function escape(): string
    {
        return $this->escape;
    }

    public function convertNumerics(): bool
    {
        return $this->convertNumerics;
    }

    /**
     * @psalm-return array<array-key,string>
     */
    public function dateColumns(): array
    {
        return $this->dateColumns;
    }


    /**
     * Create new options object with new max line length
     */
    public function withMaxLineLength(int $maxLineLength): self
    {
        if (0 > $maxLineLength) {
            throw new InvalidArgumentException("Max line length can't negative");
        }

        $clone = clone $this;
        $clone->maxLineLength = $maxLineLength;
        return $clone;
    }

    /**
     * Create new options object with new separator
     */
    public function withSeparator(string $separator): self
    {
        if (1 !== strlen($separator)) {
            throw new InvalidArgumentException("Separator must be one character length");
        }

        $clone = clone $this;
        $clone->separator = $separator;
        return $clone;
    }

    /**
     * Create new options object with new enclosure
     */
    public function withEnclosure(string $enclosure): self
    {
        if (1 !== strlen($enclosure)) {
            throw new InvalidArgumentException("Enclosure must be one character length");
        }

        $clone = clone $this;
        $clone->enclosure = $enclosure;
        return $clone;
    }

    /**
     * Create new options object with new escape character
     */
    public function withEscape(string $escape): self
    {
        if (1 < strlen($escape)) {
            throw new InvalidArgumentException("Escape must be one character length or empty");
        }

        $clone = clone $this;
        $clone->escape = $escape;
        return $clone;
    }

    /**
     * Create new options object with new convert numerics setting
     */
    public function withConvertNumerics(bool $convertNumerics): self
    {
        $clone = clone $this;
        $clone->convertNumerics = $convertNumerics;
        return $clone;
    }

    /**
     * Create new options object with new date columns
     *
     * @psalm-param array<int|string, string> $columns
     */
    public function withDateColumns(array $columns): self
    {
        $clone = clone $this;

        foreach ($columns as $column => $format) {
            $clone->dateColumns[$column] = $format;
        }

        return $clone;
    }

    /**
     * Create new options object with added date column
     *
     * @param int|string $column numerical index or string representing date column in the CSV source
     * @param string $format date format used to parse column value
     */
    public function withDateColumn($column, string $format): self
    {
        return $this->withDateColumns([$column => $format]);
    }
}
