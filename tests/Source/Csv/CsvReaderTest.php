<?php

declare(strict_types=1);

namespace IteratorTools\Source\Csv;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use function fclose;
use function fopen;
use function fwrite;
use function implode;
use function is_resource;

class CsvReaderTest extends TestCase
{
    /** @var resource|null */
    private $handle = null;

    protected function tearDown(): void
    {
        $handle = $this->handle;

        if (is_resource($handle)) {
            fclose($handle);
        }
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

    private function prepareDataUrlWith(string $content): string
    {
        return 'data://text/plain;base64,' . base64_encode($content);
    }

    /**
     * @psalm-return array<string, array{ callable(string):(string|resource) }>
     */
    public function contentFactoryDataProvider(): array
    {
        return [
            'data:// URL'   => [fn (string $content) => $this->prepareDataUrlWith($content)],
            'pipeline handle' => [fn (string $content) => $this->prepareHandleWith($content)],
        ];
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
}
