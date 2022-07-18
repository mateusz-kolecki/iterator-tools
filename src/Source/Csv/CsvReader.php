<?php

declare(strict_types=1);

namespace IteratorTools\Source\Csv;

use Generator;
use InvalidArgumentException;
use IteratorTools\IteratorPipeline;
use RuntimeException;

use function array_combine;
use function count;
use function fclose;
use function feof;
use function fgetcsv;
use function fopen;
use function get_resource_type;
use function gettype;
use function restore_error_handler;
use function rewind;
use function set_error_handler;
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
        $this->fileHandle = $fileHandle;
        $this->closeOnDestruct = true;
        $this->options = $options ?: CsvReaderOptions::defaults();
    }

    public function __destruct()
    {
        if ($this->closeOnDestruct) {
            @fclose($this->fileHandle);
            $this->closeOnDestruct = false;
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
     * @throws RuntimeException
     */
    private static function fromString(string $name, CsvReaderOptions $options = null): self
    {
        set_error_handler(function (int $errno, string $error) {
            throw new RuntimeException($error, $errno);
        });

        try {
            $handle = @fopen($name, 'r');
        } finally {
            /** @infection-ignore-all */
            restore_error_handler();
        }

        return new self($handle, $options);
    }

    /**
     * Create CsvReader instance reading from file or from resource handle.
     *
     * When CsvReader is created from string (file path or URL) then the file
     * is opened and closed automatically. fclose() is called when CsvReader
     * instance is destructed.
     * When CsvReader is created from resource (PHP pipeline) then the handler
     * is not closed when CsvRead is destructed and handler must be closed
     * manually with fclose().
     *
     * @param string|resource $from when string then it should be file path
     * or URL (anything that can be opened with fopen() function).
     * When resource then it should be valid PHP pipeline handler (created by fopen())
     *
     * @param CsvReaderOptions|null $options instance of CsvReaderOptions or null to use defaults
     * @throws InvalidArgumentException when first argument is not a string or handler
     * @throws RuntimeException when first argument is string and there was error while trying fopen()
     */
    public static function from($from, CsvReaderOptions $options = null): self
    {
        $type = gettype($from);

        switch ($type) {
            case 'string':
                return self::fromString($from, $options);

            case 'resource':
                if ('stream' === get_resource_type($from)) {
                    return self::fromHandle($from, $options);
                }
        }

        throw new InvalidArgumentException("Argument \$from should be string or stream resource but {$type} provided");
    }

    /**
     * @psalm-return Generator<array-key, array<array-key, string>>
     */
    private function readAllGenerator(): Generator
    {
        $fileHandle = $this->fileHandle;
        $seekable = stream_get_meta_data($fileHandle)['seekable'];

        if ($seekable && !rewind($fileHandle)) {
            throw new RuntimeException("Could not rewind seekable handler");
        } elseif (!$seekable && feof($fileHandle)) {
            throw new RuntimeException("Reached end-of-file and handler is not seekable");
        }

        $maxLineLength = $this->options->maxLineLength();
        $separator = $this->options->separator();
        $enclosure = $this->options->enclosure();
        $escape = $this->options->escape();

        for (; ;) {
            $line = fgetcsv($fileHandle, $maxLineLength, $separator, $enclosure, $escape);

            // A blank line in a CSV file will be returned as an array comprising
            // a single null field, and will not be treated as an error
            if ($line === [null]) {
                continue;
            }

            // fgetcsv returns null if an invalid handle is supplied
            // or false on other errors, including end of file.

            if (null === $line) {
                throw new RuntimeException("Invalid handle");
            }

            if (false === $line) {
                break;
            }

            yield $line;
        }
    }

    /**
     * Apply transformations to the IteratorPipeline
     *
     * @param IteratorPipeline $pipeline pipeline representing CSV source (file of handler)
     * @psalm-param IteratorPipeline<array-key, array<string|int, string>> $pipeline
     *
     * @return IteratorPipeline pipeline with transformations applied
     * @psalm-return IteratorPipeline<array-key, array<string|int, string|int|float|null|\DateTimeInterface>>
     */
    private function applyTransformations(IteratorPipeline $pipeline): IteratorPipeline
    {
        $dateColumns = $this->options->dateColumns();

        if (count($dateColumns)) {
            $pipeline = $pipeline->map(new Transformation\DateColumns($dateColumns));
        }

        if ($this->options->convertNumerics()) {
            $pipeline = $pipeline->map(new Transformation\Numerics());
        }

        return $pipeline;
    }

    /**
     * Read all lines from CSV source (file or handler) as lists
     *
     * Read all lines and yield each row as a list (indexed array).
     * First field from the CSV line is under index 0, second filed is under 2, and so on.
     *
     * @return IteratorPipeline pipeline of indexed arrays
     * @psalm-return IteratorPipeline<array-key, array<array-key, string|int|float|null|\DateTimeInterface>>
     */
    public function read(): IteratorPipeline
    {
        return $this->applyTransformations(
            IteratorPipeline::from($this->readAllGenerator())
        );
    }

    /**
     * @psalm-return Generator<array-key, array<array-key, string>>
     */
    private function readAllAssocGenerator(): Generator
    {
        $rows = $this->readAllGenerator();

        if (!$rows->valid()) {
            return;
        }

        $header = $rows->current();
        $rows->next();

        while ($rows->valid()) {
            $values = $rows->current();

            if (count($values) !== count($header)) {
                throw new RuntimeException("Number of fields is not the same as in header!");
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
     * @return IteratorPipeline pipeline of assoc arrays
     * @psalm-return IteratorPipeline<array-key, array<array-key, string|int|float|null|\DateTimeInterface>>
     */
    public function readAssoc(): IteratorPipeline
    {
        return $this->applyTransformations(
            IteratorPipeline::from($this->readAllAssocGenerator())
        );
    }
}
