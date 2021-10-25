# Iterator Tools

**Work in progress!**

This library is in early stage of development. It is not ready for production usage.
Before `v1.0.0` I'm not pushing it to https://packagist.org/.

---

Library providing tools for working with iterator in convenient way. Promotes functional style of
working with collections. Allowing to define iterators pipeline separately from running consuming iterators.
Allows to iterate and process large collections by avoiding loading all data to memory when possible.

## Example:

```php
<?php

$csvReader = CsvReader::fromFile('large-file.csv')->assocArrays();
$yesterday = new DateTime('yesterday');

// Here we create a "stream" using $csvReader iterator as a source
$total = IteratorStream::from($csvReader)

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
    ->consume(Consumer:intSum());
```

## Install using Composer:

```bash
# Not available yet!
composer require mkolecki/iterator-tools
```
