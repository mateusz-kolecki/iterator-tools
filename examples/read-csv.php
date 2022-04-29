<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use IteratorTools\Source\Csv\CsvReader;
use IteratorTools\Source\Csv\CsvReaderOptions;
use function IteratorTools\Iterator\pipeline;
use function IteratorTools\Consumers\int_sum;

$options = CsvReaderOptions::defaults()
    ->withDateColumn('created_at', 'Y-m-d H:i');

$csv = CsvReader::from('some-file.csv', $options)->readAssoc();

$deadline = new DateTime('2022-03-01 00:00');

$total = pipeline($csv)
    ->filter(function (array $row) use ($deadline) {
        return $row['created_at'] >= $deadline;
    })
    ->map(fn($row) => (int)$row['count'])
    ->consume(int_sum());

echo "Total: {$total}\n";