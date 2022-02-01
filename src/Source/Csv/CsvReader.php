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
     * Create CsvReader instance reading from file or from resource handle.
     *
     * When CsvReader is created from string (file path or URL) then the file
     * is opened and closed automatically. fclose() is called when CsvReader
     * instance is destructed.
     * When CsvReader is created from resource (PHP stream) then the handler
     * is not closed when CsvRead is destructed and handler must be closed
     * manually with fclose().
     *
     * @param string|resource $from when string then it should be file path
     * or URL (anything that can be opened with fopen() function).
     * When resource then it should be valid PHP stream handler (created by fopen())
     *
     * @param CsvReaderOptions $options instance of CsvReaderOptions or null to use defaults
     * @throws InvalidArgumentException when first argument is not a string or handler
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
     * Apply transformations to the IteratorStream
     *
     * @param IteratorStream $stream stream representing CSV source (file of handler)
     * @psalm-param IteratorStream<array-key, array<string|int, string>> $stream
     *
     * @return IteratorStream stream with transformations applied
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
     * Read all lines from CSV source (file or handler) as lists
     *
     * Read all lines and yield each row as a list (indexed array).
     * First field from the CSV line is under index 0, second filed is under 2, and so on.
     *
     * @return IteratorStream stream of indexed arrays
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
     * Read all lines as assoc arrays using first row as keys
     *
     * Read all lines and yield each row as assoc array where first row from
     * the CSV source is used to prepare keys. The first row is not included in
     * the result and is used only as a header.
     *
     * @return IteratorStream stream of assoc arrays
     * @psalm-return IteratorStream<array-key, array<array-key, string|int|float|null|\DateTimeInterface>>
     */
    public function readAssoc(): IteratorStream
    {
        return $this->applyTransformations(
            IteratorStream::from($this->readAllAssoc())
        );
    }
}
