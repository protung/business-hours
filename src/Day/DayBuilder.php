<?php

declare(strict_types=1);

namespace Speicher210\BusinessHours\Day;

use Psl\Type;
use Speicher210\BusinessHours\Day\Time\Time;
use Speicher210\BusinessHours\Day\Time\TimeInterval;
use Speicher210\BusinessHours\Day\Time\TimeIntervalInterface;

use function assert;
use function reset;

/**
 * @phpstan-import-type TimeArray from Time
 * @phpstan-type DayArray array{dayOfWeek: int, openingIntervals: list<array{start: TimeArray, end: TimeArray}>, allDay?: bool}
 */
final class DayBuilder
{
    /**
     * @param int                                               $dayOfWeek        The day of week.
     * @param list<TimeIntervalInterface|array{string, string}> $openingIntervals The opening intervals.
     */
    public static function fromArray(int $dayOfWeek, array $openingIntervals): Day
    {
        $intervals = [];
        foreach ($openingIntervals as $interval) {
            if ($interval instanceof TimeIntervalInterface) {
                $intervals[] = $interval;
            } else {
                $intervals[] = new TimeInterval(
                    Time::fromString($interval[0]),
                    Time::fromString($interval[1]),
                );
            }
        }

        $day          = new Day($dayOfWeek, $intervals);
        $dayIntervals = $day->getOpeningHoursIntervals();
        $dayInterval  = reset($dayIntervals);
        assert($dayInterval instanceof TimeIntervalInterface);
        if (self::isIntervalAllDay($dayInterval->getStart(), $dayInterval->getEnd())) {
            return new AllDay($dayOfWeek);
        }

        return $day;
    }

    /**
     * @param DayArray $data The day data.
     */
    public static function fromAssociativeArray(array $data): DayInterface
    {
        $data = self::associativeArrayType()->coerce($data);

        $openingIntervals = [];
        foreach ($data['openingIntervals'] as $openingInterval) {
            $start = Time::fromArray($openingInterval['start']);
            $end   = Time::fromArray($openingInterval['end']);
            if (self::isIntervalAllDay($start, $end)) {
                return new AllDay($data['dayOfWeek']);
            }

            $openingIntervals[] = new TimeInterval($start, $end);
        }

        return new Day($data['dayOfWeek'], $openingIntervals);
    }

    /**
     * @internal
     *
     * @return Type\TypeInterface<DayArray>
     */
    public static function associativeArrayType(): Type\TypeInterface
    {
        $time = Type\shape([
            'hours' => Type\int(),
            'minutes' => Type\optional(Type\int()),
            'seconds' => Type\optional(Type\int()),
        ]);

        return Type\shape([
            'dayOfWeek' => Type\int(),
            'openingIntervals' => Type\vec(Type\shape(['start' => $time, 'end' => $time])),
            'allDay' => Type\optional(Type\bool()),
        ]);
    }

    private static function isIntervalAllDay(Time $start, Time $end): bool
    {
        if ($start->hours() !== 0 || $start->minutes() !== 0 || $start->seconds() !== 0) {
            return false;
        }

        return $end->hours() === 24 && $end->minutes() === 0 && $end->seconds() === 0;
    }
}
