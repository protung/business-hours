<?php

declare(strict_types=1);

namespace Speicher210\BusinessHours\Day\Time;

use InvalidArgumentException;
use Override;
use Psl\Str;

class TimeInterval implements TimeIntervalInterface
{
    protected Time $start;

    protected Time $end;

    /**
     * @throws InvalidArgumentException If the opening time is not earlier than closing time.
     */
    public function __construct(Time $start, Time $end)
    {
        if ($start->isAfterOrEqual($end)) {
            throw new InvalidArgumentException(
                Str\format('The opening time "%s" must be before the closing time "%s".', $start->asString(), $end->asString()),
            );
        }

        $this->start = $start;
        $this->end   = $end;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromString(string $startTime, string $endTime): self
    {
        return new static(Time::fromString($startTime), Time::fromString($endTime));
    }

    #[Override]
    public function contains(Time $time): bool
    {
        return $this->start->isBeforeOrEqual($time) && $this->end->isAfterOrEqual($time);
    }

    #[Override]
    public function getStart(): Time
    {
        return $this->start;
    }

    #[Override]
    public function getEnd(): Time
    {
        return $this->end;
    }

    /**
     * @return array<string,Time>
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'start' => $this->start,
            'end' => $this->end,
        ];
    }

    public function __clone()
    {
        $this->start = clone $this->start;
        $this->end   = clone $this->end;
    }
}
