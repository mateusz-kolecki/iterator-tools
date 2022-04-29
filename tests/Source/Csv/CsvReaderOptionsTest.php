<?php

declare(strict_types=1);

namespace IteratorTools\Source\Csv;

use DateTime;
use PHPUnit\Framework\TestCase;

class CsvReaderOptionsTest extends TestCase
{
    private array $defaultsArray = [
        'max_line_length' => 0,
        'separator' => ',',
        'enclosure' => '"',
        'escape' => '\\',
        'convert_numerics' => false,
        'date_columns' => [],
    ];

    /** @test */
    public function it_should_return_array(): void
    {
        $options = CsvReaderOptions::defaults();

        $array = $options->toArray();

        $this->assertSame($this->defaultsArray, $array);
    }


    /** @test */
    public function it_should_return_array_with_max_line_length(): void
    {
        $expected = $this->defaultsArray;
        $expected['max_line_length'] = 128;

        $array = CsvReaderOptions::defaults()
            ->withMaxLineLength(128)
            ->toArray();

        $this->assertSame($expected, $array);
    }

    /** @test */
    public function it_should_return_array_with_separator(): void
    {
        $expected = $this->defaultsArray;
        $expected['separator'] = "\t";

        $array = CsvReaderOptions::defaults()
            ->withSeparator("\t")
            ->toArray();

        $this->assertSame($expected, $array);
    }

    /** @test */
    public function it_should_return_array_with_enclosure(): void
    {
        $expected = $this->defaultsArray;
        $expected['enclosure'] = "`";

        $array = CsvReaderOptions::defaults()
            ->withEnclosure('`')
            ->toArray();

        $this->assertSame($expected, $array);
    }

    /** @test */
    public function it_should_return_array_with_escape(): void
    {
        $expected = $this->defaultsArray;
        $expected['escape'] = "-";

        $array = CsvReaderOptions::defaults()
            ->withEscape('-')
            ->toArray();

        $this->assertSame($expected, $array);
    }

    /**
     * @psalm-return list<list<bool>>
     */
    public function boolDataProvider(): array
    {
        return  [
            [true],
            [false],
        ];
    }

    /**
     * @test
     * @dataProvider boolDataProvider
     */
    public function it_should_return_array_with_convert_numerics(bool $bool): void
    {
        $expected = $this->defaultsArray;
        $expected['convert_numerics'] = $bool;

        $array = CsvReaderOptions::defaults()
            ->withConvertNumerics($bool)
            ->toArray();

        $this->assertSame($expected, $array);
    }

    /** @test */
    public function it_should_return_array_with_date_columns(): void
    {
        $expected = $this->defaultsArray;
        $expected['date_columns'] = [
            'foo' => DateTime::ATOM,
            'bar' => DateTime::ISO8601,
        ];

        $array = CsvReaderOptions::defaults()
            ->withDateColumns([
                'foo' => DateTime::ATOM,
                'bar' => DateTime::ISO8601,
            ])
            ->toArray();

        $this->assertSame($expected, $array);
    }

    /** @test */
    public function it_should_return_array_with_date_columns_added_separately(): void
    {
        $expected = $this->defaultsArray;
        $expected['date_columns'] = [
            'foo' => DateTime::ATOM,
            1 => DateTime::ISO8601,
        ];

        $array = CsvReaderOptions::defaults()
            ->withDateColumn('foo', DateTime::ATOM)
            ->withDateColumn(1, DateTime::ISO8601)
            ->toArray();

        $this->assertSame($expected, $array);
    }

    /** @test */
    public function it_should_fallback_to_default_when_restoring_form_empty_array(): void
    {
        $options = CsvReaderOptions::fromArray([]);

        $this->assertEquals(CsvReaderOptions::defaults(), $options);
    }

    /** @test */
    public function it_should_include_convert_numerics_when_present(): void
    {
        $options = CsvReaderOptions::fromArray([
            'convert_numerics' => true,
        ]);

        $expected = CsvReaderOptions::defaults()
            ->withConvertNumerics(true);

        $this->assertEquals($expected, $options);
    }

    /** @test */
    public function it_should_include_all_present_options(): void
    {
        $options = CsvReaderOptions::fromArray([
            'max_line_length' => 128,
            'separator' => ';',
            'enclosure' => '`',
            'escape' => '-',
            'convert_numerics' => true,
            'date_columns' => [
                'date' => DateTime::ATOM
            ],
        ]);

        $expected = CsvReaderOptions::defaults()
            ->withMaxLineLength(128)
            ->withSeparator(";")
            ->withEnclosure('`')
            ->withEscape("-")
            ->withConvertNumerics(true)
            ->withDateColumn('date', DateTime::ATOM);

        $this->assertEquals($expected, $options);
    }
}
