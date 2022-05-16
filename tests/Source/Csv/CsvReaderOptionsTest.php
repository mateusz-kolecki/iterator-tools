<?php

declare(strict_types=1);

namespace IteratorTools\Tests\Source\Csv;

use DateTime;
use InvalidArgumentException;
use IteratorTools\Source\Csv\CsvReaderOptions;
use PHPUnit\Framework\TestCase;
use const DATE_ATOM;

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

    /**
     * @test
     * @dataProvider invalidEnclosureProvider
     */
    public function it_should_throw_invalid_argument_exception_when_enclosure_is_not_one_char(
        string $invalidEnclosure
    ): void {
        // expect
        $this->expectException(InvalidArgumentException::class);

        // when
        CsvReaderOptions::defaults()->withEnclosure($invalidEnclosure);
    }

    /**
     * @return list<list<string>>
     */
    public function invalidEnclosureProvider(): array
    {
        return [
            [''],
            ['aa'],
        ];
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
        return [
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
            'max_line_length' => 0,
            'separator' => ';',
            'enclosure' => '`',
            'escape' => '-',
            'convert_numerics' => true,
            'date_columns' => [
                'date' => DateTime::ATOM
            ],
        ]);

        $expected = CsvReaderOptions::defaults()
            ->withMaxLineLength(0)
            ->withSeparator(";")
            ->withEnclosure('`')
            ->withEscape("-")
            ->withConvertNumerics(true)
            ->withDateColumn('date', DateTime::ATOM);

        $this->assertEquals($expected, $options);
    }

    /** @test */
    public function it_should_throw_invalid_argument_exception_when_invalid_max_line_length_given(): void
    {
        // expect
        $this->expectException(InvalidArgumentException::class);

        // when
        CsvReaderOptions::defaults()->withMaxLineLength(-1);
    }

    /**
     * @test
     * @dataProvider invalidEscapeProvider
     */
    public function it_should_throw_invalid_argument_exception_when_invalid_escape(
        string $invalidEscape
    ): void {
        // expect
        $this->expectException(InvalidArgumentException::class);

        // when
        CsvReaderOptions::defaults()->withEscape($invalidEscape);
    }

    /**
     * @return list<list<string>>
     */
    public function invalidEscapeProvider(): array
    {
        return [
            ['aa'],
            ['\\\\'],
        ];
    }

    /** @test */
    public function it_creates_fresh_object_when_calling_with_max_line_length(): void
    {
        $original = CsvReaderOptions::defaults();

        $new = $original->withMaxLineLength(100);

        $this->assertNotSame($new, $original);
    }

    /** @test */
    public function it_creates_fresh_object_when_calling_with_separator(): void
    {
        $original = CsvReaderOptions::defaults();

        $new = $original->withSeparator(',');

        $this->assertNotSame($new, $original);
    }

    /** @test */
    public function it_creates_fresh_object_when_calling_with_convert_numerics(): void
    {
        $original = CsvReaderOptions::defaults();

        $new = $original->withConvertNumerics(true);

        $this->assertNotSame($new, $original);
    }

    /** @test */
    public function it_creates_fresh_object_when_calling_with_date_column(): void
    {
        $original = CsvReaderOptions::defaults();

        $new = $original->withDateColumn(1, DATE_ATOM);

        $this->assertNotSame($new, $original);
    }

    /** @test */
    public function it_creates_fresh_object_when_calling_with_enclosure(): void
    {
        $original = CsvReaderOptions::defaults();

        $new = $original->withEnclosure('"');

        $this->assertNotSame($new, $original);
    }

    /** @test */
    public function it_creates_fresh_object_when_calling_with_escape(): void
    {
        $original = CsvReaderOptions::defaults();

        $new = $original->withEscape('\\');

        $this->assertNotSame($new, $original);
    }
}
