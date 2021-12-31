<?php

declare(strict_types=1);

namespace MK\IteratorTools\Source\Csv;

use Exception;
use Generator;
use InvalidArgumentException;
use LogicException;
use MK\IteratorTools\IteratorStream;
use function array_combine;
use function count;
use function fclose;
use function feof;
use function fgetcsv;
use function fopen;
use function get_resource_type;
use function gettype;
use function rewind;
use function stream_get_meta_data;

class CsvReader
{
    /** @var resource */
    private $fileHandle;
    private bool $closeOnDestruct;
    private CsvReaderOptions $options;

    /**
     * @param resource $fileHandle
     */
    private function __construct($fileHandle, CsvReaderOptions $options = null)
    {
        if ('stream' !== get_resource_type($fileHandle)) {
            throw new InvalidArgumentException("Expected stream resource");
        }

        $this->fileHandle = $fileHandle;
        $this->closeOnDestruct = true;
        $this->options = $options ?: CsvReaderOptions::defaults();
    }

    public function __destruct()
    {
        if ($this->closeOnDestruct) {
            fclose($this->fileHandle);
        }
    }

    /**
     * @param resource $fileHandle
     */
    private static function fromHandle($fileHandle, CsvReaderOptions $options = null): self
    {
        $reader = new self($fileHandle, $options);
        $reader->closeOnDestruct = false;

        return $reader;
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function fromString(string $name, CsvReaderOptions $options = null): self
    {
        $handle = fopen($name, 'r');

        if (false === $handle) {
            throw new InvalidArgumentException("Can't open {$name}");
        }

        return new self($handle, $options);
    }

    /**
     * @param string|resource $from
     */
    public static function from($from, CsvReaderOptions $options = null): self
    {
        $type = gettype($from);

        switch ($type) {
            case 'string':
                return self::fromString($from, $options);

            case 'resource':
                return self::fromHandle($from, $options);
        }

        throw new InvalidArgumentException("Argument \$from should be string or resource but {$type} provided");
    }

    /**
     * @psalm-return Generator<array-key, array<array-key, string>>
     */
    private function readAllGenerator(): Generator
    {
        $fileHandle = $this->fileHandle;
        $seekable = stream_get_meta_data($fileHandle)['seekable'];

        if ($seekable) {
            if (!rewind($fileHandle)) {
                throw new Exception("Could not rewind seekable handler");
            }
        } elseif (feof($fileHandle)) {
            throw new Exception("Reached end of file and handler is not seekable");
        }

        $maxLineLength = $this->options->maxLineLength();
        $separator = $this->options->separator();
        $enclosure = $this->options->enclosure();
        $escape = $this->options->escape();

        for (;;) {
            $line = fgetcsv($fileHandle, $maxLineLength, $separator, $enclosure, $escape);

            // A blank line in a CSV file will be returned as an array comprising
            // a single null field, and will not be treated as an error
            if ($line === [null]) {
                continue;
            }

            // fgetcsv returns null if an invalid handle is supplied
            // or false on other errors, including end of file.

            if (null === $line) {
                throw new Exception("Invalid handle");
            }

            if (false === $line) {
                break;
            }

            yield $line;
        }
    }

    /**
     * @psalm-param IteratorStream<array-key, array<string|int, string>> $stream
     * @psalm-return IteratorStream<array-key, array<string|int, string|int|float|null|\DateTimeInterface>>
     */
    protected function applyTransformations(IteratorStream $stream): IteratorStream
    {
        $dateColumns = $this->options->dateColumns();

        if (count($dateColumns)) {
            $stream = $stream->map(new Transformation\DateColumns($dateColumns));
        }

        if ($this->options->convertNumerics()) {
            $stream = $stream->map(new Transformation\Numerics());
        }

        return $stream;
    }

    /**
     * @psalm-return IteratorStream<array-key, array<array-key, string|int|float|null|\DateTimeInterface>>
     */
    public function read(): IteratorStream
    {
        return $this->applyTransformations(
            IteratorStream::from($this->readAllGenerator())
        );
    }

    /**
     * @psalm-return Generator<array-key, array<array-key, string>>
     */
    private function readAllAssoc(): Generator
    {
        $rows = $this->readAllGenerator();
        $rows->rewind();

        if (!$rows->valid()) {
            return;
        }

        $header = $rows->current();
        $rows->next();

        while ($rows->valid()) {
            $values = $rows->current();

            if (count($values) !== count($header)) {
                throw new LogicException("Number of fields is not the same as in header!");
            }

            yield array_combine($header, $values);

            $rows->next();
        }
    }

    /**
     * @psalm-return IteratorStream<array-key, array<array-key, string|int|float|null|\DateTimeInterface>>
     */
    public function readAssoc(): IteratorStream
    {
        return $this->applyTransformations(
            IteratorStream::from($this->readAllAssoc())
        );
    }
}
