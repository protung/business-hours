Business Hours
==============

[![Build](https://github.com/protung/business-hours/actions/workflows/build.yml/badge.svg?branch=3.x)](https://github.com/protung/business-hours/actions/workflows/build.yml?query=branch%3A3.x)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)

## Installation

Require using composer:

```shell
$ composer require speicher210/business-hours
```

## Usage

Define the opening hours of each day of the week in a timezone. You can then check whether a date and time is within
them, and find the previous or next opening or closing.

```php
use Speicher210\BusinessHours\BusinessHours;
use Speicher210\BusinessHours\BusinessHoursBuilder;
use Speicher210\BusinessHours\Day\AllDay;
use Speicher210\BusinessHours\Day\Day;
use Speicher210\BusinessHours\Day\DayBuilder;
use Speicher210\BusinessHours\Day\DayInterface;
use Speicher210\BusinessHours\Day\Time\TimeInterval;

$timezone = new DateTimeZone('Europe/Berlin');

$businessHours = new BusinessHours(
    [
        // Monday from 08:00 to 18:00.
        new Day(DayInterface::WEEK_DAY_MONDAY, [TimeInterval::fromString('08:00', '18:00')]),
        // Tuesday all day, from 00:00 to 24:00.
        new AllDay(DayInterface::WEEK_DAY_TUESDAY),
        // Wednesday with a lunch break. Overlapping intervals are merged into 09:00 to 13:00.
        DayBuilder::fromArray(DayInterface::WEEK_DAY_WEDNESDAY, [['09:00', '12:00'], ['11:30', '13:00'], ['14:00', '18:00']]),
    ],
    $timezone,
);

$date = new DateTimeImmutable('2026-09-30 13:30', $timezone); // a Wednesday

$businessHours->within($date);                    // false
$businessHours->getPreviousChangeDateTime($date); // 2026-09-30 13:00
$businessHours->getNextChangeDateTime($date);     // 2026-09-30 14:00

$businessHours->getNextChangeDateTime(new DateTimeImmutable('2026-09-30 18:30', $timezone)); // 2026-10-05 08:00, the next Monday
```

### Other timezones

The offset between two timezones can change with daylight saving time, so shifting business hours to another timezone
takes a date and time in that timezone:

```php
$newYork = BusinessHoursBuilder::shiftToTimezone(
    $businessHours,
    new DateTimeImmutable('2026-09-30', new DateTimeZone('America/New_York')),
);

$newYork->getDays()[0]->getOpeningTime()->asString(); // 02:00:00, Monday 08:00 in Berlin
```

### Storing business hours

Business hours serialize to JSON, and `BusinessHoursBuilder::fromAssociativeArray()` reads the decoded JSON back. The
array is validated: invalid data throws `Psl\Type\Exception\CoercionException`, which names the invalid path.

```php
$json = json_encode($businessHours, JSON_THROW_ON_ERROR);

$restored = BusinessHoursBuilder::fromAssociativeArray(json_decode($json, true, flags: JSON_THROW_ON_ERROR));
```

### Times

`Time` is an immutable time of day, from 00:00:00 to 24:00:00:

```php
use Speicher210\BusinessHours\Day\Time\Time;

$time = Time::fromString('10:15');

$time->addMinutes(30)->asString();                    // 10:45:00
$time->roundToHour(Time::ROUND_DOWN)->asString();     // 10:00:00
$time->roundToMinutes(30, Time::ROUND_UP)->asString(); // 10:30:00
```

## Development

The tools are run through [just](https://github.com/casey/just) (`just --list` shows every recipe):

```shell
$ just check          # coding standard, static analysis, composer audit and tests
$ just test           # tests
$ just test-mutation  # mutation tests, needs pcov
```

## Credits

Based on [florianv/business](https://github.com/florianv/business) by Florian Voutzinos.

## License

This package is released under the [MIT license](LICENSE.md).
