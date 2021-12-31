<?php

declare(strict_types=1);

namespace MK\IteratorTools\Source\Csv;

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

    public static function defaults(): self
    {
        return new self();
    }

    /**
     * @psalm-param array{
     *    max_line_length?: int,
     *    separator?: string,
     *    enclosure?: string,
     *    escape?: string,
     *    convert_numerics?: bool,
     *    date_columns?: array<array-key, string>
     * } $array
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

    public function withMaxLineLength(int $maxLineLength): self
    {
        if (0 > $maxLineLength) {
            throw new InvalidArgumentException("Max line length can't negative");
        }

        $clone = clone $this;
        $clone->maxLineLength = $maxLineLength;
        return $clone;
    }

    public function withSeparator(string $separator): self
    {
        if (1 < strlen($separator) || empty($separator)) {
            throw new InvalidArgumentException("Separator must be one character length");
        }

        $clone = clone $this;
        $clone->separator = $separator;
        return $clone;
    }

    public function withEnclosure(string $enclosure): self
    {
        if (1 < strlen($enclosure) || empty($enclosure)) {
            throw new InvalidArgumentException("Enclosure must be one character length");
        }

        $clone = clone $this;
        $clone->enclosure = $enclosure;
        return $clone;
    }

    public function withEscape(string $escape): self
    {
        if (1 < strlen($escape)) {
            throw new InvalidArgumentException("Escape must be one character length or empty");
        }

        $clone = clone $this;
        $clone->escape = $escape;
        return $clone;
    }

    public function withConvertNumerics(bool $convertNumerics): self
    {
        $clone = clone $this;
        $clone->convertNumerics = $convertNumerics;
        return $clone;
    }

    /**
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

    public function withDateColumn(int|string $column, string $format): self
    {
        return $this->withDateColumns([$column => $format]);
    }
}
