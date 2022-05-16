<?php

declare(strict_types=1);

namespace IteratorTools\Tests\Source\Csv;

use DateTimeImmutable;
use InvalidArgumentException;
use IteratorTools\Source\Csv\CsvReader;
use IteratorTools\Source\Csv\CsvReaderOptions;
use IteratorTools\Tests\TestAsset\HttpServer;
use IteratorTools\Tests\TestAsset\InMemoryFilesStreamWrapper;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function fclose;
use function file_put_contents;
use function fopen;
use function fwrite;
use function implode;
use function is_resource;
use function stream_wrapper_register;
use function stream_wrapper_unregister;
use function tempnam;

class CsvReaderTest extends TestCase
{
    /** @var resource|null */
    private $handle = null;

    /**
     * @psalm-return array<string, array{ callable(string):(string|resource) }>
     */
    public function contentFactoryDataProvider(): array
    {
        return [
            'data:// URL' => [fn (string $content) => $this->prepareDataUrlWith($content)],
            'pipeline handle' => [fn (string $content) => $this->prepareHandleWith($content)],
            '\tmpfile()' => [fn (string $content) => $this->prepareTmpFile($content)],
        ];
    }

    private function prepareDataUrlWith(string $content): string
    {
        return 'data://text/plain;base64,' . base64_encode($content);
    }

    /**
     * @return resource
     */
    private function prepareHandleWith(string $content)
    {
        $this->handle = fopen('php://memory', 'r+');
        fwrite($this->handle, $content, strlen($content));

        return $this->handle;
    }

    private function prepareTmpFile(string $content): string
    {
        $file = tempnam('/tmp', 'iterator-tools--pipeline-tests');

        if (false === $file) {
            throw new RuntimeException("Cannot create file with \\tmpnam()");
        }

        file_put_contents($file, $content);
        return $file;
    }

    /**
     * @test
     * @dataProvider contentFactoryDataProvider
     * @psalm-param callable(string):(string|resource) $contentFactory
     */
    public function it_is_empty_when_file_is_empty(callable $contentFactory): void
    {
        $reader = CsvReader::from($contentFactory(''));

        $items = $reader->read()->toArray();

        $this->assertEmpty($items);
    }

    /**
     * @test
     * @dataProvider contentFactoryDataProvider
     * @psalm-param callable(string):(string|resource) $contentFactory
     */
    public function it_is_empty_when_file_is_empty_assoc_variant(callable $contentFactory): void
    {
        $reader = CsvReader::from($contentFactory(''));

        $items = $reader->readAssoc()->toArray();

        $this->assertEmpty($items);
    }

    /** @test */
    public function it_throws_when_cannot_open_file(): void
    {
        // expect
        $this->expectException(RuntimeException::class);

        CsvReader::from('garbage-123://foo');
    }

    /**
     * @test
     * @dataProvider contentFactoryDataProvider
     * @psalm-param callable(string):(string|resource) $contentFactory
     */
    public function it_return_non_empty_lines(callable $contentFactory): void
    {
        $content = $contentFactory(implode("\r\n", [
            '',
            '"foo bar",1234',
            '',
            'zet,321',
            '',
        ]));

        $reader = CsvReader::from($content);

        $items = $reader->read()->toArray();

        $expected = [
            ["foo bar", "1234"],
            ["zet", "321"],
        ];

        $this->assertSame($expected, $items);
    }

    /**
     * @test
     * @dataProvider contentFactoryDataProvider
     * @psalm-param callable(string):(string|resource) $contentFactory
     */
    public function it_return_assoc_array_with_header_from_first_non_empty_row(callable $contentFactory): void
    {
        $content = $contentFactory(implode("\r\n", [
            'name,area_km2,population',
            'Asia,4461400,"4.6 billion"',
            'Africa,3036500,"1.3 billion"',
            '"North America",2423000,"580 million"',
            '"South America",1781400,"420 million"',
            '"Antarctica",14200000,"0"',
            'Europe,10000000,"750 million"',
            'Oceania,8510900,"42 million"',
        ]));

        $options = CsvReaderOptions::defaults()
            ->withConvertNumerics(true);

        $reader = CsvReader::from($content, $options);

        $items = $reader->readAssoc()->toArray();

        $expected = [
            [
                'name' => 'Asia',
                'area_km2' => 4461400,
                'population' => '4.6 billion',
            ],
            [
                'name' => 'Africa',
                'area_km2' => 3036500,
                'population' => '1.3 billion',
            ],
            [
                'name' => 'North America',
                'area_km2' => 2423000,
                'population' => '580 million',
            ],
            [
                'name' => 'South America',
                'area_km2' => 1781400,
                'population' => '420 million',
            ],
            [
                'name' => 'Antarctica',
                'area_km2' => 14200000,
                'population' => 0,
            ],
            [
                'name' => 'Europe',
                'area_km2' => 10000000,
                'population' => '750 million',
            ],
            [
                'name' => 'Oceania',
                'area_km2' => 8510900,
                'population' => '42 million',
            ],
        ];

        $this->assertSame($expected, $items);
    }

