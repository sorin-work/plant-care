<?php

namespace App\Household;

use App\Entity\Household;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

/**
 * "Today" as seen by a household: the calendar date in its own timezone.
 *
 * The returned value is midnight of that date in the server's timezone, so it
 * can be compared with the DATE columns of schedules and events.
 */
final class HouseholdClock
{
    public function __construct(private readonly ClockInterface $clock)
    {
    }

    public function today(Household $household): DateTimeImmutable
    {
        $local = $this->clock->now()->setTimezone(new DateTimeZone($household->getTimezone()));

        return new DateTimeImmutable($local->format('Y-m-d'));
    }
}
