# Iterator Tools

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

use ThirdPartyLibrary\CsvReader;

use function MK\IteratorTools\Iterator\stream;
use function MK\IteratorTools\Consumers\int_sum;

// First you need an iterable source from which you can create a stream

/** @psalm-var \Iterator<array<string, mixed>> $source */ 
$source = CsvReader::fromFile('large-file.csv')->assocArrays();

// Some helper object
$yesterday = new DateTime('yesterday');

// Here we create a "stream" using $source Iterator
$total = stream($source)

    // First we tell that we want to filter all rows by date
    // (no iteration is happening at this moment)
    ->filter(function (array $row) use ($yesterday) {
        return new DateTime($row['created_at']) >= $yesterday;
    })

    // Then we tell that after filtering we want mapping to integer values
    // (still, no iteration happens here)
    ->map(fn(array $row): int => $row['count'])

    // And here we want to consume all integers
    // and compute a integer sum
    // (here $csvReader is consumed; one row from file
    // at a time is filtered, mapped and then summed)
    // This line returns integer value.
    ->consume(int_sum());
```

## Install using Composer:

```bash
# Not available yet!
composer require mkolecki/iterator-tools
```
