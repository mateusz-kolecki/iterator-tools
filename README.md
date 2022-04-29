# Iterator Tools

![Build status](https://github.com/mateusz-kolecki/iterator-tools/actions/workflows/pipeline.yml/badge.svg?branch=master)

---

**Work in progress!**

This library is in an early stage of development. It is not ready for production usage.
Before `v1.0.0` I'm not pushing it to https://packagist.org/.

---

Library provides tools for working with iterators in a convenient way. Promotes functional style
of programming when dealing with `iterable` collections.

Allows defining iterators pipeline separately from actually consuming it.
Allows processing large collections by avoiding loading all data into the memory
at once - each key, value pair returned by source iterator is passed by multiple stages separately.

## Example:

```php
<?php

use IteratorTools\Source\Csv\CsvReader;
use IteratorTools\Source\Csv\CsvReaderOptions;
use function IteratorTools\Iterator\pipeline;
use function IteratorTools\Consumers\int_sum;

$options = CsvReaderOptions::defaults()->withDateColumn('created_at', 'Y-m-d H:i');

// First you need an iterable source from which you can create a stream
$csv = CsvReader::from('some-file.csv', $options)->readAssoc();

// Some helper object
$deadline = new DateTime('2022-03-01 00:00');

// Here we create a "pipeline" using source Iterator
$total = pipeline($csv)
    // First we tell that we want to filter all rows by date
    // (no iteration is happening at this moment)
    ->filter(function (array $row) use ($deadline) {
        return $row['created_at'] >= $deadline;
    })
    // Then we tell that after filtering we want mapping to integer values
    // (still, no iteration happens here)
    ->map(fn($row) => (int)$row['count'])
    // And here we want to consume all integers
    // and compute an integer sum
    // (here $csvReader is consumed; one row from file
    // at a time is filtered, mapped and then summed)
    // This line returns integer value.
    // result: 70
    ->consume(int_sum());
```

Example `some-file.csv` file for the code above:

```csv
id,count,created_at
1,30,"2022-01-01 12:00"
2,10,"2022-02-01 12:00"
3,50,"2022-03-01 12:00"
4,20,"2022-04-01 12:00"
```

## Install using Composer:

```bash
composer require iterator-tools/pipeline
```