    /** @test */
    public function it_should_close_file_on_destruction(): void
    {
        InMemoryFilesStreamWrapper::reset();
        InMemoryFilesStreamWrapper::putFile('file.csv', '');

        stream_wrapper_register('memory-file', InMemoryFilesStreamWrapper::class);

        $csv = CsvReader::from('memory-file://file.csv');

        // when
        $csv->__destruct();

        // then
        $this->assertCount(1, InMemoryFilesStreamWrapper::getCalls('file.csv', 'stream_close'));

        // cleanup
        /** @psalm-suppress UnusedFunctionCall */
        stream_wrapper_unregister('memory-file');
    }

    /** @test */
    public function it_will_keep_original_stream_open_when_created_from_resource(): void
    {
        InMemoryFilesStreamWrapper::reset();
        InMemoryFilesStreamWrapper::putFile('file.csv', '');

        stream_wrapper_register('memory-file', InMemoryFilesStreamWrapper::class);

        $stream = fopen('memory-file://file.csv', 'r');

        $csv = CsvReader::from($stream);

        // when
        $csv->__destruct();


        // then
        $this->assertCount(0, InMemoryFilesStreamWrapper::getCalls('file.csv', 'stream_close'));

        // cleanup
        fclose($stream);

        /** @psalm-suppress UnusedFunctionCall */
        stream_wrapper_unregister('memory-file');
    }

    /** @test */
    public function it_throws_runtime_exception_when_trying_read_non_seekable_stream_twice(): void
    {
        $httpServer = new HttpServer();
        $httpServer->start(function (): string {
            return
                "HTTP/1.1 200 OK\r\n" .
                "Content-Type: text/plain\r\n" .
                "Content-Length: 2\r\n" .
                "Connection: close\r\n" .
                "\r\n" .
                "OK";
        });

        try {
            $csv = CsvReader::from($httpServer->getBaseUrl());
            $csv->read()->toArray();

            $this->expectException(RuntimeException::class);

            $csv->read()->toArray();
        } finally {
            $httpServer->stop();
        }
    }

    /** @test */
    public function it_allows_fetching_csv_from_http(): void
    {
        $httpServer = new HttpServer();
        $httpServer->start(function (): string {
            return
                "HTTP/1.1 200 OK\r\n" .
                "Content-Type: text/plain\r\n" .
                "Content-Length: 14\r\n" .
                "Connection: close\r\n" .
                "\r\n" .
                "john,10\n" .
                "doe,20";
        });

        try {
            $csv = CsvReader::from(
                $httpServer->getBaseUrl(),
                CsvReaderOptions::defaults()->withConvertNumerics(true)
            );
            $result = $csv->read()->toArray();
        } finally {
            $httpServer->stop();
        }

        $expected = [
            ['john', 10],
            ['doe', 20],
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * @test
     * @dataProvider contentFactoryDataProvider
     * @psalm-param callable(string):(string|resource) $contentFactory
     */
    public function it_should_convert_assoc_date_columns_to_date_immutable(callable $contentFactory): void
    {
        // given
        $format = DateTimeImmutable::ISO8601;

        $options = CsvReaderOptions::defaults()
            ->withSeparator(";")
            ->withConvertNumerics(true)
            ->withDateColumns([
                'created_at' => $format,
                'updated_at' => $format,
            ]);

        $content = $contentFactory(implode("\r\n", [
            'id;created_at;name;updated_at',
            '1;"2021-12-26T10:00:31+0100";John;"2021-12-27T11:34:24+0100"',
            '2;"2021-12-27T12:35:00+0100";"Mark";',
            '3;;"Marry";2021-12-29T13:12:00+0100',
        ]));

        $reader = CsvReader::from($content, $options);

        // when
        $result = $reader->readAssoc()->toArray();

        // then
        $expected = [
            [
                'id' => 1,
                'created_at' => DateTimeImmutable::createFromFormat($format, '2021-12-26T10:00:31+0100'),
                'name' => 'John',
                'updated_at' => DateTimeImmutable::createFromFormat($format, '2021-12-27T11:34:24+0100'),
            ],
            [
                'id' => 2,
                'created_at' => DateTimeImmutable::createFromFormat($format, '2021-12-27T12:35:00+0100'),
                'name' => 'Mark',
                'updated_at' => null,
            ],
            [
                'id' => 3,
                'created_at' => null,
                'name' => 'Marry',
                'updated_at' => DateTimeImmutable::createFromFormat($format, '2021-12-29T13:12:00+0100'),
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * @dataProvider contentFactoryDataProvider
     * @psalm-param callable(string):(string|resource) $contentFactory
     */
    public function it_should_convert_indexed_date_columns_to_date_immutable(callable $contentFactory): void
    {
        // given
        $format = DateTimeImmutable::ISO8601;

        $options = CsvReaderOptions::defaults()
            ->withSeparator(";")
            ->withConvertNumerics(true)
            ->withDateColumn(1, $format)
            ->withDateColumn(3, $format);

        $content = $contentFactory(implode("\r\n", [
            '1;"2021-12-26T10:00:31+0100";John;"2021-12-27T11:34:24+0100"',
            '2;"2021-12-27T12:35:00+0100";"Mark";',
        ]));

        $reader = CsvReader::from($content, $options);

        // when
        $result = $reader->read()->toArray();

        // then
        $expected = [
            [
                1,
                DateTimeImmutable::createFromFormat($format, '2021-12-26T10:00:31+0100'),
                'John',
                DateTimeImmutable::createFromFormat($format, '2021-12-27T11:34:24+0100'),
            ],
            [
                2,
                DateTimeImmutable::createFromFormat($format, '2021-12-27T12:35:00+0100'),
                'Mark',
                null,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * @dataProvider contentFactoryDataProvider
     * @psalm-param callable(string):(string|resource) $contentFactory
     */
    public function it_should_throw_runtime_exception_when_one_of_the_rows_has_less_elements_than_the_header(
        callable $contentFactory
    ): void {
        $content =
            "id,name,number\n" .
            "1,john,123\n" .
            "1,321\n" .
            "3,mat,543";

        $csv = CsvReader::from($contentFactory($content));

        $iterator = $csv->readAssoc()->getIterator();
        $iterator->rewind();

        $this->expectException(RuntimeException::class);

        // when
        $iterator->next();
    }

    /**
     * @test
     * @dataProvider invalidSeparatorProvider
     */
    public function it_should_throw_invalid_argument_exception_when_separator_is_not_one_char(
        string $invalidSeparator
    ): void {
        // expect
        $this->expectException(InvalidArgumentException::class);

        // when
        CsvReaderOptions::defaults()->withSeparator($invalidSeparator);
    }

    /**
     * @return list<list<string>>
     */
    public function invalidSeparatorProvider(): array
    {
        return [
            [''],
            ['""'],
            [' . '],
        ];
    }

    /**
     * @test
     * @dataProvider contentFactoryDataProvider
     * @psalm-param callable(string):(string|resource) $contentFactory
     */
    public function it_should_throw_invalid_argument_exception_when_cannot_parse_date(
        callable $contentFactory
    ): void {
        // given
        $format = DateTimeImmutable::ISO8601;

        $options = CsvReaderOptions::defaults()
            ->withDateColumn(0, $format);

        $content = $contentFactory(implode("\r\n", [
            '"aaaa-12-26T10:00:31+0100"',
        ]));

        $reader = CsvReader::from($content, $options);

        // expect
        $this->expectException(InvalidArgumentException::class);

        // when
        $reader->read()->toArray();
    }

    /** @test */
    public function it_should_throw_invalid_argument_exception_when_creating_from_not_string_and_not_from_stream(): void
    {
        $this->expectException(InvalidArgumentException::class);

        /** @psalm-suppress InvalidArgument */
        CsvReader::from(false);
    }

    protected function tearDown(): void
    {
        $handle = $this->handle;

        if (is_resource($handle)) {
            fclose($handle);
        }
    }
}
